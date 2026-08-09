<?php
/**
 * SaaS Database Auto-Migrator & Setup Script
 * Ensures multi-tenant tables and pharmacy_id columns exist across all operational tables.
 */

if (!function_exists('run_saas_migrations')) {
    function run_saas_migrations($conn) {
        static $migrated = false;
        if ($migrated) return;

        // 1. Create tbl_pharmacies (Tenants Table)
        $sql_pharmacies = "CREATE TABLE IF NOT EXISTS tbl_pharmacies (
            pharmacy_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            address TEXT,
            logo VARCHAR(255) DEFAULT 'img/pharmacy-default.png',
            pan_number VARCHAR(50) DEFAULT '609823145',
            business_hours VARCHAR(255) DEFAULT 'Sun - Fri: 8:00 AM - 9:00 PM',
            plan VARCHAR(50) DEFAULT 'Pro',
            delivery_fee DECIMAL(10, 2) DEFAULT 100.00,
            status TINYINT(1) DEFAULT 1 COMMENT '1 = Active, 0 = Suspended',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_pharmacies);

        // Ensure pan_number and business_hours columns exist if table was already created
        $chk_pan = $conn->query("SHOW COLUMNS FROM tbl_pharmacies LIKE 'pan_number'");
        if ($chk_pan && $chk_pan->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_pharmacies ADD COLUMN pan_number VARCHAR(50) DEFAULT '609823145' AFTER logo");
        }
        $chk_bh = $conn->query("SHOW COLUMNS FROM tbl_pharmacies LIKE 'business_hours'");
        if ($chk_bh && $chk_bh->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_pharmacies ADD COLUMN business_hours VARCHAR(255) DEFAULT 'Sun - Fri: 8:00 AM - 9:00 PM' AFTER pan_number");
        }

        // Seed default initial pharmacy if empty
        $check_pharmacy = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_pharmacies");
        if ($check_pharmacy) {
            $cnt = (int)$check_pharmacy->fetch_assoc()['cnt'];
            if ($cnt === 0) {
                $conn->query("INSERT INTO tbl_pharmacies (pharmacy_id, name, slug, email, phone, address, pan_number, business_hours, plan, delivery_fee, status) 
                              VALUES (1, 'MedLife Central Pharmacy', 'medlife-central', 'central@medlife.com', '9800000000', 'Central Health Plaza, Kathmandu', '609823145', 'Sun - Fri: 8:00 AM - 9:00 PM', 'Enterprise', 100.00, 1)");
                seed_default_pharmacy_categories($conn, 1);
            }
        }

        // 2. Create tbl_super_admin (SaaS Platform Super Admins)
        $sql_super_admin = "CREATE TABLE IF NOT EXISTS tbl_super_admin (
            super_admin_id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_super_admin);

        // Seed default Super Admin account (admin@medlifesaas.com / admin123)
        $check_sa = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_super_admin");
        if ($check_sa) {
            $cnt_sa = (int)$check_sa->fetch_assoc()['cnt'];
            if ($cnt_sa === 0) {
                $sa_pass = md5('admin123');
                $conn->query("INSERT INTO tbl_super_admin (name, email, password) 
                              VALUES ('SaaS Platform Administrator', 'admin@medlifesaas.com', '$sa_pass')");
            }
        }

        // 3. Helper to add pharmacy_id column to operational tables if missing
        $tables_to_migrate = [
            'tbl_admin',
            'tbl_products',
            'tbl_categories',
            'tbl_order',
            'tbl_cart',
            'tbl_wishlist',
            'tbl_user'
        ];

        foreach ($tables_to_migrate as $table) {
            // Check if table exists first
            $chk_tbl = $conn->query("SHOW TABLES LIKE '$table'");
            if ($chk_tbl && $chk_tbl->num_rows > 0) {
                $chk_col = $conn->query("SHOW COLUMNS FROM $table LIKE 'pharmacy_id'");
                if ($chk_col && $chk_col->num_rows === 0) {
                    @$conn->query("ALTER TABLE $table ADD COLUMN pharmacy_id INT NOT NULL DEFAULT 1");
                }
            }
        }

        $migrated = true;
    }
}

/**
 * Automatically seeds default category taxonomy for newly registered pharmacies.
 */
if (!function_exists('seed_default_pharmacy_categories')) {
    function seed_default_pharmacy_categories($conn, $pharmacy_id) {
        $pharmacy_id = (int)$pharmacy_id;
        if ($pharmacy_id <= 0) return;

        // Check if categories already exist for this pharmacy
        $check = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_categories WHERE pharmacy_id = $pharmacy_id");
        if ($check) {
            $cnt = (int)$check->fetch_assoc()['cnt'];
            if ($cnt > 0) return;
        }

        $default_tree = [
            'Prescription Medicines' => [
                'Antibiotics & Anti-Infectives',
                'Pain Relief & Fever',
                'Cardiovascular & Blood Pressure',
                'Respiratory & Asthma Care'
            ],
            'Vitamins & Supplements' => [
                'Daily Multivitamins',
                'Protein Powders & Gym',
                'Herbal & Ayurvedic Supplements',
                'Calcium & Bone Health'
            ],
            'Medical Devices & Healthcare' => [
                'Blood Pressure Monitors',
                'Thermometers & Diagnostic Tools',
                'First Aid Kits & Bandages',
                'Orthopedic Supports & Braces'
            ]
        ];

        foreach ($default_tree as $root_name => $subcats) {
            $stmt_root = $conn->prepare("INSERT INTO tbl_categories (cat_name, cat_status, parent_id, pharmacy_id) VALUES (?, 1, 0, ?)");
            if ($stmt_root) {
                $stmt_root->bind_param("si", $root_name, $pharmacy_id);
                $stmt_root->execute();
                $root_id = $conn->insert_id;
                $stmt_root->close();

                if ($root_id > 0) {
                    foreach ($subcats as $sub_name) {
                        $stmt_sub = $conn->prepare("INSERT INTO tbl_categories (cat_name, cat_status, parent_id, pharmacy_id) VALUES (?, 1, ?, ?)");
                        if ($stmt_sub) {
                            $stmt_sub->bind_param("sii", $sub_name, $root_id, $pharmacy_id);
                            $stmt_sub->execute();
                            $stmt_sub->close();
                        }
                    }
                }
            }
        }
    }
}

