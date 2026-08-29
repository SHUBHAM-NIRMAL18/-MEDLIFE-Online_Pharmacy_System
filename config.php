<?php
// Load .env variables manually
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Helper to retrieve environment variables
if (!function_exists('env')) {
    function env($key, $default = null) {
        $value = getenv($key);
        if ($value === false) {
            $value = isset($_ENV[$key]) ? $_ENV[$key] : (isset($_SERVER[$key]) ? $_SERVER[$key] : null);
        }
        if ($value === null || $value === false) {
            return $default;
        }
        return $value;
    }
}

// Central database credentials
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'medlife'));

require_once __DIR__ . '/config/saas_setup.php';

// Centralized database connection function
if (!function_exists('get_db_connection')) {
    function get_db_connection() {
        static $conn = null;
        if ($conn === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                die("Database connection failed: " . $conn->connect_error);
            }
            ensure_product_stock_column($conn);
            ensure_category_parent_column($conn);
            run_saas_migrations($conn);
        }
        return $conn;
    }
}

// Helper to ensure stock_quantity column exists in tbl_products
if (!function_exists('ensure_product_stock_column')) {
    function ensure_product_stock_column($conn) {
        static $checked = false;
        if (!$checked) {
            $check = $conn->query("SHOW COLUMNS FROM tbl_products LIKE 'stock_quantity'");
            if ($check && $check->num_rows === 0) {
                @$conn->query("ALTER TABLE tbl_products ADD COLUMN stock_quantity INT NOT NULL DEFAULT 50");
            }
            $checked = true;
        }
    }
}

// Helper to ensure parent_id column exists in tbl_categories
if (!function_exists('ensure_category_parent_column')) {
    function ensure_category_parent_column($conn) {
        static $checked = false;
        if (!$checked) {
            $check = $conn->query("SHOW COLUMNS FROM tbl_categories LIKE 'parent_id'");
            if ($check && $check->num_rows === 0) {
                @$conn->query("ALTER TABLE tbl_categories ADD COLUMN parent_id INT NOT NULL DEFAULT 0");
            }
            $checked = true;
        }
    }
}

// Helper to get active customer/visitor pharmacy context
if (!function_exists('get_current_pharmacy_id')) {
    function get_current_pharmacy_id() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Allow URL override e.g. ?pharmacy=2
        if (isset($_GET['pharmacy']) && is_numeric($_GET['pharmacy'])) {
            $req_id = (int)$_GET['pharmacy'];
            $conn = get_db_connection();
            $chk = $conn->query("SELECT pharmacy_id FROM tbl_pharmacies WHERE pharmacy_id = $req_id AND status = 1");
            if ($chk && $chk->num_rows > 0) {
                $_SESSION['current_pharmacy_id'] = $req_id;
            }
        }
        return isset($_SESSION['current_pharmacy_id']) ? (int)$_SESSION['current_pharmacy_id'] : 1;
    }
}

// Helper to set customer/visitor pharmacy context
if (!function_exists('set_current_pharmacy_id')) {
    function set_current_pharmacy_id($id) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['current_pharmacy_id'] = (int)$id;
    }
}

// Helper to fetch details of a specific pharmacy
if (!function_exists('get_pharmacy_details')) {
    function get_pharmacy_details($pharmacy_id) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT * FROM tbl_pharmacies WHERE pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $pharmacy_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $data = $res->fetch_assoc();
                if (empty($data['pan_number'])) $data['pan_number'] = '609823145';
                if (empty($data['business_hours'])) $data['business_hours'] = 'Sun - Fri: 8:00 AM - 9:00 PM';
                return $data;
            }
        }
        return [
            'pharmacy_id' => 1,
            'name' => 'MedLife Central Pharmacy',
            'slug' => 'medlife-central',
            'email' => 'central@medlife.com',
            'phone' => '9800000000',
            'address' => 'Central Health Plaza, Kathmandu',
            'logo' => 'img/pharmacy-default.png',
            'pan_number' => '609823145',
            'business_hours' => 'Sun - Fri: 8:00 AM - 9:00 PM',
            'plan' => 'Enterprise',
            'delivery_fee' => 100.00,
            'status' => 1
        ];
    }
}

