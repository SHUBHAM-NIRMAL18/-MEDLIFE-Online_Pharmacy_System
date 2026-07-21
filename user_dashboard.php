<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_status']) || !isset($_SESSION['user_id'])) {
    header('location:customer_login.php?msg=1');
    exit();
}

$uid = (int)$_SESSION['user_id'];
$conn = get_db_connection();

// Fetch current user details
$user_stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
$user_data = [];
if ($user_stmt) {
    $user_stmt->bind_param("i", $uid);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    if ($user_res && $user_res->num_rows > 0) {
        $user_data = $user_res->fetch_assoc();
    }
    $user_stmt->close();
}

$page_title = "My Dashboard";
$page_css = "css/dashboard.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    <div class="dashboard-container">
        
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="user-profile-header">
                <div class="user-avatar">
                    <?php echo substr($user_data['name'], 0, 1); ?>
                </div>
                <h3>Welcome, <?php echo htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="sidebar-nav-link active">
                    <i class="bx bx-package"></i> Order History
                </a>
                <a href="personal_info.php" class="sidebar-nav-link">
                    <i class="bx bx-user-circle"></i> Personal Info
                </a>
                <a href="change_password.php" class="sidebar-nav-link">
                    <i class="bx bx-shield-quarter"></i> Change Password
                </a>
                <a href="user_logout.php" class="sidebar-nav-link logout">
                    <i class="bx bx-log-out"></i> Logout Account
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <section class="dashboard-content">
            <div class="tab-pane active">
                <h2>Order History</h2>
                
                <?php
                $order_stmt = $conn->prepare("SELECT * FROM tbl_order WHERE user_id = ? ORDER BY order_id DESC");
                if ($order_stmt) {
                    $order_stmt->bind_param("i", $uid);
                    $order_stmt->execute();
                    $order_run = $order_stmt->get_result();
                    if ($order_run && $order_run->num_rows > 0):
                ?>
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Tracking No</th>
                                    <th>Date</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($items = $order_run->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: 500;"><?php echo htmlspecialchars($items['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($items['created_at'])); ?></td>
                                        <td style="font-weight: 600; color: var(--text-main);">रु. <?php echo number_format($items['total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            if ($items['status'] == 0) {
                                                echo "<span class='status-badge process'>Under Process</span>";
                                            } elseif ($items['status'] == 1) {
                                                echo "<span class='status-badge completed'>Completed</span>";
                                            } elseif ($items['status'] == 2) {
                                                echo "<span class='status-badge cancelled'>Cancelled</span>";
                                            }
                                            ?>
                                        </td>
                                         <td>
                                             <div style="display: flex; gap: 6px; align-items: center;">
                                                 <button type="button" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; color: #2563eb; border-color: rgba(37, 99, 235, 0.3); background: #eff6ff;" onclick="openOrderProductsModal(<?php echo $items['order_id']; ?>, '<?php echo htmlspecialchars(addslashes($items['tracking_order']), ENT_QUOTES, 'UTF-8'); ?>', '<?php echo date("M d, Y, g:i a", strtotime($items['created_at'])); ?>')">
                                                     <i class="bx bx-show"></i> Items
                                                 </button>
                                                 <?php if ($items['status'] == 1): ?>
                                                     <a href="order_receipt.php?id=<?php echo $items['order_id']; ?>" target="_blank" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; color: #059669; border-color: rgba(5, 150, 105, 0.3);">
                                                         <i class="bx bx-receipt"></i> Receipt
                                                     </a>
                                                 <?php endif; ?>
                                                 <a href="track_order.php?tracking=<?php echo urlencode($items['tracking_order']); ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 12px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;">
                                                     <i class="bx bx-map-pin"></i> Track
                                                 </a>
                                             </div>
                                         </td>
                                     </tr>
                                 <?php endwhile; ?>
                             </tbody>
                         </table>
                 <?php 
                     else: 
                 ?>
                         <div style="text-align: center; padding: 40px 0; color: var(--text-light);">
                             <i class="bx bx-receipt" style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                             <p>You haven't placed any pharmacy orders yet.</p>
                             <a href="index.php" class="btn btn-primary" style="margin-top: 16px;">Browse Medicines</a>
                         </div>
                 <?php 
                     endif;
                     $order_stmt->close();
                 }
                 ?>
             </div>
         </section>

    </div>
</main>

<!-- Customer Order Products Detail Modal Overlay -->
<div class="modal-overlay" id="orderProductsModal">
    <div class="confirm-modal-card" style="max-width: 600px; width: 92%; text-align: left; padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0;" id="modalOrderRef">
                    Order Items Details
                </h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;" id="modalOrderDate">
                    Placed Date
                </div>
            </div>
            <button type="button" onclick="closeOrderProductsModal()" style="border: none; background: #f1f5f9; width: 32px; height: 32px; border-radius: 50%; color: #64748b; font-size: 18px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                <i class="bx bx-x"></i>
            </button>
        </div>

        <div id="modalOrderItemsBody">
            <div style="text-align: center; padding: 30px 0; color: #64748b;">
                <i class="bx bx-loader-circle bx-spin" style="font-size: 32px; color: #059669;"></i>
                <p style="margin-top: 8px; font-size: 13px;">Loading order items...</p>
            </div>
        </div>

        <div style="margin-top: 20px; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 14px;">
            <button type="button" class="btn btn-outline" onclick="closeOrderProductsModal()" style="padding: 7px 18px; font-size: 13px;">Close</button>
        </div>
    </div>
</div>

<script>
function openOrderProductsModal(orderId, trackingRef, dateStr) {
    var modal = document.getElementById('orderProductsModal');
    var refEl = document.getElementById('modalOrderRef');
    var dateEl = document.getElementById('modalOrderDate');
    var bodyEl = document.getElementById('modalOrderItemsBody');

    if (refEl) refEl.innerHTML = '<i class="bx bx-package" style="color: #059669;"></i> Tracking: <span style="font-family: monospace; color: #059669;">' + trackingRef + '</span>';
    if (dateEl) dateEl.textContent = 'Placed on ' + dateStr;

    if (bodyEl) {
        bodyEl.innerHTML = '<div style="text-align: center; padding: 30px 0; color: #64748b;"><i class="bx bx-loader-circle bx-spin" style="font-size: 32px; color: #059669;"></i><p style="margin-top: 8px; font-size: 13px;">Loading items...</p></div>';
    }

    if (modal) modal.classList.add('show');

    fetch('get_order_details.php?order_id=' + orderId)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                var html = '<table style="width: 100%; border-collapse: collapse; margin-top: 8px;">';
                html += '<thead><tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">';
                html += '<th style="padding: 10px; font-size: 12px; color: #475569; text-align: left;">Product</th>';
                html += '<th style="padding: 10px; font-size: 12px; color: #475569; text-align: center; width: 60px;">Qty</th>';
                html += '<th style="padding: 10px; font-size: 12px; color: #475569; text-align: right; width: 100px;">Price</th>';
                html += '<th style="padding: 10px; font-size: 12px; color: #475569; text-align: right; width: 110px;">Subtotal</th>';
                html += '</tr></thead><tbody>';

                var subtotal = 0;
                data.items.forEach(function(item) {
                    var lineTotal = parseFloat(item.price) * parseInt(item.quantity);
                    subtotal += lineTotal;
                    var imgSrc = item.prdct_img ? 'medimg/' + item.prdct_img : 'medimg/default.png';

                    var itemName = item.prdct_display_name || item.catalog_name || item.prdct_name || 'Pharmacy Item';
                    html += '<tr style="border-bottom: 1px solid #f1f5f9;">';
                    html += '<td style="padding: 10px; display: flex; align-items: center; gap: 10px;">';
                    html += '<img src="' + imgSrc + '" style="width: 38px; height: 38px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 2px;">';
                    html += '<span style="font-weight: 600; font-size: 13px; color: #0f172a;">' + itemName + '</span>';
                    html += '</td>';
                    html += '<td style="padding: 10px; text-align: center; font-weight: 600; font-size: 13px;">' + item.quantity + '</td>';
                    html += '<td style="padding: 10px; text-align: right; font-size: 13px; color: #475569;">रु. ' + parseFloat(item.price).toFixed(2) + '</td>';
                    html += '<td style="padding: 10px; text-align: right; font-weight: 700; font-size: 13px; color: #0f172a;">रु. ' + lineTotal.toFixed(2) + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table>';

                html += '<div style="margin-top: 16px; background: #f8fafc; padding: 14px 16px; border-radius: 10px; display: flex; flex-direction: column; gap: 6px; border: 1px solid #f1f5f9;">';
                html += '<div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b;"><span>Items Subtotal</span><span>रु. ' + subtotal.toFixed(2) + '</span></div>';
                html += '<div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b;"><span>Delivery Charge</span><span>रु. 100.00</span></div>';
                html += '<div style="display: flex; justify-content: space-between; font-size: 15px; font-weight: 800; color: #059669; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px;"><span>Grand Total</span><span>रु. ' + parseFloat(data.order.total).toFixed(2) + '</span></div>';
                html += '</div>';

                if (bodyEl) bodyEl.innerHTML = html;
            } else {
                if (bodyEl) bodyEl.innerHTML = '<p style="color: #dc2626; text-align: center; padding: 20px;">Could not load items.</p>';
            }
        })
        .catch(err => {
            if (bodyEl) bodyEl.innerHTML = '<p style="color: #dc2626; text-align: center; padding: 20px;">Error loading order items.</p>';
        });
}

function closeOrderProductsModal() {
    var modal = document.getElementById('orderProductsModal');
    if (modal) modal.classList.remove('show');
}

document.getElementById('orderProductsModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrderProductsModal();
});
</script>

<?php include('footer.php'); ?>