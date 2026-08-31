<?php
/**
 * esewa_success.php
 * Handles eSewa redirect callback on successful transaction
 */

require_once 'config.php';
require_once 'includes/PaymentGateways.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$encoded_data = $_GET['data'] ?? '';

if (empty($encoded_data)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Payment Error',
        'message' => 'No payment transaction data received from eSewa.'
    ];
    header("Location: index.php");
    exit();
}

$verify_res = PaymentGateways::verifyEsewaCallback($encoded_data);

if (!$verify_res['success']) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Payment Verification Failed',
        'message' => $verify_res['message'] ?? 'Could not verify eSewa payment signature.'
    ];
    header("Location: index.php");
    exit();
}

$transaction_uuid = $verify_res['transaction_uuid'];
$transaction_code = !empty($verify_res['transaction_code']) ? $verify_res['transaction_code'] : $transaction_uuid;
$raw_json = $verify_res['raw'] ?? json_encode($verify_res['data'] ?? []);

// Extract order_id from transaction_uuid pattern: MEDLIFE-{order_id}-{timestamp}
$order_id = 0;
if (preg_match('/^MEDLIFE-(\d+)-/i', $transaction_uuid, $m)) {
    $order_id = (int)$m[1];
}

// Fallback search in tbl_order by transaction_id if not found via regex
if ($order_id <= 0) {
    $stmt_find = $conn->prepare("SELECT order_id FROM tbl_order WHERE transaction_id = ? ORDER BY order_id DESC LIMIT 1");
    if ($stmt_find) {
        $stmt_find->bind_param("s", $transaction_uuid);
        $stmt_find->execute();
        $res = $stmt_find->get_result();
        if ($res && $res->num_rows > 0) {
            $order_id = (int)$res->fetch_assoc()['order_id'];
        }
        $stmt_find->close();
    }
}

if ($order_id > 0) {
    $stmt_up = $conn->prepare("UPDATE tbl_order SET payment_status = 'Paid', transaction_id = ?, payment_data = ? WHERE order_id = ?");
    if ($stmt_up) {
        $stmt_up->bind_param("ssi", $transaction_code, $raw_json, $order_id);
        $stmt_up->execute();
        $stmt_up->close();
    }

    // Fetch order tracking number
    $tracking_order = '';
    $stmt_track = $conn->prepare("SELECT tracking_order FROM tbl_order WHERE order_id = ?");
    if ($stmt_track) {
        $stmt_track->bind_param("i", $order_id);
        $stmt_track->execute();
        $res_tr = $stmt_track->get_result();
        if ($res_tr && $res_tr->num_rows > 0) {
            $tracking_order = $res_tr->fetch_assoc()['tracking_order'];
        }
        $stmt_track->close();
    }

    // Clear cart session
    unset($_SESSION['cart'], $_SESSION['cart_pharmacy_id']);

    $_SESSION['toast'] = [
        'type' => 'success',
        'title' => 'Payment Successful!',
        'message' => "Your eSewa payment of रु. " . number_format($verify_res['total_amount'] ?? 0, 2) . " was received. Ref: $transaction_code"
    ];

    header("Location: order_placed.php?order_id=" . $order_id);
    exit();
} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Order Not Found',
        'message' => 'Payment received, but matching MedLife order could not be located. Please contact support.'
    ];
    header("Location: index.php");
    exit();
}
