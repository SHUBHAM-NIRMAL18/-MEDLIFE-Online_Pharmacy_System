<?php 
require_once 'config.php';
$pharmacy_id = require_admin_tenant();
$conn = get_db_connection();

if (isset($_GET['admin_id']) && is_numeric($_GET['admin_id'])) {
    $admin_id = (int)$_GET['admin_id'];

    if ($admin_id === (int)$_SESSION['admin_id']) {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Action Blocked',
            'message' => 'You cannot delete your own active administrator account.'
        ];
        header('Location: view_user.php?tab=admin');
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM tbl_admin WHERE admin_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $admin_id, $pharmacy_id);
        $stmt->execute();
        if ($stmt->affected_rows === 1) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Account Deleted',
                'message' => 'Staff administrator account removed successfully.'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Account not found or does not belong to your pharmacy.'
            ];
        }
        $stmt->close();
    }
    header('Location: view_user.php?tab=admin&action=1');
    exit();
} elseif (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
    $stmt = $conn->prepare("DELETE FROM tbl_user WHERE user_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $user_id, $pharmacy_id);
        $stmt->execute();
        if ($stmt->affected_rows === 1) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Customer Deleted',
                'message' => 'Customer record removed successfully.'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Customer record not found or belongs to another pharmacy.'
            ];
        }
        $stmt->close();
    }
    header('Location: view_user.php?tab=customer&action=1');
    exit();
} else {
    header('Location: view_user.php?msg=1');
    exit();
}
?>