// Helper to get all active pharmacies with metadata for customer selector and directory
if (!function_exists('get_active_pharmacies')) {
    function get_active_pharmacies() {
        $conn = get_db_connection();
        $list = [];
        $res = $conn->query("SELECT p.*, 
                                    (SELECT COUNT(*) FROM tbl_products pr WHERE pr.pharmacy_id = p.pharmacy_id AND pr.prdct_status = 1) AS product_count,
                                    (SELECT COUNT(*) FROM tbl_categories c WHERE c.pharmacy_id = p.pharmacy_id AND c.cat_status = 1) AS category_count
                             FROM tbl_pharmacies p 
                             WHERE p.status = 1 
                             ORDER BY p.pharmacy_id ASC");
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }
        return $list;
    }
}

// Helper to safely resolve product image URL
if (!function_exists('get_product_image_url')) {
    function get_product_image_url($img_path) {
        if (empty($img_path)) {
            return 'medimg/img1.jpg';
        }
        if (strpos($img_path, 'http://') === 0 || strpos($img_path, 'https://') === 0) {
            return $img_path;
        }
        $clean = trim($img_path);
        if (file_exists(__DIR__ . '/' . $clean)) {
            return $clean;
        }
        if (file_exists(__DIR__ . '/medimg/' . $clean)) {
            return 'medimg/' . $clean;
        }
        if (file_exists(__DIR__ . '/img/' . $clean)) {
            return 'img/' . $clean;
        }
        return 'medimg/' . $clean;
    }
}

// Helper to get current admin's pharmacy ID strictly
if (!function_exists('get_admin_pharmacy_id')) {
    function get_admin_pharmacy_id() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
    }
}

// Helper to check if logged in as Super Admin
if (!function_exists('is_super_admin')) {
    function is_super_admin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['super_admin_login']) && $_SESSION['super_admin_login'] === true;
    }
}

// Security Middleware to validate admin authentication and tenant active status
if (!function_exists('require_admin_tenant')) {
    function require_admin_tenant() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
            header('Location: admin_login.php?msg=1');
            exit();
        }

        $pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
        $conn = get_db_connection();

        // Check if the tenant pharmacy is suspended
        $stmt = $conn->prepare("SELECT name, status FROM tbl_pharmacies WHERE pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $pharmacy_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $pharm = $res->fetch_assoc();
                if ((int)$pharm['status'] === 0 && !isset($_SESSION['impersonating_super_admin'])) {
                    // Tenant is suspended! Clear admin session and redirect with notice
                    unset($_SESSION['admin_login'], $_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_name'], $_SESSION['admin_pharmacy_id'], $_SESSION['admin_pharmacy_name']);
                    $_SESSION['toast'] = [
                        'type' => 'error',
                        'title' => 'Pharmacy Suspended',
                        'message' => 'Your pharmacy account has been suspended by platform administration. Please contact support.'
                    ];
                    header('Location: admin_login.php?suspended=1');
                    exit();
                }
            }
            $stmt->close();
        }
        return $pharmacy_id;
    }
}

// Helper to get active admin staff role (admin, pharmacist, cashier, driver)
if (!function_exists('get_current_admin_role')) {
    function get_current_admin_role() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['admin_role']) && is_string($_SESSION['admin_role']) ? strtolower(trim($_SESSION['admin_role'])) : 'admin';
    }
}

// Role-Based Access Control Guard
if (!function_exists('require_admin_role')) {
    function require_admin_role($allowed_roles = ['admin']) {
        require_admin_tenant();
        $current_role = get_current_admin_role();
        if (is_string($allowed_roles)) {
            $allowed_roles = [$allowed_roles];
        }
        $allowed_roles = array_map('strtolower', $allowed_roles);
        
        // Super admin impersonation always allowed
        if (isset($_SESSION['impersonating_super_admin']) && $_SESSION['impersonating_super_admin'] === true) {
            return true;
        }

        if (!in_array($current_role, $allowed_roles) && $current_role !== 'admin') {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Permission Denied',
                'message' => 'Your staff role (' . ucfirst($current_role) . ') does not have permission to access this module.'
            ];
            header('Location: admin_home.php');
            exit();
        }
        return true;
    }
}
