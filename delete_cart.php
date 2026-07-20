<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Fetch product name for personalized toast
    $conn = get_db_connection();
    $product_name = "Product";
    $stmt = $conn->prepare("SELECT prdct_name FROM tbl_products WHERE prdct_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $product_name = $row['prdct_name'];
        }
        $stmt->close();
    }
    
    unset($_SESSION['cart'][$id]);
    
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Removed from Cart',
        'message' => '"' . $product_name . '" has been removed from your shopping cart.'
    ];
}

header('location:cart.php');
exit();
?>