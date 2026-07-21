<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$order_data = [];

$uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($uid > 0) {
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE user_id = ? ORDER BY order_id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $order_data = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

if (empty($order_data)) {
    $sql = "SELECT * FROM tbl_order ORDER BY order_id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $order_data = $result->fetch_assoc();
    } else {
        header("Location: index.php");
        exit();
    }
}

$page_title = "Order Placed Successfully";
$page_css = "css/checkout.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <div class="order-placed-card">
        <div class="order-placed-icon">
            <i class="bx bx-check-circle"></i>
        </div>
        <h2>Thank You!</h2>
        <p class="success-msg">Your pharmacy order has been placed successfully and is now under processing.</p>
        
        <div class="order-details-summary">
            <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Order Details</h3>
            
            <div class="detail-line">
                <strong>Tracking Reference</strong>
                <span style="font-family: monospace; font-weight: 600;"><?php echo htmlspecialchars($order_data['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="detail-line">
                <strong>Customer Name</strong>
                <span><?php echo htmlspecialchars($order_data['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="detail-line">
                <strong>Phone Number</strong>
                <span><?php echo htmlspecialchars($order_data['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="detail-line">
                <strong>Delivery Address</strong>
                <span><?php echo htmlspecialchars($order_data['address'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="detail-line">
                <strong>Payment Mode</strong>
                <span style="text-transform: uppercase; font-size: 12.5px;"><?php echo htmlspecialchars($order_data['payment'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            
            <div class="detail-line">
                <strong>Order Date & Time</strong>
                <span><?php echo date("F d, Y, g:i a", strtotime($order_data['created_at'])); ?></span>
            </div>
            
            <div class="detail-line total-line">
                <strong>Grand Total</strong>
                <span>रु. <?php echo number_format($order_data['total'], 2); ?></span>
            </div>
        </div>
        
        <div class="order-placed-actions">
            <a href="user_dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <a href="index.php" class="btn btn-outline">Continue Shopping</a>
        </div>
    </div>
    
</main>

<?php include('footer.php'); ?>