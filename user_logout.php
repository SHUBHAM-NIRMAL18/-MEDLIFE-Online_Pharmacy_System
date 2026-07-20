<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset user sessions
unset($_SESSION['email']);
unset($_SESSION['name']);
unset($_SESSION['login_status']);
unset($_SESSION['user_id']);

// Clear cookies
setcookie('emailcookie', null, time() - 3600);
setcookie('email', null, time() - 3600);
setcookie('name', null, time() - 3600);
setcookie('user_id', null, time() - 3600);

$_SESSION['toast'] = [
    'type' => 'info',
    'title' => 'Logged Out',
    'message' => 'You have logged out of your account successfully.'
];

header('location:customer_login.php');
exit();
?>