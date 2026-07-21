<?php 
require_once 'config.php';
include_once('dashboard.php');

$conn = get_db_connection();

// Handle status update with confirmation
if (isset($_POST['btnStatus'])) {
    $odr_id = (int)$_POST['order_id'];
    $order_status = (int)$_POST['order_status'];

    $stmt = $conn->prepare("UPDATE tbl_order SET status = ? WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $order_status, $odr_id);
        $stmt->execute();
        $stmt->close();
    }

    echo "<script>window.location.href = 'view_order.php?order_id=" . $odr_id . "&updated=1';</script>";
    exit();
}

// Fetch order data
$data = null;
$order_id = 0;
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

if (!$data) {
    echo "<script>window.location.href = 'admin_order.php';</script>";
    exit();
}

// Fetch order items
$order_items = $conn->query("SELECT * FROM tbl_orderitems WHERE order_id = $order_id");
?>

  <div class="admin-page-wrapper">

    <div class="admin-page-header" style="display: flex; align-items: center; justify-content: space-between;">
      <div>
        <h1>Order #<?php echo $data['order_id']; ?></h1>
        <p>Tracking: <strong style="font-family: monospace;"><?php echo htmlspecialchars($data['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></strong> &middot; Placed on <?php echo date("F d, Y, g:i a", strtotime($data['created_at'])); ?></p>
      </div>
      <div style="display: flex; gap: 10px; align-items: center;">
        <a href="order_receipt.php?id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn primary" style="height: 36px; display: inline-flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
          <i class="bx bx-printer"></i> Print Tax Receipt
        </a>
        <a href="admin_order.php" class="admin-btn outline" style="height: 36px; display: inline-flex; align-items: center; gap: 4px;">
          <i class="bx bx-arrow-back"></i> Back to Orders
        </a>
      </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
      <div style="padding: 12px 18px; background-color: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; color: var(--admin-success); font-size: 13.5px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-check-circle" style="font-size: 18px;"></i> Order status has been updated successfully.
      </div>
    <?php endif; ?>

    <div class="order-detail-grid">

      <!-- Customer Details Card -->
      <div class="admin-card">
        <h3><i class="bx bx-user" style="font-size: 16px;"></i> Customer Details</h3>

        <div class="admin-form-group">
          <label>Customer Name</label>
          <input type="text" class="admin-form-control" value="<?php echo htmlspecialchars($data['user_name'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
        </div>

        <div class="admin-form-group">
          <label>Phone</label>
          <input type="text" class="admin-form-control" value="<?php echo htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
        </div>

        <div class="admin-form-group">
          <label>Delivery Address</label>
          <input type="text" class="admin-form-control" value="<?php echo htmlspecialchars($data['address'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
        </div>

        <div class="admin-form-group">
          <label>Payment Method</label>
          <input type="text" class="admin-form-control" value="<?php echo strtoupper(htmlspecialchars($data['payment'], ENT_QUOTES, 'UTF-8')); ?>" readonly>
        </div>

        <?php if (!empty($data['prescription'])): ?>
        <div class="admin-form-group">
          <label>Prescription</label>
          <a href="<?php echo htmlspecialchars($data['prescription'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="admin-btn view" style="display: inline-flex; gap: 4px; margin-top: 4px;">
            <i class="bx bx-file"></i> View File
          </a>
        </div>
        <?php endif; ?>

        <!-- Update Status Section -->
        <form action="" method="POST" id="statusForm">
          <input type="hidden" name="order_id" value="<?php echo $data['order_id']; ?>">
          <div class="order-status-form">
            <select class="admin-select" name="order_status" id="orderStatusSelect">
              <option value="0" <?php echo $data['status'] == 0 ? "selected" : ""; ?>>Under Process</option>
              <option value="1" <?php echo $data['status'] == 1 ? "selected" : ""; ?>>Completed</option>
              <option value="2" <?php echo $data['status'] == 2 ? "selected" : ""; ?>>Cancelled</option>
            </select>
            <button type="button" class="admin-btn primary" onclick="confirmStatusUpdate()">
              <i class="bx bx-refresh"></i> Update
            </button>
          </div>
        </form>
      </div>

      <!-- Order Summary Card -->
      <div class="admin-card">
        <h3><i class="bx bx-receipt" style="font-size: 16px;"></i> Order Summary</h3>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Unit Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            if ($order_items && $order_items->num_rows > 0):
              while ($item = $order_items->fetch_assoc()):
                $line_total = $item['price'] * $item['quantity'];
            ?>
              <tr>
                <td><?php echo htmlspecialchars($item['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                <td>रु. <?php echo number_format($item['price'], 2); ?></td>
                <td style="font-weight: 600;">रु. <?php echo number_format($line_total, 2); ?></td>
              </tr>
            <?php 
              endwhile;
            endif;
            ?>
            <tr>
              <td colspan="3" style="text-align: right; font-weight: 600; color: var(--admin-text);">Grand Total</td>
              <td style="font-weight: 700; font-size: 15px; color: var(--admin-accent);">रु. <?php echo number_format($data['total'], 2); ?></td>
            </tr>
          </tbody>
        </table>

        <div style="margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--admin-border); display: flex; gap: 20px; font-size: 13px; color: var(--admin-text-muted);">
          <span><strong>Status:</strong> 
            <?php
            if ($data['status'] == 0) echo '<span class="admin-badge process">Under Process</span>';
            elseif ($data['status'] == 1) echo '<span class="admin-badge completed">Completed</span>';
            elseif ($data['status'] == 2) echo '<span class="admin-badge cancelled">Cancelled</span>';
            ?>
          </span>
        </div>
      </div>

    </div>

  </div>

  <!-- Status Update Confirmation Modal -->
  <div class="admin-modal-overlay" id="statusModal">
    <div class="admin-modal-card">
      <div class="admin-modal-icon warning">
        <i class="bx bx-error"></i>
      </div>
      <h4>Update Order Status?</h4>
      <p>Are you sure you want to change the status for order <strong>#<?php echo $data['order_id']; ?></strong>? The customer will see this change on their dashboard.</p>
      <div class="admin-modal-actions">
        <button class="admin-btn outline" onclick="closeStatusModal()">Cancel</button>
        <button class="admin-btn primary" onclick="submitStatusForm()">Confirm Update</button>
      </div>
    </div>
  </div>

  <script>
    function confirmStatusUpdate() {
      document.getElementById('statusModal').classList.add('show');
    }

    function closeStatusModal() {
      document.getElementById('statusModal').classList.remove('show');
    }

    function submitStatusForm() {
      // Add hidden submit trigger
      var form = document.getElementById('statusForm');
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'btnStatus';
      input.value = '1';
      form.appendChild(input);
      form.submit();
    }

    // Close on backdrop click
    document.getElementById('statusModal').addEventListener('click', function(e) {
      if (e.target === this) closeStatusModal();
    });
  </script>

  </main>
  </body>
</html>
