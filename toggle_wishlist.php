<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    unset($_SESSION['wishlist']);
    header('location:wishlist.php');
    exit();
}

header('Content-Type: application/json');

if (!isset($_SESSION['wishlist'])) {
    $_SESSION['wishlist'] = [];
}

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$action = 'removed';

if ($id > 0) {
    if (isset($_SESSION['wishlist'][$id])) {
        unset($_SESSION['wishlist'][$id]);
        $action = 'removed';
    } else {
        $_SESSION['wishlist'][$id] = true;
        $action = 'added';
    }
}

echo json_encode([
    'status' => 'success',
    'action' => $action,
    'count' => count($_SESSION['wishlist'])
]);
exit();
