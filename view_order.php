<?php 
/**
 * view_order.php
 * Modern Order Details, Readable Customer Inspection & Status Management for Admin
 */

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
            'title' => 'Order Status Updated',
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

$pay_mode = strtolower($data['payment'] ?? 'cod');
$pay_st = $data['payment_status'] ?? 'Pending';
$tx_id = !empty($data['transaction_id']) ? $data['transaction_id'] : '';
?>

<div class="admin-page-wrapper">

  <!-- Header & Actions -->
  <div class="admin-page-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
    <div>
      <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 4px;">
        <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin: 0;">Order #<?php echo $data['order_id']; ?></h1>
        <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--admin-accent, #059669); background: rgba(5, 150, 105, 0.1); border: 1px solid rgba(5, 150, 105, 0.25); padding: 3px 10px; border-radius: 6px;">
          <?php echo htmlspecialchars($data['tracking_order'], ENT_QUOTES, 'UTF-8'); ?>
        </span>
      </div>
      <p style="font-size: 13px; color: #64748b; margin: 0;">
        Placed on <?php echo date("F d, Y, h:i A", strtotime($data['created_at'])); ?> &middot; Customer ID: #<?php echo htmlspecialchars($data['user_id']); ?>
      </p>
    </div>
    
    <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
      <a href="admin_payment_slip.php?order_id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn primary" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
        <i class="bx bx-receipt"></i> Payment Slip
      </a>
      <a href="order_receipt.php?id=<?php echo $data['order_id']; ?>" target="_blank" class="admin-btn outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="bx bx-printer"></i> Tax Invoice
      </a>
      <a href="admin_order.php" class="admin-btn outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="bx bx-arrow-back"></i> Back to Orders
      </a>
    </div>
  </div>

  <?php if (isset($_GET['updated'])): ?>
    <div style="padding: 12px 18px; background-color: rgba(16, 185, 129, 0.08); border: 1.5px solid rgba(16, 185, 129, 0.3); border-radius: 10px; color: #059669; font-size: 13.5px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 8px;">
      <i class="bx bx-check-circle" style="font-size: 20px;"></i> Order status and driver dispatch updated successfully.
    </div>
  <?php endif; ?>

  <!-- Top 2-Column Info Grid: Left = Customer & Payment Details, Right = Status & Dispatch Form -->
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 24px;">

    <!-- Left Column: Readable Customer & Payment Information -->
    <div style="display: flex; flex-direction: column; gap: 20px;">

      <!-- Customer Profile Card (Readable Clean Design, No Inputs) -->
      <div class="admin-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 16px;">
          <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="bx bx-user-circle" style="color: #059669; font-size: 20px;"></i> Customer Information
          </h3>
          <span style="font-size: 11.5px; font-weight: 700; color: #64748b; background: #f8fafc; border: 1px solid #e2e8f0; padding: 2px 8px; border-radius: 6px;">
            Verified Buyer
          </span>
        </div>

        <div style="display: flex; align-items: flex-start; gap: 16px; margin-bottom: 18px;">
          <div style="width: 46px; height: 46px; border-radius: 50%; background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; flex-shrink: 0; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);">
            <?php echo strtoupper(substr($data['user_name'], 0, 1)); ?>
          </div>
          <div>
            <div style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
              <?php echo htmlspecialchars($data['user_name'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <a href="tel:<?php echo htmlspecialchars($data['phone']); ?>" style="font-size: 13.5px; font-weight: 700; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="bx bx-phone-call"></i> <?php echo htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($data['phone']); ?>'); alert('Phone copied!');" style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 14px;" title="Copy Phone Number">
                <i class="bx bx-copy"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Delivery Address Box -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px;">
          <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
            <i class="bx bx-map-pin" style="color: #ef4444;"></i> Delivery Destination Address
          </div>
          <div style="font-size: 13.5px; font-weight: 600; color: #1e293b; line-height: 1.4;">
            <?php echo htmlspecialchars($data['address'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
        </div>

        <!-- Prescription Inspection Box (if uploaded) -->
        <?php if (!empty($data['prescription'])): ?>
          <div style="background: #f0fdf4; border: 1.5px dashed #86efac; border-radius: 10px; padding: 14px 16px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
              <div style="font-size: 12.5px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                <i class="bx bx-file" style="color: #059669; font-size: 18px;"></i> Medical Prescription Attached
              </div>
              <?php 
              $rx_st = (int)($data['prescription_status'] ?? 0);
              if ($rx_st === 1): ?>
                <span style="background: #059669; color: #ffffff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-check"></i> Approved
                </span>
              <?php elseif ($rx_st === 2): ?>
                <span style="background: #ef4444; color: #ffffff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-x"></i> Rejected
                </span>
              <?php else: ?>
                <span style="background: #f59e0b; color: #ffffff; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700;">
                  <i class="bx bx-time"></i> Pending Rx Review
                </span>
              <?php endif; ?>
            </div>

            <div style="display: flex; gap: 8px; align-items: center;">
              <a href="<?php echo htmlspecialchars($data['prescription'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="admin-btn view" style="font-size: 12px; padding: 5px 12px; height: auto;">
                <i class="bx bx-search-alt"></i> Open Prescription Document
              </a>
              <a href="prescription_management.php" class="admin-btn outline" style="font-size: 12px; padding: 5px 12px; height: auto;">
                <i class="bx bx-slider-alt"></i> Review Queue
              </a>
            </div>
          </div>
        <?php endif; ?>

      </div>

      <!-- Payment & Settlement Summary Card -->
      <div class="admin-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 22px 24px; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 16px;">
          <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="bx bx-wallet" style="color: #059669; font-size: 20px;"></i> Payment & Settlement
          </h3>
          <a href="admin_payment_slip.php?order_id=<?php echo $data['order_id']; ?>" target="_blank" style="font-size: 12px; font-weight: 700; color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
            <i class="bx bx-receipt"></i> Full Slip &rarr;
          </a>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 50px; height: 34px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; display: flex; align-items: center; justify-content: center; padding: 3px;">
              <?php if ($pay_mode === 'esewa'): ?>
                <img src="img/esewa_logo.png" alt="eSewa" style="max-height: 100%; max-width: 100%; object-fit: contain;">
              <?php elseif ($pay_mode === 'khalti'): ?>
                <img src="img/khalti_logo.png" alt="Khalti" style="max-height: 100%; max-width: 100%; object-fit: contain;">
              <?php else: ?>
                <img src="img/cod_logo.png" alt="COD" style="max-height: 100%; max-width: 100%; object-fit: contain;">
              <?php endif; ?>
            </div>
            <div>
              <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                <?php 
                if ($pay_mode === 'esewa') echo 'eSewa Mobile Wallet';
                elseif ($pay_mode === 'khalti') echo 'Khalti Digital Wallet';
                else echo 'Cash on Delivery (COD)';
                ?>
              </div>
              <div style="font-size: 11.5px; color: #64748b;">Method: <?php echo strtoupper($pay_mode); ?></div>
            </div>
          </div>

          <div>
            <?php if (strcasecmp($pay_st, 'Paid') === 0 || strcasecmp($pay_st, 'COMPLETE') === 0): ?>
              <span style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                <i class="bx bx-check-circle"></i> Paid Online
              </span>
            <?php elseif (strcasecmp($pay_st, 'Failed') === 0): ?>
              <span style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                <i class="bx bx-x-circle"></i> Failed
              </span>
            <?php else: ?>
              <span style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 12px; font-weight: 800; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px;">
                <i class="bx bx-time-five"></i> Settlement Pending
              </span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($tx_id)): ?>
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between;">
            <div>
              <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Gateway Transaction ID</span>
              <div style="font-family: monospace; font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px;">
                <?php echo htmlspecialchars($tx_id); ?>
              </div>
            </div>
            <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($tx_id); ?>'); alert('Transaction ID copied!');" class="admin-btn outline" style="padding: 4px 10px; font-size: 11.5px; height: auto;">
              <i class="bx bx-copy"></i> Copy
            </button>
          </div>
        <?php endif; ?>

      </div>

    </div>

    <!-- Right Column: Status Updater & Courier Dispatch Form -->
    <div class="admin-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px 26px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); height: fit-content;">
      
      <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 14px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
          <i class="bx bx-refresh" style="color: var(--admin-accent, #059669); font-size: 22px;"></i> Update Order Status & Dispatch
        </h3>
        <p style="font-size: 12.5px; color: #64748b; margin: 4px 0 0 0;">
          Select current fulfillment stage and assign delivery courier.
        </p>
      </div>

      <!-- Current Status Banner -->
      <div style="margin-bottom: 20px; padding: 14px 16px; border-radius: 10px; <?php 
        if ($data['status'] == 1) echo 'background: rgba(16, 185, 129, 0.1); border: 1px solid #86efac; color: #065f46;';
        elseif ($data['status'] == 4) echo 'background: rgba(99, 102, 241, 0.1); border: 1px solid #a5b4fc; color: #4338ca;';
        elseif ($data['status'] == 3) echo 'background: rgba(59, 130, 246, 0.1); border: 1px solid #93c5fd; color: #1e40af;';
        elseif ($data['status'] == 2) echo 'background: rgba(239, 68, 68, 0.1); border: 1px solid #fca5a5; color: #991b1b;';
        else echo 'background: rgba(245, 158, 11, 0.1); border: 1px solid #fcd34d; color: #92400e;';
      ?>">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">
          Current Fulfillment State
        </div>
        <div style="font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 6px;">
          <?php 
          if ($data['status'] == 0) echo '<i class="bx bx-loader-alt bx-spin"></i> Processing & Verification';
          elseif ($data['status'] == 3) echo '<i class="bx bx-package"></i> Packed & Ready for Pickup';
          elseif ($data['status'] == 4) echo '<i class="bx bx-cycling"></i> Out for Delivery (Courier En Route)';
          elseif ($data['status'] == 1) echo '<i class="bx bx-check-circle"></i> Delivered & Completed';
          elseif ($data['status'] == 2) echo '<i class="bx bx-x-circle"></i> Cancelled';
          ?>
        </div>
      </div>

      <!-- Update Form -->
      <form action="" method="POST" id="statusForm">
        <input type="hidden" name="order_id" value="<?php echo $data['order_id']; ?>">

        <div class="admin-form-group" style="margin-bottom: 16px;">
          <label style="font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px; display: block;">
            Set New Status Stage
          </label>
          <select class="admin-select" name="order_status" id="orderStatusSelect" style="width: 100%; height: 42px; font-size: 13.5px; border-radius: 8px; border-color: #cbd5e1;">
            <option value="0" <?php echo $data['status'] == 0 ? "selected" : ""; ?>>0 &bull; Processing & Verification</option>
            <option value="3" <?php echo $data['status'] == 3 ? "selected" : ""; ?>>3 &bull; Packed & Ready for Pickup</option>
            <option value="4" <?php echo $data['status'] == 4 ? "selected" : ""; ?>>4 &bull; Out for Delivery (Courier En Route)</option>
            <option value="1" <?php echo $data['status'] == 1 ? "selected" : ""; ?>>1 &bull; Delivered & Completed</option>
            <option value="2" <?php echo $data['status'] == 2 ? "selected" : ""; ?>>2 &bull; Cancelled</option>
          </select>
        </div>

        <div class="admin-form-group" style="margin-bottom: 16px;">
          <label style="font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px; display: block;">
            Assign Delivery Rider / Fleet Courier
          </label>
          <select class="admin-select" name="driver_id" style="width: 100%; height: 42px; font-size: 13.5px; border-radius: 8px; border-color: #cbd5e1;">
            <option value="">-- No Driver Assigned (Self Pickup / OTC) --</option>
            <?php foreach ($fleet_drivers as $fd): ?>
              <option value="<?php echo $fd['driver_id']; ?>" <?php echo (isset($data['driver_id']) && (int)$data['driver_id'] === (int)$fd['driver_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($fd['name']); ?> &bull; <?php echo htmlspecialchars($fd['vehicle_type']); ?> (<?php echo htmlspecialchars($fd['phone']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admin-form-group" style="margin-bottom: 20px;">
          <label style="font-weight: 700; font-size: 13px; color: #0f172a; margin-bottom: 6px; display: block;">
            Dispatch & Delivery Notes
          </label>
          <input type="text" name="delivery_notes" class="admin-form-control" placeholder="e.g. Fragile glass bottle / Leave with security" value="<?php echo htmlspecialchars($data['delivery_notes'] ?? '', ENT_QUOTES); ?>" style="height: 42px; border-radius: 8px; border-color: #cbd5e1;">
        </div>

        <button type="button" onclick="confirmStatusUpdate()" class="admin-btn primary" style="width: 100%; justify-content: center; height: 46px; font-size: 14.5px; font-weight: 700; border-radius: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
          <i class="bx bx-check-shield" style="font-size: 18px;"></i> Save Status & Dispatch Updates
        </button>
      </form>

    </div>

  </div>

  <!-- Order Items & Invoice Breakdown Table -->
  <div class="admin-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 0; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
    
    <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-capsule" style="color: #059669; font-size: 20px;"></i> Prescribed Medicines & Items Breakdown
      </h3>
      <span style="font-size: 12px; color: #64748b; font-weight: 600;">
        <?php echo $order_items ? $order_items->num_rows : 0; ?> Item(s) in Order
      </span>
    </div>

    <div style="overflow-x: auto;">
      <table class="admin-table" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
            <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">#</th>
            <th style="padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Medicine / Product Name</th>
            <th style="padding: 12px 20px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Quantity</th>
            <th style="padding: 12px 20px; text-align: right; font-size: 12px; font-weight: 700; color: #475569;">Unit Price</th>
            <th style="padding: 12px 20px; text-align: right; font-size: 12px; font-weight: 700; color: #475569;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $items_subtotal = 0;
          $count = 1;
          if ($order_items && $order_items->num_rows > 0):
            while ($item = $order_items->fetch_assoc()):
              $line_total = $item['price'] * $item['quantity'];
              $items_subtotal += $line_total;
          ?>
            <tr style="border-bottom: 1px solid #f1f5f9;">
              <td style="padding: 14px 20px; color: #64748b; font-size: 13px;"><?php echo $count++; ?></td>
              <td style="padding: 14px 20px;">
                <div style="font-weight: 700; color: #0f172a; font-size: 14px;"><?php echo htmlspecialchars($item['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                <small style="color: #94a3b8; font-size: 11px;">Product ID: #<?php echo $item['prdct_id']; ?></small>
              </td>
              <td style="padding: 14px 20px; text-align: center; font-weight: 700; color: #0f172a; font-size: 14px;">
                x<?php echo htmlspecialchars($item['quantity']); ?>
              </td>
              <td style="padding: 14px 20px; text-align: right; color: #475569; font-size: 13.5px;">
                रु. <?php echo number_format($item['price'], 2); ?>
              </td>
              <td style="padding: 14px 20px; text-align: right; font-weight: 700; color: #0f172a; font-size: 14px;">
                रु. <?php echo number_format($line_total, 2); ?>
              </td>
            </tr>
          <?php 
            endwhile;
          endif; 
          
          $delivery_charge = max(0, $data['total'] - $items_subtotal);
          ?>
        </tbody>
      </table>
    </div>

    <!-- Totals Summary Bar -->
    <div style="background: #f8fafc; padding: 20px 24px; border-top: 1.5px solid #e2e8f0; display: flex; justify-content: flex-end;">
      <div style="width: 300px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 6px;">
          <span>Medicines Subtotal:</span>
          <span style="font-weight: 600; color: #0f172a;">रु. <?php echo number_format($items_subtotal, 2); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b; margin-bottom: 10px;">
          <span>Delivery & Dispatch Fee:</span>
          <span style="font-weight: 600; color: #0f172a;">रु. <?php echo number_format($delivery_charge, 2); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 800; color: #059669; border-top: 2px solid #e2e8f0; padding-top: 8px;">
          <span>Grand Total:</span>
          <span>रु. <?php echo number_format($data['total'], 2); ?></span>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Status Update Confirmation Modal -->
<div class="admin-modal-overlay" id="statusModal">
  <div class="admin-modal-card" style="border-radius: 14px; padding: 24px; text-align: center;">
    <div class="admin-modal-icon warning" style="width: 50px; height: 50px; border-radius: 50%; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 16px;">
      <i class="bx bx-bell"></i>
    </div>
    <h4 style="font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">Confirm Order Status Update?</h4>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.4;">
      You are changing the fulfillment status for order <strong>#<?php echo $data['order_id']; ?></strong>. The customer and assigned courier will be immediately notified.
    </p>
    <div class="admin-modal-actions" style="display: flex; gap: 10px; justify-content: center;">
      <button class="admin-btn outline" onclick="closeStatusModal()" style="height: 38px; padding: 0 18px;">Cancel</button>
      <button class="admin-btn primary" onclick="submitStatusForm()" style="height: 38px; padding: 0 20px;">Confirm & Save</button>
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
  var form = document.getElementById('statusForm');
  var input = document.createElement('input');
  input.type = 'hidden';
  input.name = 'btnStatus';
  input.value = '1';
  form.appendChild(input);
  form.submit();
}

// Close modal on backdrop click
document.getElementById('statusModal').addEventListener('click', function(e) {
  if (e.target === this) closeStatusModal();
});
</script>
</main>
</body>
</html>
