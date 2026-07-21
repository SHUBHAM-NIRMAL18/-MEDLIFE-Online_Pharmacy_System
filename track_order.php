<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tracking_no = '';
if (isset($_GET['tracking'])) {
    $tracking_no = trim($_GET['tracking']);
} elseif (isset($_POST['search_tracking'])) {
    $tracking_no = trim($_POST['search_tracking']);
}

$order_data = null;
$order_items = [];

if (!empty($tracking_no)) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE tracking_order = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tracking_no);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $order_data = $res->fetch_assoc();
            
            // Fetch order items
            $items_stmt = $conn->prepare("SELECT * FROM tbl_orderitems WHERE order_id = ?");
            if ($items_stmt) {
                $items_stmt->bind_param("i", $order_data['order_id']);
                $items_stmt->execute();
                $items_res = $items_stmt->get_result();
                if ($items_res) {
                    while ($item = $items_res->fetch_assoc()) {
                        $order_items[] = $item;
                    }
                }
                $items_stmt->close();
            }
        }
        $stmt->close();
    }
}

$page_title = !empty($tracking_no) ? "Tracking Order: " . htmlspecialchars($tracking_no, ENT_QUOTES, 'UTF-8') : "Track Your Order";
$page_css = "css/track.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">

    <!-- Tracking Input Card -->
    <div class="tracking-search-card">
        <h2><i class="bx bx-map-pin" style="color: var(--primary);"></i> Track Your Pharmacy Order</h2>
        <p>Enter your 11-digit order tracking number to see live delivery updates.</p>
        <form action="track_order.php" method="GET" class="tracking-input-group">
            <input type="text" class="form-control" name="tracking" placeholder="e.g. MED175829103" value="<?php echo htmlspecialchars($tracking_no, ENT_QUOTES, 'UTF-8'); ?>" required>
            <button type="submit" class="btn btn-primary">
                <i class="bx bx-search-alt"></i> Track
            </button>
        </form>
    </div>

    <?php if (!empty($tracking_no) && $order_data): ?>
        
        <?php 
        $status = (int)$order_data['status'];
        // Step calculations
        $step1_class = "completed"; // Placed
        $step2_class = $status >= 0 ? ($status == 0 ? "active" : "completed") : "pending"; // Processing
        $step3_class = $status == 1 ? "completed" : "pending"; // Out for delivery
        $step4_class = $status == 1 ? "completed" : "pending"; // Delivered
        
        $progress_width = "25%";
        $vert_progress = "33%";
        if ($status == 1) {
            $progress_width = "75%";
            $vert_progress = "100%";
        } elseif ($status == 2) {
            $progress_width = "0%";
            $vert_progress = "0%";
        }
        ?>

        <!-- Visual Timeline Card -->
        <div class="tracking-timeline-card">
            
            <div class="timeline-header">
                <div>
                    <div class="order-no">Tracking: <span><?php echo htmlspecialchars($order_data['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="order-date">Placed on <?php echo date("F d, Y, g:i a", strtotime($order_data['created_at'])); ?></div>
                </div>
                <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <?php if ($status == 1): ?>
                        <a href="order_receipt.php?id=<?php echo $order_data['order_id']; ?>" target="_blank" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; border-radius: 8px; color: #059669; border-color: rgba(5, 150, 105, 0.4);">
                            <i class="bx bx-receipt"></i> Print Tax Receipt
                        </a>
                    <?php endif; ?>
                    <?php if ($status == 0): ?>
                        <span class="status-badge process" style="font-size: 13px; padding: 6px 14px;"><i class="bx bx-loader-circle bx-spin"></i> Under Processing</span>
                    <?php elseif ($status == 1): ?>
                        <span class="status-badge completed" style="font-size: 13px; padding: 6px 14px;"><i class="bx bx-check-circle"></i> Successfully Delivered</span>
                    <?php elseif ($status == 2): ?>
                        <span class="status-badge cancelled" style="font-size: 13px; padding: 6px 14px;"><i class="bx bx-x-circle"></i> Order Cancelled</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($status == 2): ?>
                <div class="cancelled-alert-banner">
                    <i class="bx bx-error"></i>
                    <div>This order has been cancelled. If you have any questions or need a refund, please contact customer support.</div>
                </div>
            <?php else: ?>
                <!-- Stepper Bar -->
                <div class="timeline-stepper">
                    <div class="stepper-progress-line" style="width: <?php echo $progress_width; ?>; --vertical-progress-height: <?php echo $vert_progress; ?>;"></div>

                    <!-- Step 1 -->
                    <div class="step-item <?php echo $step1_class; ?>">
                        <div class="step-icon">
                            <i class="bx bx-file-blank"></i>
                        </div>
                        <div class="step-title">Order Placed</div>
                        <div class="step-desc">Invoice Received</div>
                    </div>

                    <!-- Step 2 -->
                    <div class="step-item <?php echo $step2_class; ?>">
                        <div class="step-icon">
                            <i class="bx bx-package"></i>
                        </div>
                        <div class="step-title">Processing</div>
                        <div class="step-desc">Pharmacist Verification</div>
                    </div>

                    <!-- Step 3 -->
                    <div class="step-item <?php echo $step3_class; ?>">
                        <div class="step-icon">
                            <i class="bx bx-cycling"></i>
                        </div>
                        <div class="step-title">Out for Delivery</div>
                        <div class="step-desc">With Express Courier</div>
                    </div>

                    <!-- Step 4 -->
                    <div class="step-item <?php echo $step4_class; ?>">
                        <div class="step-icon">
                            <i class="bx bx-check-double"></i>
                        </div>
                        <div class="step-title">Delivered</div>
                        <div class="step-desc">Handed Over</div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Order Information Summary Grid -->
        <div class="track-summary-grid">
            
            <!-- Items Table -->
            <div class="track-info-card">
                <h3><i class="bx bx-basket" style="color: var(--primary);"></i> Order Items</h3>
                <table class="track-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="text-align: center; font-weight: 600;"><?php echo $item['quantity']; ?></td>
                                <td style="text-align: right; font-weight: 600;">रु. <?php echo number_format($item['price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background-color: #f8fafc;">
                            <td colspan="2" style="text-align: right; font-weight: 700; color: #0f172a;">Total Amount</td>
                            <td style="text-align: right; font-weight: 800; color: var(--primary); font-size: 15px;">
                                रु. <?php echo number_format($order_data['total'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Delivery & Payment Info -->
            <div class="track-info-card">
                <h3><i class="bx bx-map" style="color: var(--primary);"></i> Shipping & Payment</h3>
                <div class="track-info-list">
                    <div class="track-info-item">
                        <strong>Recipient:</strong>
                        <span><?php echo htmlspecialchars($order_data['user_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="track-info-item">
                        <strong>Phone:</strong>
                        <span><?php echo htmlspecialchars($order_data['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="track-info-item">
                        <strong>Delivery Address:</strong>
                        <span><?php echo htmlspecialchars($order_data['address'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="track-info-item">
                        <strong>Payment Method:</strong>
                        <span style="text-transform: uppercase;"><?php echo htmlspecialchars($order_data['payment'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="track-info-item">
                        <strong>Prescription:</strong>
                        <span>
                            <?php if (!empty($order_data['prescription'])): ?>
                                <a href="<?php echo htmlspecialchars($order_data['prescription'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" style="color: var(--primary); text-decoration: underline;">
                                    <i class="bx bx-paperclip"></i> View Attached
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-light); font-weight: 400;">Not Required</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>

    <?php elseif (!empty($tracking_no) && !$order_data): ?>
        
        <div style="text-align: center; padding: 40px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; max-width: 650px; margin: 0 auto;">
            <i class="bx bx-error-circle" style="font-size: 48px; color: var(--danger); margin-bottom: 12px; display: block;"></i>
            <h3 style="font-size: 18px; color: #0f172a; margin-bottom: 6px;">No Order Found</h3>
            <p style="color: var(--text-muted); font-size: 14px;">We couldn't find an order matching tracking code <strong>"<?php echo htmlspecialchars($tracking_no, ENT_QUOTES, 'UTF-8'); ?>"</strong>. Please verify the code and try again.</p>
        </div>

    <?php endif; ?>

</main>

<?php include('footer.php'); ?>
