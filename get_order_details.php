<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$order_id = isset($_GET['order_id']) && is_numeric($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Order ID']);
    exit();
}

$conn = get_db_connection();

// Verify order belongs to user or admin
$is_admin = isset($_SESSION['admin_login']) ? 1 : 0;
$order_check = $conn->prepare("SELECT * FROM tbl_order WHERE order_id = ? AND (user_id = ? OR ? = 1)");
if ($order_check) {
    $order_check->bind_param("iii", $order_id, $user_id, $is_admin);
    $order_check->execute();
    $order_res = $order_check->get_result();
    
    if (!$order_res || $order_res->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Order not found']);
        exit();
    }

    $order_info = $order_res->fetch_assoc();
    $order_check->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
    exit();
}

// Fetch items with product image and catalog name fallback
$items_stmt = $conn->prepare("SELECT i.*, p.prdct_name AS catalog_name, p.prdct_img FROM tbl_orderitems i LEFT JOIN tbl_products p ON (i.prdct_id = p.prdct_id OR i.prdct_name = p.prdct_id) WHERE i.order_id = ?");
$items = [];
if ($items_stmt) {
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $res = $items_stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $name = '';
            // Check catalog name first
            if (!empty($row['catalog_name']) && $row['catalog_name'] !== '0' && !is_numeric($row['catalog_name'])) {
                $name = $row['catalog_name'];
            } elseif (!empty($row['prdct_name']) && $row['prdct_name'] !== '0' && !is_numeric($row['prdct_name'])) {
                $name = $row['prdct_name'];
            } else {
                // If prdct_id or prdct_name holds the numeric ID, look up in tbl_products
                $pid = (int)$row['prdct_id'];
                if ($pid === 0 && is_numeric($row['prdct_name']) && (int)$row['prdct_name'] > 0) {
                    $pid = (int)$row['prdct_name'];
                }
                if ($pid > 0) {
                    $lookup = $conn->query("SELECT prdct_name, prdct_img FROM tbl_products WHERE prdct_id = $pid");
                    if ($lookup && $lookup->num_rows > 0) {
                        $p_data = $lookup->fetch_assoc();
                        $name = $p_data['prdct_name'];
                        if (empty($row['prdct_img']) && !empty($p_data['prdct_img'])) {
                            $row['prdct_img'] = $p_data['prdct_img'];
                        }
                    }
                }
            }

            if (empty($name) || $name === '0') {
                $name = 'Medicine Item #' . ($row['prdct_id'] > 0 ? $row['prdct_id'] : $order_id);
            }

            $row['prdct_display_name'] = $name;
            $items[] = $row;
        }
    }
    $items_stmt->close();
}

echo json_encode([
    'status' => 'success',
    'order' => $order_info,
    'items' => $items
]);
exit();
