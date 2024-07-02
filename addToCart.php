<?php
session_start();

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Check if the item already exists in the cart
    if (isset($_SESSION['cart'][$id])) {
        // Get the current quantity of the item
        $currentQuantity = $_SESSION['cart'][$id]['quantity'];
        
        // Check if the quantity parameter is provided
        if (isset($_GET['quantity'])) {
            $additionalQuantity = $_GET['quantity'];
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
            $quantity = $_GET['quantity'];
        } else {
            $quantity = 1;
        }
        
        $_SESSION['cart'][$id] = array('quantity' => $quantity);
    }
    
    header('location: cart.php');
}
?>