<?php 
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_admin_pharmacy_id = require_admin_tenant();
$conn = get_db_connection();
$pharmacy_id = $active_admin_pharmacy_id;

// Handle status update with confirmation and driver dispatch
if (isset($_POST['btnStatus'])) {
    $odr_id = (int)$_POST['order_id'];
    $order_status = (int)$_POST['order_status'];
    $driver_id = !empty($_POST['driver_id']) ? (int)$_POST['driver_id'] : null;
    $delivery_notes = trim($_POST['delivery_notes'] ?? '');

    $stmt = $conn->prepare("UPDATE tbl_order SET status = ?, driver_id = ?, delivery_notes = ?, delivered_at = IF(? = 1, NOW(), delivered_at) WHERE order_id = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("iisiii", $order_status, $driver_id, $delivery_notes, $order_status, $odr_id, $pharmacy_id);
        $stmt->execute();
        $stmt->close();

        // Update driver status
        if ($driver_id && $order_status == 4) {
            $conn->query("UPDATE tbl_delivery_drivers SET status = 2 WHERE driver_id = $driver_id");
        } elseif ($driver_id && $order_status == 1) {
            $conn->query("UPDATE tbl_delivery_drivers SET status = 1 WHERE driver_id = $driver_id");
        }

        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Order Updated',
            'message' => "Order #$odr_id status and driver dispatch updated successfully."
        ];
    }

    header("Location: view_order.php?order_id=" . $odr_id . "&updated=1");
    exit();
}

// Fetch order data strictly scoped to tenant with assigned driver info
$data = null;
$order_id = 0;
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    $stmt = $conn->prepare("SELECT o.*, d.name AS driver_name, d.phone AS driver_phone, d.vehicle_type, d.vehicle_number 
                            FROM tbl_order o 
                            LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
                            WHERE o.order_id = ? AND o.pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $order_id, $pharmacy_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

if (!$data) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Access Denied',
        'message' => 'Order not found or does not belong to your store.'
    ];
    header("Location: admin_order.php");
    exit();
}

// Include Dashboard Layout Shell after redirects
include_once('dashboard.php');

