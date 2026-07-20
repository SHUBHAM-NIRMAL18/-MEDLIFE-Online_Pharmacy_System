<?php 
require_once 'config.php';
include_once('dashboard.php');

$conn = get_db_connection();

// Handle order deletion
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    
    // Delete order items first (foreign key), then the order
    $conn->query("DELETE FROM tbl_orderitems WHERE order_id = $delete_id");
    $conn->query("DELETE FROM tbl_order WHERE order_id = $delete_id");
    
    echo "<script>window.location.href = 'admin_order.php';</script>";
    exit();
}

$orders = $conn->query("SELECT * FROM tbl_order ORDER BY order_id DESC");
?>

  <div class="admin-page-wrapper">

    <div class="admin-page-header">
      <h1>Order Management</h1>
      <p>View and manage all customer pharmacy orders.</p>
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
            <th>Phone</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($orders && $orders->num_rows > 0): ?>
            <?php while ($item = $orders->fetch_assoc()): ?>
              <tr>
                <td>#<?php echo $item['order_id']; ?></td>
                <td style="font-family: monospace; font-weight: 500;"><?php echo htmlspecialchars($item['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($item['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo date("M d, Y", strtotime($item['created_at'])); ?></td>
                <td style="font-weight: 600;">रु. <?php echo number_format($item['total'], 2); ?></td>
                <td>
                  <?php
                  if ($item['status'] == 0) {
                    echo '<span class="admin-badge process">Under Process</span>';
                  } elseif ($item['status'] == 1) {
                    echo '<span class="admin-badge completed">Completed</span>';
                  } elseif ($item['status'] == 2) {
                    echo '<span class="admin-badge cancelled">Cancelled</span>';
                  }
                  ?>
                </td>
                <td>
                  <div style="display: flex; gap: 6px;">
                    <a href="view_order.php?order_id=<?php echo $item['order_id']; ?>" class="admin-btn view">
                      <i class="bx bx-show"></i> View
                    </a>
                    <button class="admin-btn danger-btn" onclick="confirmDeleteOrder(<?php echo $item['order_id']; ?>, '<?php echo htmlspecialchars($item['tracking_order'], ENT_QUOTES, 'UTF-8'); ?>')">
                      <i class="bx bx-trash"></i> Delete
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