<?php
/**
 * khalti_callback.php
 * Handles Khalti ePayment v2 return redirect, verifies with Lookup API, and finalizes order.
 */

require_once 'config.php';
require_once 'includes/PaymentGateways.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

$pidx = $_GET['pidx'] ?? '';
$url_status = $_GET['status'] ?? '';
$purchase_order_id = $_GET['purchase_order_id'] ?? '';

if (empty($pidx)) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Khalti Error',
        'message' => 'No payment transaction identifier (pidx) received from Khalti.'
    ];
    header("Location: index.php");
    exit();
}

// 1. Locate Order in database
$order_id = 0;
if (!empty($purchase_order_id) && preg_match('/^order_(\d+)$/i', $purchase_order_id, $m)) {
    $order_id = (int)$m[1];
}

if ($order_id <= 0) {
    $stmt_find = $conn->prepare("SELECT order_id FROM tbl_order WHERE transaction_id = ? ORDER BY order_id DESC LIMIT 1");
    if ($stmt_find) {
        $stmt_find->bind_param("s", $pidx);
        $stmt_find->execute();
        $res = $stmt_find->get_result();
        if ($res && $res->num_rows > 0) {
            $order_id = (int)$res->fetch_assoc()['order_id'];
        }
        $stmt_find->close();
    }
}

// 2. Perform Server-to-Server Lookup Verification with Khalti API
$lookup_res = PaymentGateways::lookupKhaltiPayment($pidx);

if ($lookup_res['success'] && strcasecmp($lookup_res['status'], 'Completed') === 0) {
    $final_txn_id = !empty($lookup_res['transaction_id']) ? $lookup_res['transaction_id'] : $pidx;
    $raw_json = $lookup_res['raw'] ?? json_encode($lookup_res['data'] ?? []);

    if ($order_id > 0) {
        $stmt_up = $conn->prepare("UPDATE tbl_order SET payment_status = 'Paid', transaction_id = ?, payment_data = ? WHERE order_id = ?");
        if ($stmt_up) {
            $stmt_up->bind_param("ssi", $final_txn_id, $raw_json, $order_id);
            $stmt_up->execute();
            $stmt_up->close();
        }
    }

    // Clear cart session
    unset($_SESSION['cart'], $_SESSION['cart_pharmacy_id']);

    $_SESSION['toast'] = [
        'type' => 'success',
        'title' => 'Khalti Payment Received!',
        'message' => 'Your payment was confirmed successfully via Khalti. Transaction ID: ' . $final_txn_id
    ];

    header("Location: order_placed.php" . ($order_id > 0 ? "?order_id=$order_id" : ""));
    exit();
} else {
    // Payment was not completed or failed verification
    $fail_reason = $lookup_res['status'] ?? ($url_status ? $url_status : 'Payment Incomplete');

    if ($order_id > 0) {
        $stmt_fail = $conn->prepare("UPDATE tbl_order SET payment_status = 'Failed' WHERE order_id = ? AND payment_status = 'Pending'");
        if ($stmt_fail) {
            $stmt_fail->bind_param("i", $order_id);
            $stmt_fail->execute();
            $stmt_fail->close();
        }
    }

    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Khalti Payment Incomplete',
        'message' => 'Status: ' . htmlspecialchars($fail_reason)
    ];

    $page_title = "Khalti Payment Incomplete - MedLife";
    $page_css = "css/checkout.css";
    include('header.php');
    ?>

    <main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
        
        <div class="order-placed-card" style="border-top: 4px solid #5c2d91;">
            <div class="order-placed-icon" style="background: rgba(92, 45, 145, 0.1); color: #5c2d91;">
                <i class="bx bx-error-circle"></i>
            </div>
            <h2>Khalti Payment <?php echo htmlspecialchars($fail_reason); ?></h2>
            <p class="success-msg" style="color: var(--text-light);">We could not verify completion of your Khalti transaction (Reference: <code style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($pidx); ?></code>).</p>
            
            <div class="order-details-summary" style="text-align: center; padding: 20px;">
                <p style="font-size: 14px; color: var(--text-main); margin-bottom: 12px;">
                    If your Khalti balance was debited, it will be automatically reversed, or you can retry payment now.
                </p>
                <?php if ($order_id > 0): ?>
                    <p style="font-size: 13px; color: var(--text-light);">
                        Order ID: <strong>#<?php echo $order_id; ?></strong>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="order-placed-actions">
                <a href="checkout.php" class="btn btn-primary" style="background: #5c2d91; border-color: #5c2d91;">Try Again</a>
                <a href="cart.php" class="btn btn-outline">View Cart</a>
            </div>
        </div>
        
    </main>

    <?php 
    include('footer.php');
    exit();
}
