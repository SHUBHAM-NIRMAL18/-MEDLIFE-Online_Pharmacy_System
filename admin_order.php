<?php 
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_admin_pharmacy_id = require_admin_tenant();
$conn = get_db_connection();
$pharmacy_id = $active_admin_pharmacy_id;

// Handle order deletion
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Verify that the order strictly belongs to this pharmacy tenant before deleting items
    $chk_stmt = $conn->prepare("SELECT order_id FROM tbl_order WHERE order_id = ? AND pharmacy_id = ?");
    if ($chk_stmt) {
        $chk_stmt->bind_param("ii", $delete_id, $pharmacy_id);
        $chk_stmt->execute();
        $chk_res = $chk_stmt->get_result();
        if ($chk_res && $chk_res->num_rows > 0) {
            $conn->query("DELETE FROM tbl_orderitems WHERE order_id = $delete_id");
            $conn->query("DELETE FROM tbl_order WHERE order_id = $delete_id AND pharmacy_id = $pharmacy_id");
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Order Deleted',
                'message' => "Order #$delete_id has been removed successfully."
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Delete Failed',
                'message' => 'Order not found or does not belong to your store.'
            ];
        }
        $chk_stmt->close();
    }
    header('Location: admin_order.php');
    exit();
}

// Include Dashboard Layout Shell after redirects
include_once('dashboard.php');

