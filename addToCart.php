<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch product name, stock quantity and pharmacy_id from database
    $conn = get_db_connection();
    $product_name = "Product";
    $available_stock = 50;
    $prod_pharmacy_id = 1;

    $p_stmt = $conn->prepare("SELECT prdct_name, stock_quantity, pharmacy_id FROM tbl_products WHERE prdct_id = ?");
    if ($p_stmt) {
        $p_stmt->bind_param("i", $id);
        $p_stmt->execute();
        $p_result = $p_stmt->get_result();
        if ($p_result && $p_result->num_rows > 0) {
            $p_row = $p_result->fetch_assoc();
            $product_name = $p_row['prdct_name'];
            $available_stock = (int)($p_row['stock_quantity'] ?? 50);
            $prod_pharmacy_id = isset($p_row['pharmacy_id']) ? (int)$p_row['pharmacy_id'] : 1;
        }
        $p_stmt->close();
    }

    $pharm_details = get_pharmacy_details($prod_pharmacy_id);

    // Tenant boundary check: prevent mixed orders from different pharmacies
    if (!empty($_SESSION['cart']) && isset($_SESSION['cart_pharmacy_id']) && (int)$_SESSION['cart_pharmacy_id'] !== $prod_pharmacy_id) {
        $old_pharmacy = get_pharmacy_details((int)$_SESSION['cart_pharmacy_id']);
        $_SESSION['cart'] = []; // Reset cart for new pharmacy store
        $_SESSION['toast'] = [
            'type' => 'info',
            'title' => 'Cart Switched Store',
            'message' => 'Your cart was cleared to start a new order with ' . $pharm_details['name'] . '.'
        ];
    }

    $_SESSION['cart_pharmacy_id'] = $prod_pharmacy_id;
    $_SESSION['current_pharmacy_id'] = $prod_pharmacy_id;

    $requested_qty = isset($_GET['quantity']) ? max(1, (int)$_GET['quantity']) : 1;
    $current_qty = isset($_SESSION['cart'][$id]['quantity']) ? (int)$_SESSION['cart'][$id]['quantity'] : 0;
    $total_requested = $current_qty + $requested_qty;

    if ($available_stock <= 0) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Out of Stock',
            'message' => '"' . $product_name . '" is currently out of stock.'
        ];
        header('location: cart.php');
        exit();
    } elseif ($total_requested > $available_stock) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Stock Limit Exceeded',
            'message' => 'Only ' . $available_stock . ' units of "' . $product_name . '" are available in stock.'
        ];
        $_SESSION['cart'][$id]['quantity'] = $available_stock;
        header('location: cart.php');
        exit();
    } else {
        $_SESSION['cart'][$id]['quantity'] = $total_requested;
        if (!isset($_SESSION['toast']) || $_SESSION['toast']['type'] !== 'info') {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Added to Cart',
                'message' => '"' . $product_name . '" added to cart (' . $pharm_details['name'] . ').'
            ];
        }
    }

    header('location: cart.php');
    exit();
}
?>