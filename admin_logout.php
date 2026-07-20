<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset admin sessions
unset($_SESSION['email']);
unset($_SESSION['name']);
unset($_SESSION['login_status']);
unset($_SESSION['admin_id']);

// Clear cookies
setcookie('emailcookie', null, time() - 3600);
setcookie('email', null, time() - 3600);
setcookie('name', null, time() - 3600);
setcookie('admin_id', null, time() - 3600);

$_SESSION['toast'] = [
    'type' => 'info',
    'title' => 'Admin Session Ended',
    'message' => 'You have logged out of the admin panel successfully.'
];

header('location:admin_login.php');
exit();
?>