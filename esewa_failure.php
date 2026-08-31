<?php
/**
 * esewa_failure.php
 * Handles eSewa cancellation or failure
 */

require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id > 0) {
    $stmt_up = $conn->prepare("UPDATE tbl_order SET payment_status = 'Failed' WHERE order_id = ? AND payment_status = 'Pending'");
    if ($stmt_up) {
        $stmt_up->bind_param("i", $order_id);
        $stmt_up->execute();
        $stmt_up->close();
    }
}

$_SESSION['toast'] = [
    'type' => 'error',
    'title' => 'eSewa Payment Cancelled',
    'message' => 'The payment transaction was cancelled or could not be completed.'
];

$page_title = "Payment Failed - MedLife";
$page_css = "css/checkout.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <div class="order-placed-card" style="border-top: 4px solid var(--danger);">
        <div class="order-placed-icon" style="background: rgba(239, 68, 68, 0.1); color: var(--danger);">
            <i class="bx bx-x-circle"></i>
        </div>
        <h2>Payment Cancelled or Failed</h2>
        <p class="success-msg" style="color: var(--text-light);">We could not verify your eSewa transaction. No charges have been made to your wallet for unconfirmed items.</p>
        
        <div class="order-details-summary" style="text-align: center; padding: 20px;">
            <p style="font-size: 14px; color: var(--text-main); margin-bottom: 12px;">
                If your money was deducted, eSewa will automatically refund it, or you can retry with another payment mode.
            </p>
            <?php if ($order_id > 0): ?>
                <p style="font-size: 13px; color: var(--text-light);">
                    Order Reference: <strong>#<?php echo $order_id; ?></strong>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="order-placed-actions">
            <a href="checkout.php" class="btn btn-primary">Retry Checkout</a>
            <a href="cart.php" class="btn btn-outline">View Cart</a>
        </div>
    </div>
    
</main>

<?php include('footer.php'); ?>
