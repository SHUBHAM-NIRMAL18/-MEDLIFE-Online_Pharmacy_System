<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['cart']);

$_SESSION['toast'] = [
    'type' => 'info',
    'title' => 'Cart Cleared',
    'message' => 'All items have been cleared from your shopping cart.'
];

header('location:cart.php');
exit();
