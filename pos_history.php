<?php
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;

// Date & Search Filters
$range = isset($_GET['range']) ? trim($_GET['range']) : 'today';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

$today = date('Y-m-d');
$start_date = $today;
$end_date = $today;

if ($range === 'yesterday') {
    $start_date = date('Y-m-d', strtotime('-1 day'));
    $end_date = $start_date;
} elseif ($range === 'week') {
    $start_date = date('Y-m-d', strtotime('-7 days'));
    $end_date = $today;
} elseif ($range === 'month') {
    $start_date = date('Y-m-01');
    $end_date = date('Y-m-t');
} elseif ($range === 'all') {
    $start_date = '2000-01-01';
    $end_date = '2099-12-31';
}

// Summary Metrics for this Pharmacy
$stats_sql = "SELECT 
    COUNT(*) AS total_invoices,
    SUM(grand_total) AS total_revenue,
    SUM(CASE WHEN payment_method = 'Cash' THEN grand_total ELSE 0 END) AS cash_revenue,
    SUM(CASE WHEN payment_method != 'Cash' THEN grand_total ELSE 0 END) AS digital_revenue
    FROM tbl_pos_sales 
    WHERE pharmacy_id = $pharmacy_id AND DATE(sale_date) BETWEEN '$start_date' AND '$end_date'";
$stats_res = $conn->query($stats_sql);
$stats = [
    'total_invoices' => 0,
    'total_revenue' => 0.00,
    'cash_revenue' => 0.00,
    'digital_revenue' => 0.00
];
if ($stats_res && $stats_res->num_rows > 0) {
    $st = $stats_res->fetch_assoc();
    $stats['total_invoices'] = (int)$st['total_invoices'];
    $stats['total_revenue'] = (float)$st['total_revenue'];
    $stats['cash_revenue'] = (float)$st['cash_revenue'];
    $stats['digital_revenue'] = (float)$st['digital_revenue'];
}

// Build sales query
$where = ["pharmacy_id = $pharmacy_id", "DATE(sale_date) BETWEEN '$start_date' AND '$end_date'"];
if (!empty($search)) {
    $esc = $conn->real_escape_string($search);
    $where[] = "(invoice_no LIKE '%$esc%' OR customer_name LIKE '%$esc%' OR customer_phone LIKE '%$esc%' OR customer_pan LIKE '%$esc%')";
}

$where_sql = " WHERE " . implode(" AND ", $where);
$sales_sql = "SELECT * FROM tbl_pos_sales $where_sql ORDER BY sale_id DESC";
$sales_res = $conn->query($sales_sql);
$sales = [];
if ($sales_res && $sales_res->num_rows > 0) {
    while ($r = $sales_res->fetch_assoc()) {
        $sales[] = $r;
    }
}
?>