$orders = $conn->query("SELECT o.*, d.name AS driver_name, d.phone AS driver_phone, d.vehicle_type, d.vehicle_number 
                       FROM tbl_order o 
                       LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
                       WHERE o.pharmacy_id = $pharmacy_id 
                       ORDER BY o.order_id DESC");
?>

  <div class="admin-page-wrapper">

    <div class="admin-page-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
      <div>
        <h1>Order Dispatch & Delivery Management</h1>
        <p>Monitor online orders, assign fleet delivery couriers, and track live status.</p>
      </div>
      <a href="driver_management.php" class="admin-btn primary" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
        <i class="bx bx-cycling"></i> Manage Delivery Fleet
      </a>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <h3>All Orders (<?php echo $orders ? $orders->num_rows : 0; ?>)</h3>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Tracking No</th>
            <th>Customer</th>
            <th>Delivery Courier</th>
            <th>Date</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($orders && $orders->num_rows > 0): ?>
            <?php while ($item = $orders->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo $item['order_id']; ?></td>
                <td style="font-family: monospace; font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($item['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td>
                  <div style="font-weight: 600; color: #0f172a;"><?php echo htmlspecialchars($item['user_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <small style="color: #64748b;"><?php echo htmlspecialchars($item['phone'], ENT_QUOTES, 'UTF-8'); ?></small>
                </td>
                <td>
                  <?php if (!empty($item['driver_name'])): ?>
                    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 4px;">
                      <i class='bx bx-cycling' style="color: #059669;"></i> <?php echo htmlspecialchars($item['driver_name']); ?>
                    </div>
                    <small style="color: #64748b; font-size: 11px;"><?php echo htmlspecialchars($item['vehicle_number'] ?? ''); ?></small>
                  <?php else: ?>
                    <span style="color: #94a3b8; font-size: 12px;">Unassigned</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date("M d, Y", strtotime($item['created_at'])); ?></td>
                <td style="font-weight: 700; color: #059669;">रु. <?php echo number_format($item['total'], 2); ?></td>
                <td>
                  <?php 
                  $pm = strtolower($item['payment'] ?? 'cod');
                  $pst = $item['payment_status'] ?? 'Pending';
                  if ($pm === 'esewa') {
                      echo '<span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: #438f2f; font-size: 11.5px;"><strong style="color: #60bb46; font-size: 13px;">e</strong> eSewa ' . (strcasecmp($pst, 'Paid') === 0 ? '<i class="bx bx-check-circle" style="color: #059669;" title="Paid Online"></i>' : '<span style="color: #b45309; font-size: 10px;">(' . $pst . ')</span>') . '</span>';
                  } elseif ($pm === 'khalti') {
                      echo '<span style="display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: #5c2d91; font-size: 11.5px;"><strong style="color: #5c2d91; font-size: 13px;">K</strong> Khalti ' . (strcasecmp($pst, 'Paid') === 0 ? '<i class="bx bx-check-circle" style="color: #059669;" title="Paid Online"></i>' : '<span style="color: #b45309; font-size: 10px;">(' . $pst . ')</span>') . '</span>';
                  } else {
                      echo '<span style="color: #64748b; font-size: 12px; font-weight: 600;"><i class="bx bx-money"></i> COD</span>';
                  }
                  ?>
                </td>
                <td>
                  <?php
                  if ($item['status'] == 0) {
                    echo '<span class="admin-badge process" style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3);"><i class="bx bx-loader-alt bx-spin"></i> Processing</span>';
                  } elseif ($item['status'] == 3) {
                    echo '<span class="admin-badge" style="background: rgba(59, 130, 246, 0.12); color: #2563eb; border: 1px solid rgba(59, 130, 246, 0.3);"><i class="bx bx-package"></i> Ready for Pickup</span>';
                  } elseif ($item['status'] == 4) {
                    echo '<span class="admin-badge" style="background: rgba(99, 102, 241, 0.12); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);"><i class="bx bx-cycling"></i> Out for Delivery</span>';
                  } elseif ($item['status'] == 1) {
                    echo '<span class="admin-badge completed"><i class="bx bx-check-circle"></i> Delivered</span>';
                  } elseif ($item['status'] == 2) {
                    echo '<span class="admin-badge cancelled"><i class="bx bx-x-circle"></i> Cancelled</span>';
                  }
                  ?>
                </td>
                <td>
                  <div style="display: flex; gap: 6px; align-items: center;">
                    <a href="admin_payment_slip.php?order_id=<?php echo $item['order_id']; ?>" target="_blank" class="admin-btn outline" style="padding: 4px 8px; font-size: 11.5px; height: auto;" title="View Payment Slip">
                      <i class="bx bx-receipt"></i> Slip
                    </a>
                    <a href="view_order.php?order_id=<?php echo $item['order_id']; ?>" class="admin-btn view">
                      <i class="bx bx-show"></i> View
                    </a>
                    <button class="admin-btn danger-btn" onclick="confirmDeleteOrder(<?php echo $item['order_id']; ?>, '<?php echo htmlspecialchars($item['tracking_order'], ENT_QUOTES, 'UTF-8'); ?>')">
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 40px; color: var(--admin-text-light);">
                <i class="bx bx-receipt" style="font-size: 36px; display: block; margin-bottom: 8px;"></i>
                No orders found.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Delete Order Confirmation Modal -->
  <div class="admin-modal-overlay" id="deleteOrderModal">
    <div class="admin-modal-card">
      <div class="admin-modal-icon danger">
        <i class="bx bx-error-circle"></i>
      </div>
      <h4>Delete Order?</h4>
      <p>Are you sure you want to permanently delete order <strong id="deleteOrderTracking"></strong>? This action cannot be undone.</p>
      <div class="admin-modal-actions">
        <button class="admin-btn outline" onclick="closeDeleteModal()">Cancel</button>
        <a href="#" id="deleteOrderLink" class="admin-btn danger-btn">Delete</a>
      </div>
    </div>
  </div>

  <script>
    function confirmDeleteOrder(orderId, trackingNo) {
      document.getElementById('deleteOrderTracking').textContent = trackingNo;
      document.getElementById('deleteOrderLink').href = 'admin_order.php?delete_id=' + orderId;
      document.getElementById('deleteOrderModal').classList.add('show');
    }

    function closeDeleteModal() {
      document.getElementById('deleteOrderModal').classList.remove('show');
    }

    // Close on backdrop click
    document.getElementById('deleteOrderModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });
  </script>

  </main>
  </body>
</html>