// Fetch all available drivers for dropdown
$fleet_drivers = [];
$drv_res = $conn->query("SELECT * FROM tbl_delivery_drivers WHERE pharmacy_id = $pharmacy_id ORDER BY name ASC");
if ($drv_res && $drv_res->num_rows > 0) {
    while ($dr = $drv_res->fetch_assoc()) {
        $fleet_drivers[] = $dr;
    }
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
      <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <a href="admin_payment_slip.php?order_id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn primary" style="height: 36px; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
          <i class="bx bx-receipt"></i> View Payment Slip
        </a>
        <a href="order_receipt.php?id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn outline" style="height: 36px; display: inline-flex; align-items: center; gap: 4px;">
          <i class="bx bx-printer"></i> Tax Receipt
        </a>
        <a href="admin_order.php" class="admin-btn outline" style="height: 36px; display: inline-flex; align-items: center; gap: 4px;">
          <i class="bx bx-arrow-back"></i> Back
        </a>
      </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
      <div style="padding: 12px 18px; background-color: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; color: var(--admin-success); font-size: 13.5px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-check-circle" style="font-size: 18px;"></i> Order status and driver dispatch updated successfully.
      </div>
    <?php endif; ?>

    <div class="order-detail-grid">

      <!-- Customer Details Card -->
      <div class="admin-card">
        <h3><i class="bx bx-user" style="font-size: 16px;"></i> Customer & Delivery Details</h3>

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
          <label>Payment Method & Settlement</label>
          <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
            <?php 
            $pay_mode = strtolower($data['payment'] ?? 'cod');
            $pay_st = $data['payment_status'] ?? 'Pending';
            if ($pay_mode === 'esewa'): ?>
              <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(96, 187, 70, 0.12); color: #438f2f; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                <strong style="color: #60bb46;">e</strong> eSewa ePay v2
              </span>
            <?php elseif ($pay_mode === 'khalti'): ?>
              <span style="display: inline-flex; align-items: center; gap: 4px; background: rgba(92, 45, 145, 0.12); color: #5c2d91; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                <strong style="color: #5c2d91;">K</strong> Khalti Digital Wallet
              </span>
            <?php else: ?>
              <span style="display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 13px;">
                <i class='bx bx-money'></i> Cash on Delivery (COD)
              </span>
            <?php endif; ?>

            <?php if (strcasecmp($pay_st, 'Paid') === 0): ?>
              <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                <i class="bx bx-check-circle"></i> Paid Online
              </span>
            <?php elseif (strcasecmp($pay_st, 'Failed') === 0): ?>
              <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                <i class="bx bx-x-circle"></i> Failed
              </span>
            <?php else: ?>
              <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700;">
                <i class="bx bx-time"></i> Pending
              </span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($data['transaction_id'])): ?>
        <div class="admin-form-group">
          <label>Gateway Transaction ID</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" class="admin-form-control" style="font-family: monospace; font-size: 12px; flex: 1;" value="<?php echo htmlspecialchars($data['transaction_id'], ENT_QUOTES, 'UTF-8'); ?>" readonly>
            <a href="admin_payment_slip.php?order_id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn outline" style="padding: 6px 12px; font-size: 12px; height: auto;" title="View Payment Slip">
              <i class="bx bx-receipt"></i> Slip
            </a>
          </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($data['prescription'])): ?>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
            <label style="font-weight: 700; font-size: 13px; color: #0f172a; margin: 0;">
              <i class="bx bx-file" style="color: #059669;"></i> Prescription Document
            </label>
            <div>
              <?php 
              $rx_st = (int)($data['prescription_status'] ?? 0);
              if ($rx_st === 1): ?>
                <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-check-shield"></i> Approved
                </span>
              <?php elseif ($rx_st === 2): ?>
                <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-x-circle"></i> Rejected
                </span>
              <?php else: ?>
                <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-time"></i> Pending Review
                </span>
              <?php endif; ?>
            </div>
          </div>

          <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 8px;">
            <a href="<?php echo htmlspecialchars($data['prescription'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="admin-btn view" style="display: inline-flex; gap: 4px; font-size: 12px;">
              <i class="bx bx-search-alt"></i> Inspect Prescription Slip
            </a>
            <a href="prescription_management.php" class="admin-btn outline" style="display: inline-flex; gap: 4px; font-size: 12px;">
              <i class="bx bx-layer"></i> Open RX Queue
            </a>
          </div>

          <?php if (!empty($data['pharmacist_notes'])): ?>
            <div style="font-size: 12px; color: #475569; background: #ffffff; padding: 8px 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 6px;">
              <strong>Pharmacist Notes:</strong> <?php echo htmlspecialchars($data['pharmacist_notes']); ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Assigned Delivery Driver Card -->
        <?php if (!empty($data['driver_name'])): ?>
          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; margin-top: 14px; margin-bottom: 14px;">
            <div style="font-size: 11px; font-weight: 700; color: #059669; text-transform: uppercase; margin-bottom: 4px;">
              <i class='bx bx-cycling'></i> Assigned Delivery Courier
            </div>
            <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?php echo htmlspecialchars($data['driver_name']); ?></div>
            <div style="font-size: 12px; color: #475569; margin-top: 2px;">
              Phone: <a href="tel:<?php echo htmlspecialchars($data['driver_phone']); ?>" style="color: #059669; font-weight: 600;"><?php echo htmlspecialchars($data['driver_phone']); ?></a> &bull; <?php echo htmlspecialchars($data['vehicle_type']); ?> (<?php echo htmlspecialchars($data['vehicle_number'] ?? ''); ?>)
            </div>
            <?php if (!empty($data['delivered_at'])): ?>
              <div style="font-size: 11.5px; color: #059669; font-weight: 600; margin-top: 4px;">
                <i class='bx bx-check'></i> Delivered on: <?php echo date('M d, Y h:i A', strtotime($data['delivered_at'])); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Update Status & Dispatch Section -->
        <form action="" method="POST" id="statusForm" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
          <input type="hidden" name="order_id" value="<?php echo $data['order_id']; ?>">
          
          <div class="admin-form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Assign Delivery Driver</label>
            <select class="admin-select" name="driver_id" style="width: 100%;">
              <option value="">-- No Driver Assigned --</option>
              <?php foreach ($fleet_drivers as $fd): ?>
                <option value="<?php echo $fd['driver_id']; ?>" <?php echo (isset($data['driver_id']) && (int)$data['driver_id'] === (int)$fd['driver_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($fd['name']); ?> (<?php echo htmlspecialchars($fd['vehicle_type']); ?> - <?php echo htmlspecialchars($fd['phone']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="admin-form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Order Status</label>
            <select class="admin-select" name="order_status" id="orderStatusSelect" style="width: 100%;">
              <option value="0" <?php echo $data['status'] == 0 ? "selected" : ""; ?>>0 - Under Process / Verification</option>
              <option value="3" <?php echo $data['status'] == 3 ? "selected" : ""; ?>>3 - Packed & Ready for Pickup</option>
              <option value="4" <?php echo $data['status'] == 4 ? "selected" : ""; ?>>4 - Out for Delivery (Rider Dispatched)</option>
              <option value="1" <?php echo $data['status'] == 1 ? "selected" : ""; ?>>1 - Delivered & Completed</option>
              <option value="2" <?php echo $data['status'] == 2 ? "selected" : ""; ?>>2 - Cancelled</option>
            </select>
          </div>

          <div class="admin-form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Dispatch Notes</label>
            <input type="text" name="delivery_notes" class="admin-form-control" placeholder="e.g. Leave with gate security / Call before arriving" value="<?php echo htmlspecialchars($data['delivery_notes'] ?? '', ENT_QUOTES); ?>">
          </div>

          <button type="submit" name="btnStatus" class="admin-btn primary" style="width: 100%; justify-content: center; height: 40px; margin-top: 6px;">
            <i class="bx bx-refresh"></i> Update Status & Dispatch Courier
          </button>
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
