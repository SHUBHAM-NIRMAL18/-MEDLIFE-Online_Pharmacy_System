<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch product name and stock quantity from database
    $conn = get_db_connection();
    $product_name = "Product";
    $available_stock = 50;

    $p_stmt = $conn->prepare("SELECT prdct_name, stock_quantity FROM tbl_products WHERE prdct_id = ?");
    if ($p_stmt) {
        $p_stmt->bind_param("i", $id);
        $p_stmt->execute();
        $p_result = $p_stmt->get_result();
        if ($p_result && $p_result->num_rows > 0) {
            $p_row = $p_result->fetch_assoc();
            $product_name = $p_row['prdct_name'];
            $available_stock = (int)($p_row['stock_quantity'] ?? 50);
        }
        $p_stmt->close();
    }

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
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Added to Cart',
            'message' => '"' . $product_name . '" has been added to your shopping cart.'
        ];
    }

    header('location: cart.php');
    exit();
}
?>