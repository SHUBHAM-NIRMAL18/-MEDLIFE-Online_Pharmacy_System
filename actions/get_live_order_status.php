<?php
/**
 * actions/get_live_order_status.php
 * Real-time order status and fulfillment synchronization endpoint
 */

require_once __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

$conn = get_db_connection();

// ----------------------------------------------------
// 1. Order Tracking Mode (?tracking=medlifeXXXX)
// ----------------------------------------------------
if (isset($_GET['tracking']) && !empty(trim($_GET['tracking']))) {
    $tracking_no = trim($_GET['tracking']);

    $stmt = $conn->prepare("SELECT o.*, d.name AS driver_name, d.phone AS driver_phone, d.vehicle_type, d.vehicle_number, p.name AS pharmacy_name 
                            FROM tbl_order o 
                            LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
                            LEFT JOIN tbl_pharmacies p ON o.pharmacy_id = p.pharmacy_id 
                            WHERE o.tracking_order = ?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }

    $stmt->bind_param("s", $tracking_no);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $order = $res->fetch_assoc();
        $status = (int)$order['status'];

        $status_names = [
            0 => 'Under Process & Verification',
            3 => 'Packed & Ready for Pickup',
            4 => 'Out for Delivery (Rider Dispatched)',
            1 => 'Delivered & Completed',
            2 => 'Order Cancelled'
        ];

        // Step index for 5-step timeline (0=Placed, 1=Verified, 2=Packed, 3=Out for Delivery, 4=Delivered)
        $step_index = 0;
        if ($status === 0) $step_index = 1;
        elseif ($status === 3) $step_index = 2;
        elseif ($status === 4) $step_index = 3;
        elseif ($status === 1) $step_index = 4;
        elseif ($status === 2) $step_index = -1; // Cancelled

        echo json_encode([
            'success' => true,
            'mode' => 'tracking',
            'order_id' => (int)$order['order_id'],
            'tracking_order' => $order['tracking_order'],
            'status' => $status,
            'status_label' => $status_names[$status] ?? 'Processing',
            'step_index' => $step_index,
            'is_delivered' => ($status === 1),
            'is_cancelled' => ($status === 2),
            'delivered_at' => $order['delivered_at'] ? date('M d, Y h:i A', strtotime($order['delivered_at'])) : null,
            'driver' => !empty($order['driver_name']) ? [
                'name' => $order['driver_name'],
                'phone' => $order['driver_phone'],
                'vehicle_type' => $order['vehicle_type'],
                'vehicle_number' => $order['vehicle_number']
            ] : null,
            'payment' => [
                'mode' => $order['payment'],
                'status' => $order['payment_status'] ?? 'Pending',
                'transaction_id' => $order['transaction_id'] ?? null
            ],
            'timestamp' => time()
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Tracking order not found']);
        exit();
    }
}

// ----------------------------------------------------
// 2. Customer Dashboard Mode (Logged-in Customer)
// ----------------------------------------------------
if (isset($_SESSION['login_status']) && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT o.order_id, o.tracking_order, o.status, o.payment, o.payment_status, o.total, o.created_at, o.delivered_at, d.name AS driver_name, d.phone AS driver_phone, p.name AS pharmacy_name 
                            FROM tbl_order o 
                            LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
                            LEFT JOIN tbl_pharmacies p ON o.pharmacy_id = p.pharmacy_id 
                            WHERE o.user_id = ? 
                            ORDER BY o.order_id DESC");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database query error']);
        exit();
    }

    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    $orders = [];
    while ($row = $res->fetch_assoc()) {
        $st = (int)$row['status'];
        $pm = strtolower($row['payment'] ?? 'cod');
        $pst = $row['payment_status'] ?? 'Pending';

        // Format status badge HTML and label
        $badge_class = 'status-badge process';
        $badge_text = 'Under Process';
        $badge_icon = 'bx bx-loader-alt bx-spin';

        if ($st === 3) {
            $badge_class = 'status-badge ready';
            $badge_text = 'Ready for Pickup';
            $badge_icon = 'bx bx-package';
        } elseif ($st === 4) {
            $badge_class = 'status-badge out-delivery';
            $badge_text = 'Out for Delivery';
            $badge_icon = 'bx bx-cycling';
        } elseif ($st === 1) {
            $badge_class = 'status-badge completed';
            $badge_text = 'Delivered';
            $badge_icon = 'bx bx-check-circle';
        } elseif ($st === 2) {
            $badge_class = 'status-badge cancelled';
            $badge_text = 'Cancelled';
            $badge_icon = 'bx bx-x-circle';
        }

        $badge_html = "<span class='{$badge_class}'><i class='{$badge_icon}'></i> {$badge_text}</span>";

        $orders[] = [
            'order_id' => (int)$row['order_id'],
            'tracking_order' => $row['tracking_order'],
            'status' => $st,
            'status_label' => $badge_text,
            'badge_html' => $badge_html,
            'can_view_receipt' => ($st === 1),
            'receipt_url' => 'order_receipt.php?id=' . $row['order_id'],
            'driver_name' => $row['driver_name'],
            'driver_phone' => $row['driver_phone'],
            'payment_status' => $pst,
            'total' => number_format($row['total'], 2)
        ];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'mode' => 'dashboard',
        'orders' => $orders,
        'count' => count($orders),
        'timestamp' => time()
    ]);
    exit();
}

echo json_encode([
    'success' => false,
    'message' => 'Unauthorized request. No user session or tracking number provided.'
]);
