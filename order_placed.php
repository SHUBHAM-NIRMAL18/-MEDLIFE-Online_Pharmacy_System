<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$order_data = [];

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $req_oid = (int)$_GET['order_id'];
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $req_oid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $order_data = $res->fetch_assoc();
        }
        $stmt->close();
    }
}

if (empty($order_data)) {
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

$page_title = "Order Placed Successfully - MedLife";
$page_css = "css/checkout.css";
include('header.php');

$pay_mode = strtolower($order_data['payment'] ?? 'cod');
$pay_status = $order_data['payment_status'] ?? 'Pending';
$txn_id = $order_data['transaction_id'] ?? '';
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <div class="order-placed-card">
        <div class="order-placed-icon">
            <i class="bx bx-check-circle"></i>
        </div>
        <h2>Thank You!</h2>
        <p class="success-msg">Your pharmacy order has been placed successfully and is now under processing.</p>
        
        <div class="order-details-summary">
            <h3 style="font-size: 16px; font-weight: 600; color: var(--text-main); margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                <span>Order Details</span>
                <span style="font-size: 12px; font-weight: 600; color: #64748b;">Order #<?php echo $order_data['order_id']; ?></span>
            </h3>
            
            <div class="detail-line">
                <strong>Tracking Reference</strong>
                <span style="font-family: monospace; font-weight: 700; color: var(--primary);"><?php echo htmlspecialchars($order_data['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                <strong>Payment Method</strong>
                <div>
                    <?php if ($pay_mode === 'esewa'): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #438f2f; padding: 4px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700; border: 1px solid rgba(96, 187, 70, 0.4); box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                            <img src="img/esewa_logo.png" alt="eSewa" style="height: 18px; max-width: 55px; object-fit: contain;"> eSewa Mobile Wallet
                        </span>
                    <?php elseif ($pay_mode === 'khalti'): ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: #ffffff; color: #5c2d91; padding: 4px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700; border: 1px solid rgba(92, 45, 145, 0.4); box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                            <img src="img/khalti_logo.png" alt="Khalti" style="height: 18px; max-width: 55px; object-fit: contain;"> Khalti Digital Wallet
                        </span>
                    <?php else: ?>
                        <span style="display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; color: #334155; padding: 4px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 700; border: 1px solid #e2e8f0;">
                            <i class="bx bx-money" style="color: #059669; font-size: 16px;"></i> Cash on Delivery (COD)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-line">
                <strong>Payment Status</strong>
                <div>
                    <?php if (strcasecmp($pay_status, 'Paid') === 0): ?>
                        <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="bx bx-check-shield"></i> Paid Online
                        </span>
                    <?php else: ?>
                        <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="bx bx-time"></i> Pending Settlement
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($txn_id)): ?>
                <div class="detail-line">
                    <strong>Transaction Reference</strong>
                    <span style="font-family: monospace; font-size: 12px; color: #475569; word-break: break-all;"><?php echo htmlspecialchars($txn_id, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="detail-line">
                <strong>Order Date & Time</strong>
                <span><?php echo date("F d, Y, g:i a", strtotime($order_data['created_at'])); ?></span>
            </div>
            
            <div class="detail-line total-line">
                <strong>Grand Total</strong>
                <span style="color: var(--primary);">रु. <?php echo number_format($order_data['total'], 2); ?></span>
            </div>
        </div>
        
        <div class="order-placed-actions" style="flex-wrap: wrap; gap: 10px; justify-content: center;">
            <a href="order_receipt.php?id=<?php echo $order_data['order_id']; ?>" target="_blank" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bx bx-printer"></i> Print Tax Receipt
            </a>
            <a href="track_order.php?tracking_order=<?php echo urlencode($order_data['tracking_order']); ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bx bx-map-pin"></i> Track Delivery
            </a>
            <a href="user_dashboard.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px;">
                <i class="bx bx-home-alt"></i> Dashboard
            </a>
        </div>
    </div>
    
</main>

<?php include('footer.php'); ?>