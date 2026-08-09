<?php 
require_once 'config.php';
$pharmacy_id = require_admin_tenant();

if (!isset($_GET['prdct_id']) || !is_numeric($_GET['prdct_id'])) {
    header('Location: view_products.php?msg=1');
    exit();
}

$id = (int)$_GET['prdct_id'];
$conn = get_db_connection();

try {
    $stmt = $conn->prepare("DELETE FROM tbl_products WHERE prdct_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $pharmacy_id);
        $stmt->execute();
        if ($stmt->affected_rows === 1) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Product Deleted',
                'message' => 'Product has been permanently removed from your catalog.'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Product not found or does not belong to your pharmacy.'
            ];
        }
        $stmt->close();
    }
    header('Location: view_products.php?action=1');
    exit();
} catch (Exception $e) {
    die('Database Error: ' . $e->getMessage());
}
?>