<link rel="stylesheet" href="css/product.css">
<style>
.history-wrapper {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.kpi-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 18px 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  display: flex;
  align-items: center;
  gap: 16px;
}
.kpi-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.kpi-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.kpi-icon.primary { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
.kpi-icon.warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.kpi-icon.info { background: rgba(59, 130, 246, 0.12); color: #3b82f6; }

.kpi-num {
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
}
.kpi-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

.filter-bar-box {
  background: #ffffff;
  border-radius: 12px;
  padding: 16px 20px;
  border: 1px solid #e2e8f0;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 14px;
}
.filter-tabs {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.filter-tab {
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  color: #64748b;
  background: #f1f5f9;
  transition: all 0.2s ease;
}
.filter-tab:hover { background: #e2e8f0; color: #1e293b; }
.filter-tab.active { background: #0f172a; color: #ffffff; }

.pay-badge {
  font-size: 11px;
  padding: 3px 8px;
  border-radius: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.pay-cash { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.pay-qr { background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; }
.pay-card { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
</style>

<div class="history-wrapper">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-receipt' style="color: #10b981;"></i> POS Sales & Register History
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                Shift logs, walk-in tax invoices, payment breakdowns, and receipt reprint.
            </p>
        </div>
        <div>
            <a href="pos.php" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                <i class='bx bx-desktop'></i> Open POS Cashier Terminal
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon success"><i class='bx bx-rupee'></i></div>
            <div>
                <div class="kpi-num">रु. <?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div class="kpi-label">POS Gross Volume</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon primary"><i class='bx bx-receipt'></i></div>
            <div>
                <div class="kpi-num"><?php echo number_format($stats['total_invoices']); ?></div>
                <div class="kpi-label">Bills / Invoices Issued</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon info"><i class='bx bx-money'></i></div>
            <div>
                <div class="kpi-num">रु. <?php echo number_format($stats['cash_revenue'], 2); ?></div>
                <div class="kpi-label">Cash Collected</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warning"><i class='bx bx-qr-scan'></i></div>
            <div>
                <div class="kpi-num">रु. <?php echo number_format($stats['digital_revenue'], 2); ?></div>
                <div class="kpi-label">Digital (QR / Card)</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-bar-box">
        <div class="filter-tabs">
            <a href="pos_history.php?range=today" class="filter-tab <?php echo $range === 'today' ? 'active' : ''; ?>">Today</a>
            <a href="pos_history.php?range=yesterday" class="filter-tab <?php echo $range === 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
            <a href="pos_history.php?range=week" class="filter-tab <?php echo $range === 'week' ? 'active' : ''; ?>">Last 7 Days</a>
            <a href="pos_history.php?range=month" class="filter-tab <?php echo $range === 'month' ? 'active' : ''; ?>">This Month</a>
            <a href="pos_history.php?range=all" class="filter-tab <?php echo $range === 'all' ? 'active' : ''; ?>">All Time</a>
        </div>

        <form method="GET" action="pos_history.php" style="display: flex; gap: 8px; flex: 1; max-width: 380px;">
            <input type="hidden" name="range" value="<?php echo htmlspecialchars($range); ?>">
            <div style="position: relative; width: 100%;">
                <i class='bx bx-search' style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search invoice #, customer..." style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none;">
            </div>
            <button type="submit" style="background: #0f172a; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Filter</button>
            <?php if (!empty($search)): ?>
                <a href="pos_history.php?range=<?php echo htmlspecialchars($range); ?>" style="background: #e2e8f0; color: #475569; padding: 8px 12px; border-radius: 8px; font-size: 13px; text-decoration: none; display: flex; align-items: center;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Sales Table -->
    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 14px 18px;">Invoice #</th>
                        <th style="padding: 14px 18px;">Date & Time</th>
                        <th style="padding: 14px 18px;">Customer</th>
                        <th style="padding: 14px 18px;">Cashier</th>
                        <th style="padding: 14px 18px;">Payment Mode</th>
                        <th style="padding: 14px 18px;">Discount</th>
                        <th style="padding: 14px 18px; font-weight: 800;">Grand Total</th>
                        <th style="padding: 14px 18px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody style="divide-y: 1px solid #e2e8f0;">
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="8" style="padding: 48px 20px; text-align: center; color: #94a3b8;">
                                <i class='bx bx-receipt' style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                <div style="font-size: 15px; font-weight: 600; color: #475569;">No POS Transactions Recorded</div>
                                <div style="font-size: 13px; margin-top: 4px;">No counter sales match your active filter range.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $s): 
                            $pm = $s['payment_method'];
                            $badge_cls = ($pm === 'Cash') ? 'pay-cash' : (($pm === 'QR Pay') ? 'pay-qr' : 'pay-card');
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <td style="padding: 14px 18px;">
                                    <span style="font-family: monospace; font-weight: 800; color: #0f172a; background: #f1f5f9; padding: 3px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                        <?php echo htmlspecialchars($s['invoice_no']); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; color: #64748b;">
                                    <?php echo date('M d, Y &bull; h:i A', strtotime($s['sale_date'])); ?>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($s['customer_name']); ?></div>
                                    <?php if (!empty($s['customer_phone'])): ?>
                                        <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($s['customer_phone']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; color: #334155; font-weight: 500;">
                                    <?php echo htmlspecialchars($s['cashier_name'] ?? 'Staff'); ?>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span class="pay-badge <?php echo $badge_cls; ?>">
                                        <i class='bx <?php echo $pm === 'Cash' ? 'bx-money' : ($pm === 'QR Pay' ? 'bx-qr' : 'bx-credit-card'); ?>'></i>
                                        <?php echo htmlspecialchars($pm); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; color: #f59e0b; font-weight: 600;">
                                    <?php echo $s['discount_amount'] > 0 ? ('- रु. ' . number_format($s['discount_amount'], 2)) : '—'; ?>
                                </td>
                                <td style="padding: 14px 18px; font-weight: 800; font-size: 14px; color: #059669;">
                                    रु. <?php echo number_format($s['grand_total'], 2); ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: right;">
                                    <a href="pos_receipt.php?inv=<?php echo urlencode($s['invoice_no']); ?>" target="_blank" style="background: #0f172a; color: #ffffff; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class='bx bx-printer'></i> Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
