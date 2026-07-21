<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset admin sessions
unset($_SESSION['admin_login']);
unset($_SESSION['admin_id']);
unset($_SESSION['admin_email']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);

// Clear cookies if any
setcookie('emailcookie', '', time() - 3600);
setcookie('email', '', time() - 3600);
setcookie('name', '', time() - 3600);
setcookie('admin_id', '', time() - 3600);

$_SESSION['toast'] = [
    'type' => 'info',
    'title' => 'Admin Session Ended',
    'message' => 'You have logged out of the admin panel successfully.'
];

header('location:admin_login.php');
exit();
?>