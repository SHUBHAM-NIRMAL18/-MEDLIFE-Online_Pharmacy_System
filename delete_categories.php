<?php 
require_once 'config.php';
$pharmacy_id = require_admin_tenant();

if (!isset($_GET['cat_id']) || !is_numeric($_GET['cat_id'])) {
    header('Location: viewcat.php?msg=1');
    exit();
}

$id = (int)$_GET['cat_id'];
$conn = get_db_connection();

try {
    // Reset any child subcategories to root (parent_id = 0) within this tenant
    $reparent = $conn->prepare("UPDATE tbl_categories SET parent_id = 0 WHERE parent_id = ? AND pharmacy_id = ?");
    if ($reparent) {
        $reparent->bind_param("ii", $id, $pharmacy_id);
        $reparent->execute();
        $reparent->close();
    }

    $stmt = $conn->prepare("DELETE FROM tbl_categories WHERE cat_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $id, $pharmacy_id);
        $stmt->execute();
        if ($stmt->affected_rows === 1) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Category Deleted',
                'message' => 'Category has been removed from your store taxonomy.'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Category not found or does not belong to your pharmacy.'
            ];
        }
        $stmt->close();
    }
    header('Location: viewcat.php?action=1');
    exit();
} catch (Exception $e) {
    die('Database Error: ' . $e->getMessage());
}
?>