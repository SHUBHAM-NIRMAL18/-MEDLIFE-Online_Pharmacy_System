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

        // 3. Create tbl_product_batches (Batch & Expiry Date Management)
        $sql_batches = "CREATE TABLE IF NOT EXISTS tbl_product_batches (
            batch_id INT AUTO_INCREMENT PRIMARY KEY,
            pharmacy_id INT NOT NULL DEFAULT 1,
            prdct_id INT NOT NULL,
            batch_number VARCHAR(100) NOT NULL,
            mfg_date DATE NOT NULL,
            exp_date DATE NOT NULL,
            purchase_cost DECIMAL(10, 2) DEFAULT 0.00,
            mrp_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            quantity INT NOT NULL DEFAULT 0,
            initial_quantity INT NOT NULL DEFAULT 0,
            status TINYINT(1) DEFAULT 1 COMMENT '1 = Active, 0 = Depleted/Disposed',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pharmacy_batch (pharmacy_id, prdct_id),
            INDEX idx_exp_date (exp_date),
            INDEX idx_batch_num (batch_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_batches);

        // 4. Create tbl_pos_sales (Point of Sale Counter Invoices)
        $sql_pos = "CREATE TABLE IF NOT EXISTS tbl_pos_sales (
            sale_id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_no VARCHAR(100) NOT NULL UNIQUE,
            pharmacy_id INT NOT NULL DEFAULT 1,
            customer_name VARCHAR(255) DEFAULT 'Walk-in Customer',
            customer_phone VARCHAR(50) DEFAULT '',
            customer_pan VARCHAR(50) DEFAULT '',
            subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            discount_percent DECIMAL(5, 2) DEFAULT 0.00,
            discount_amount DECIMAL(10, 2) DEFAULT 0.00,
            tax_percent DECIMAL(5, 2) DEFAULT 0.00,
            tax_amount DECIMAL(10, 2) DEFAULT 0.00,
            grand_total DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            payment_method VARCHAR(50) DEFAULT 'Cash',
            tendered_amount DECIMAL(10, 2) DEFAULT 0.00,
            change_amount DECIMAL(10, 2) DEFAULT 0.00,
            cashier_name VARCHAR(100) DEFAULT 'Staff',
            notes TEXT,
            sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pharmacy_sale (pharmacy_id, sale_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_pos);

        // 5. Create tbl_pos_items (POS Bill Item Details)
        $sql_pos_items = "CREATE TABLE IF NOT EXISTS tbl_pos_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            prdct_id INT NOT NULL,
            batch_id INT DEFAULT NULL,
            batch_number VARCHAR(100) DEFAULT '',
            prdct_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            item_total DECIMAL(10, 2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sale_id (sale_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_pos_items);

        // 6. Create tbl_delivery_drivers (Delivery Fleet Management)
        $sql_drivers = "CREATE TABLE IF NOT EXISTS tbl_delivery_drivers (
            driver_id INT AUTO_INCREMENT PRIMARY KEY,
            pharmacy_id INT NOT NULL DEFAULT 1,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(50) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(255) NOT NULL,
            vehicle_type VARCHAR(50) DEFAULT 'Motorcycle',
            vehicle_number VARCHAR(50) DEFAULT '',
            license_number VARCHAR(50) DEFAULT '',
            status TINYINT(1) DEFAULT 1 COMMENT '1 = Active/Available, 2 = On Delivery, 0 = Inactive',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_pharmacy_driver (pharmacy_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        @$conn->query($sql_drivers);

        // Ensure role column exists in tbl_admin (RBAC)
        $chk_role = $conn->query("SHOW COLUMNS FROM tbl_admin LIKE 'role'");
        if ($chk_role && $chk_role->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_admin ADD COLUMN role VARCHAR(50) DEFAULT 'admin' AFTER status");
        }

        // Ensure driver_id and delivery tracking columns exist in tbl_order
        $chk_drv = $conn->query("SHOW COLUMNS FROM tbl_order LIKE 'driver_id'");
        if ($chk_drv && $chk_drv->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_order ADD COLUMN driver_id INT DEFAULT NULL AFTER pharmacy_id");
        }
        $chk_dn = $conn->query("SHOW COLUMNS FROM tbl_order LIKE 'delivery_notes'");
        if ($chk_dn && $chk_dn->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_order ADD COLUMN delivery_notes TEXT DEFAULT NULL AFTER driver_id");
        }
        $chk_da = $conn->query("SHOW COLUMNS FROM tbl_order LIKE 'delivered_at'");
        if ($chk_da && $chk_da->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_order ADD COLUMN delivered_at DATETIME DEFAULT NULL AFTER delivery_notes");
        }
        $chk_ed = $conn->query("SHOW COLUMNS FROM tbl_order LIKE 'estimated_delivery'");
        if ($chk_ed && $chk_ed->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_order ADD COLUMN estimated_delivery VARCHAR(100) DEFAULT NULL AFTER delivered_at");
        }

        // Ensure batch_number column exists in tbl_products
        $chk_b = $conn->query("SHOW COLUMNS FROM tbl_products LIKE 'batch_number'");
        if ($chk_b && $chk_b->num_rows === 0) {
            @$conn->query("ALTER TABLE tbl_products ADD COLUMN batch_number VARCHAR(100) DEFAULT 'BATCH-001' AFTER stock_quantity");
        }

        // Helper to add pharmacy_id column to operational tables if missing
        $tables_to_migrate = [
            'tbl_admin',
            'tbl_products',
            'tbl_categories',
            'tbl_order',
            'tbl_cart',
            'tbl_wishlist',
            'tbl_user',
            'tbl_product_batches',
            'tbl_pos_sales',
            'tbl_delivery_drivers'
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

        // Seed default delivery driver if tbl_delivery_drivers is empty
        $chk_drivers = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_delivery_drivers");
        if ($chk_drivers) {
            $driver_cnt = (int)$chk_drivers->fetch_assoc()['cnt'];
            if ($driver_cnt === 0) {
                $driver_pass = md5('driver123');
                $conn->query("INSERT INTO tbl_delivery_drivers (pharmacy_id, name, phone, email, password, vehicle_type, vehicle_number, license_number, status) 
                              VALUES (1, 'Kiran Shrestha (Fast Courier)', '9841234567', 'driver@medlife.com', '$driver_pass', 'Motorcycle', 'BA 2 PA 9876', '01-06-87941', 1)");
            }
        }

        // Seed default batches for existing products if tbl_product_batches is empty
        $chk_batches = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_product_batches");
        if ($chk_batches) {
            $batch_cnt = (int)$chk_batches->fetch_assoc()['cnt'];
            if ($batch_cnt === 0) {
                $prods = $conn->query("SELECT prdct_id, pharmacy_id, prdct_price, manf_date, exp_date, stock_quantity, batch_number FROM tbl_products");
                if ($prods && $prods->num_rows > 0) {
                    while ($p = $prods->fetch_assoc()) {
                        $pid = (int)$p['prdct_id'];
                        $phid = (int)($p['pharmacy_id'] ?? 1);
                        if ($phid <= 0) $phid = 1;
                        $bnum = !empty($p['batch_number']) ? $p['batch_number'] : ('BAT-' . str_pad($pid, 4, '0', STR_PAD_LEFT));
                        $mfg = !empty($p['manf_date']) ? $p['manf_date'] : date('Y-m-d', strtotime('-6 months'));
                        $exp = !empty($p['exp_date']) ? $p['exp_date'] : date('Y-m-d', strtotime('+18 months'));
                        $price = (float)$p['prdct_price'];
                        $cost = round($price * 0.70, 2);
                        $qty = (int)($p['stock_quantity'] ?? 50);
                        
                        $conn->query("INSERT INTO tbl_product_batches (pharmacy_id, prdct_id, batch_number, mfg_date, exp_date, purchase_cost, mrp_price, quantity, initial_quantity, status)
                                      VALUES ($phid, $pid, '$bnum', '$mfg', '$exp', $cost, $price, $qty, $qty, 1)");
                    }
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

