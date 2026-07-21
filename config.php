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

