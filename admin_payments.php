<?php 
/**
 * admin_payments.php
 * Comprehensive Payment Slips, Gateway Logs & Settlement Dashboard for Store Admin
 */

require_once 'config.php';
include_once('dashboard.php');

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
$pharmacy_details = get_pharmacy_details($pharmacy_id);

// Handle Search and Filters
$search = trim($_GET['search'] ?? '');
$filter_gateway = trim($_GET['gateway'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where_clauses = ["o.pharmacy_id = $pharmacy_id"];

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $where_clauses[] = "(o.tracking_order LIKE '%$search_safe%' OR o.order_id LIKE '%$search_safe%' OR o.user_name LIKE '%$search_safe%' OR o.phone LIKE '%$search_safe%' OR o.transaction_id LIKE '%$search_safe%')";
}

if (!empty($filter_gateway)) {
    $gw_safe = $conn->real_escape_string($filter_gateway);
    $where_clauses[] = "o.payment = '$gw_safe'";
}

if (!empty($filter_status)) {
    $st_safe = $conn->real_escape_string($filter_status);
    $where_clauses[] = "o.payment_status = '$st_safe'";
}

$where_sql = implode(" AND ", $where_clauses);

// Fetch Payment Statistics
$total_revenue = 0.00;
$esewa_revenue = 0.00;
$esewa_count = 0;
$khalti_revenue = 0.00;
$khalti_count = 0;
$cod_revenue = 0.00;
$cod_count = 0;
$total_transactions = 0;

$stats_res = $conn->query("SELECT payment, payment_status, COUNT(*) AS cnt, SUM(total) AS sum_total FROM tbl_order WHERE pharmacy_id = $pharmacy_id GROUP BY payment, payment_status");
if ($stats_res && $stats_res->num_rows > 0) {
    while ($s = $stats_res->fetch_assoc()) {
        $p = strtolower($s['payment'] ?? 'cod');
        $pst = strtolower($s['payment_status'] ?? 'pending');
        $sum = !empty($s['sum_total']) ? (float)$s['sum_total'] : 0.00;
        $cnt = (int)$s['cnt'];

        $total_transactions += $cnt;
        if ($pst === 'paid') {
            $total_revenue += $sum;
        }

        if ($p === 'esewa') {
            $esewa_count += $cnt;
            if ($pst === 'paid') $esewa_revenue += $sum;
        } elseif ($p === 'khalti') {
            $khalti_count += $cnt;
            if ($pst === 'paid') $khalti_revenue += $sum;
        } else {
            $cod_count += $cnt;
            $cod_revenue += $sum;
        }
    }
}

// Fetch Paginated/Filtered Transactions
$sql = "SELECT o.*, d.name AS driver_name 
        FROM tbl_order o 
        LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
        WHERE $where_sql 
        ORDER BY o.order_id DESC";

$result = $conn->query($sql);
?>

<div class="admin-page-wrapper">

  <!-- Header -->
  <div class="admin-page-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; margin-bottom: 24px;">
    <div>
      <h1 style="font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 4px; display: flex; align-items: center; gap: 8px;">
        <i class="bx bx-receipt" style="color: var(--admin-accent, #059669);"></i> Payment Slips & Gateway Logs
      </h1>
      <p style="font-size: 13px; color: #64748b; margin: 0;">
        Track real-time eSewa ePay v2, Khalti ePayment v2, and Cash on Delivery payment slips for <?php echo htmlspecialchars($pharmacy_details['name']); ?>.
      </p>
    </div>
    <div style="display: flex; gap: 10px;">
      <a href="admin_order.php" class="admin-btn outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="bx bx-cart"></i> Manage Orders
      </a>
      <a href="pos_history.php" class="admin-btn outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="bx bx-desktop"></i> POS In-Store Logs
      </a>
    </div>
  </div>

  <!-- Metric Overview Cards -->
  <div class="payment-metrics-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    
    <!-- Card 1: Total Online Paid -->
    <div class="metric-card-pay" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Online Paid</span>
        <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(16, 185, 129, 0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 18px;">
          <i class="bx bx-wallet"></i>
        </div>
      </div>
      <div style="font-size: 24px; font-weight: 800; color: #0f172a;">रु. <?php echo number_format($total_revenue, 2); ?></div>
      <div style="font-size: 11.5px; color: #059669; font-weight: 600; margin-top: 4px;">
        <i class="bx bx-check-double"></i> Verified Settlement
      </div>
    </div>

    <!-- Card 2: eSewa Gateway -->
    <div class="metric-card-pay" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #438f2f; text-transform: uppercase;">eSewa Wallet</span>
        <img src="img/esewa_logo.png" alt="eSewa" style="height: 20px; max-width: 50px; object-fit: contain;">
      </div>
      <div style="font-size: 24px; font-weight: 800; color: #0f172a;">रु. <?php echo number_format($esewa_revenue, 2); ?></div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
        <strong><?php echo $esewa_count; ?></strong> total orders via eSewa
      </div>
    </div>

    <!-- Card 3: Khalti Gateway -->
    <div class="metric-card-pay" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #5c2d91; text-transform: uppercase;">Khalti Digital</span>
        <img src="img/khalti_logo.png" alt="Khalti" style="height: 20px; max-width: 50px; object-fit: contain;">
      </div>
      <div style="font-size: 24px; font-weight: 800; color: #0f172a;">रु. <?php echo number_format($khalti_revenue, 2); ?></div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
        <strong><?php echo $khalti_count; ?></strong> total orders via Khalti
      </div>
    </div>

    <!-- Card 4: Cash on Delivery -->
    <div class="metric-card-pay" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
        <span style="font-size: 12px; font-weight: 700; color: #0284c7; text-transform: uppercase;">Cash on Delivery</span>
        <img src="img/cod_logo.png" alt="COD" style="height: 20px; max-width: 50px; object-fit: contain;">
      </div>
      <div style="font-size: 24px; font-weight: 800; color: #0f172a;">रु. <?php echo number_format($cod_revenue, 2); ?></div>
      <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">
        <strong><?php echo $cod_count; ?></strong> doorstep orders
      </div>
    </div>

  </div>

  <!-- Search & Filter Form -->
  <div class="admin-card" style="padding: 16px 20px; margin-bottom: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
    <form method="GET" action="admin_payments.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
      
      <div style="flex: 1; min-width: 220px; position: relative;">
        <i class="bx bx-search" style="position: absolute; left: 12px; top: 11px; color: #94a3b8; font-size: 18px;"></i>
        <input type="text" name="search" class="admin-input" placeholder="Search Order #, Tracking, Customer, or Tx ID..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 36px; height: 40px; border-radius: 8px; width: 100%;">
      </div>

      <div style="min-width: 150px;">
        <select name="gateway" class="admin-input" style="height: 40px; border-radius: 8px; width: 100%;">
          <option value="">All Gateways</option>
          <option value="esewa" <?php echo $filter_gateway === 'esewa' ? 'selected' : ''; ?>>eSewa</option>
          <option value="khalti" <?php echo $filter_gateway === 'khalti' ? 'selected' : ''; ?>>Khalti</option>
          <option value="cod" <?php echo $filter_gateway === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
        </select>
      </div>

      <div style="min-width: 150px;">
        <select name="status" class="admin-input" style="height: 40px; border-radius: 8px; width: 100%;">
          <option value="">All Statuses</option>
          <option value="Paid" <?php echo $filter_status === 'Paid' ? 'selected' : ''; ?>>Paid</option>
          <option value="Pending" <?php echo $filter_status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="Failed" <?php echo $filter_status === 'Failed' ? 'selected' : ''; ?>>Failed</option>
        </select>
      </div>

      <button type="submit" class="admin-btn primary" style="height: 40px; padding: 0 18px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="bx bx-filter-alt"></i> Filter
      </button>

      <?php if (!empty($search) || !empty($filter_gateway) || !empty($filter_status)): ?>
        <a href="admin_payments.php" class="admin-btn outline" style="height: 40px; padding: 0 14px; border-radius: 8px; display: inline-flex; align-items: center;">
          Clear
        </a>
      <?php endif; ?>

    </form>
  </div>

  <!-- Transactions Table -->
  <div class="admin-card" style="padding: 0; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px;">
    
    <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
      <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Payment Transaction Logs</h3>
      <span style="font-size: 12px; color: #64748b; font-weight: 600;">Showing <?php echo $result ? $result->num_rows : 0; ?> payment records</span>
    </div>

    <div style="overflow-x: auto;">
      <table class="admin-table" style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background: #f8fafc; border-bottom: 1.5px solid #e2e8f0;">
            <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Slip / Order #</th>
            <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Customer</th>
            <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Gateway</th>
            <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Transaction ID</th>
            <th style="padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Date & Time</th>
            <th style="padding: 12px 16px; text-align: right; font-size: 12px; font-weight: 700; color: #475569;">Amount</th>
            <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Payment Status</th>
            <th style="padding: 12px 16px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Slip Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): 
              $pm = strtolower($row['payment'] ?? 'cod');
              $pst = $row['payment_status'] ?? 'Pending';
              $tx = !empty($row['transaction_id']) ? $row['transaction_id'] : '—';
            ?>
              <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                
                <!-- Order ID & Slip Number -->
                <td style="padding: 14px 16px;">
                  <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Order #<?php echo $row['order_id']; ?></div>
                  <div style="font-family: monospace; font-size: 11.5px; color: var(--admin-accent, #059669); font-weight: 600;">
                    <?php echo htmlspecialchars($row['tracking_order']); ?>
                  </div>
                </td>

                <!-- Customer Details -->
                <td style="padding: 14px 16px;">
                  <div style="font-weight: 600; color: #0f172a; font-size: 13px;"><?php echo htmlspecialchars($row['user_name']); ?></div>
                  <div style="color: #64748b; font-size: 11.5px;"><?php echo htmlspecialchars($row['phone']); ?></div>
                </td>

                <!-- Gateway with Logo -->
                <td style="padding: 14px 16px;">
                  <?php if ($pm === 'esewa'): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="width: 32px; height: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; padding: 2px;">
                        <img src="img/esewa_logo.png" alt="eSewa" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                      </div>
                      <span style="font-weight: 700; color: #438f2f; font-size: 12.5px;">eSewa</span>
                    </div>
                  <?php elseif ($pm === 'khalti'): ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="width: 32px; height: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; padding: 2px;">
                        <img src="img/khalti_logo.png" alt="Khalti" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                      </div>
                      <span style="font-weight: 700; color: #5c2d91; font-size: 12.5px;">Khalti</span>
                    </div>
                  <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <div style="width: 32px; height: 22px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; padding: 2px;">
                        <img src="img/cod_logo.png" alt="COD" style="max-height: 100%; max-width: 100%; object-fit: contain;">
                      </div>
                      <span style="font-weight: 600; color: #475569; font-size: 12.5px;">COD</span>
                    </div>
                  <?php endif; ?>
                </td>

                <!-- Transaction Reference -->
                <td style="padding: 14px 16px;">
                  <?php if (!empty($row['transaction_id'])): ?>
                    <div style="display: inline-flex; align-items: center; gap: 5px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 3px 8px; border-radius: 6px;">
                      <span style="font-family: monospace; font-size: 12px; font-weight: 600; color: #0f172a; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?php echo htmlspecialchars($row['transaction_id']); ?>
                      </span>
                      <button onclick="copyToClipboard('<?php echo htmlspecialchars($row['transaction_id']); ?>')" style="background: none; border: none; cursor: pointer; color: #64748b; padding: 0; font-size: 14px;" title="Copy Transaction ID">
                        <i class="bx bx-copy"></i>
                      </button>
                    </div>
                  <?php else: ?>
                    <span style="color: #94a3b8; font-size: 12px;">N/A (Cash on Delivery)</span>
                  <?php endif; ?>
                </td>

                <!-- Date -->
                <td style="padding: 14px 16px; font-size: 12.5px; color: #475569;">
                  <div><?php echo date("M d, Y", strtotime($row['created_at'])); ?></div>
                  <small style="color: #94a3b8; font-size: 11px;"><?php echo date("h:i A", strtotime($row['created_at'])); ?></small>
                </td>

                <!-- Amount -->
                <td style="padding: 14px 16px; text-align: right; font-weight: 800; color: #059669; font-size: 14px;">
                  रु. <?php echo number_format($row['total'], 2); ?>
                </td>

                <!-- Payment Status -->
                <td style="padding: 14px 16px; text-align: center;">
                  <?php if (strcasecmp($pst, 'Paid') === 0 || strcasecmp($pst, 'COMPLETE') === 0): ?>
                    <span style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="bx bx-check-circle"></i> Paid Online
                    </span>
                  <?php elseif (strcasecmp($pst, 'Pending') === 0): ?>
                    <span style="background: rgba(245, 158, 11, 0.12); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="bx bx-time-five"></i> Pending
                    </span>
                  <?php else: ?>
                    <span style="background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px;">
                      <i class="bx bx-x-circle"></i> <?php echo htmlspecialchars($pst); ?>
                    </span>
                  <?php endif; ?>
                </td>

                <!-- Actions -->
                <td style="padding: 14px 16px; text-align: center;">
                  <div style="display: inline-flex; gap: 6px; align-items: center;">
                    
                    <!-- Standalone Printable Slip -->
                    <a href="admin_payment_slip.php?order_id=<?php echo $row['order_id']; ?>" target="_blank" class="admin-btn primary" style="padding: 5px 10px; font-size: 12px; height: auto; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" title="Open Official Payment Slip">
                      <i class="bx bx-receipt"></i> Slip
                    </a>

                    <!-- View Order Details -->
                    <a href="view_order.php?order_id=<?php echo $row['order_id']; ?>" class="admin-btn outline" style="padding: 5px 10px; font-size: 12px; height: auto; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px;" title="View Order">
                      <i class="bx bx-show"></i> Order
                    </a>

                  </div>
                </td>

              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" style="padding: 40px 20px; text-align: center; color: #94a3b8;">
                <i class="bx bx-receipt" style="font-size: 44px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                <div style="font-size: 15px; font-weight: 600; color: #64748b;">No payment records found.</div>
                <p style="font-size: 12.5px; margin-top: 4px;">Try modifying your search filter or placing a test order.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

</div>

<script>
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(function() {
    alert('Transaction ID copied to clipboard: ' + text);
  }).catch(function(err) {
    console.error('Could not copy text: ', err);
  });
}
</script>
</main>
</body>
</html>
