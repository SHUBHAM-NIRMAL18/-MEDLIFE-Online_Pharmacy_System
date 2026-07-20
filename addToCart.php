<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Fetch product name from database
    $conn = get_db_connection();
    $product_name = "Product";
    $p_stmt = $conn->prepare("SELECT prdct_name FROM tbl_products WHERE prdct_id = ?");
    if ($p_stmt) {
        $p_stmt->bind_param("i", $id);
        $p_stmt->execute();
        $p_result = $p_stmt->get_result();
        if ($p_result && $p_result->num_rows > 0) {
            $p_row = $p_result->fetch_assoc();
            $product_name = $p_row['prdct_name'];
        }
        $p_stmt->close();
    }

    // Set toast notification session variables
    $_SESSION['toast'] = [
        'type' => 'success',
        'title' => 'Added to Cart',
        'message' => '"' . $product_name . '" has been added to your shopping cart.'
    ];

    // Check if the item already exists in the cart
    if (isset($_SESSION['cart'][$id])) {
        // Get the current quantity of the item
        $currentQuantity = $_SESSION['cart'][$id]['quantity'];
        
        // Check if the quantity parameter is provided
        if (isset($_GET['quantity'])) {
            $additionalQuantity = (int)$_GET['quantity'];
        } else {
            $additionalQuantity = 1;
        }
        
        // Update the quantity by adding the additional quantity
        $newQuantity = $currentQuantity + $additionalQuantity;
        
        // Update the cart with the new quantity
        $_SESSION['cart'][$id]['quantity'] = $newQuantity;
    } else {
        // Item does not exist in the cart, add it with the provided quantity
        if (isset($_GET['quantity'])) {
            $quantity = (int)$_GET['quantity'];
        } else {
            $quantity = 1;
        }
        
        $_SESSION['cart'][$id] = array('quantity' => $quantity);
    }
    
    header('location: cart.php');
    exit();
}
?>