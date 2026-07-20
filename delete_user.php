<?php 
require_once 'config.php';
$conn = get_db_connection();

if (isset($_GET['admin_id']) && is_numeric($_GET['admin_id'])) {
    $admin_id = (int)$_GET['admin_id'];
    $stmt = $conn->prepare("DELETE FROM tbl_admin WHERE admin_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: view_user.php?tab=admin&action=1');
    exit();
} elseif (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $stmt = $conn->prepare("DELETE FROM tbl_user WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: view_user.php?tab=customer&action=1');
    exit();
} else {
    header('Location: view_user.php?msg=1');
    exit();
}
?>