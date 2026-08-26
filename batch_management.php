<?php
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;

$msg = '';
$msg_type = '';

// Handle Add New Batch Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnAddBatch'])) {
    $prdct_id = (int)($_POST['prdct_id'] ?? 0);
    $batch_number = trim($_POST['batch_number'] ?? '');
    $mfg_date = trim($_POST['mfg_date'] ?? '');
    $exp_date = trim($_POST['exp_date'] ?? '');
    $purchase_cost = (float)($_POST['purchase_cost'] ?? 0.00);
    $mrp_price = (float)($_POST['mrp_price'] ?? 0.00);
    $quantity = max(1, (int)($_POST['quantity'] ?? 0));

    if ($prdct_id > 0 && !empty($batch_number) && !empty($mfg_date) && !empty($exp_date) && $quantity > 0) {
        // Verify product belongs to current pharmacy
        $chk_p = $conn->prepare("SELECT prdct_id, prdct_price FROM tbl_products WHERE prdct_id = ? AND pharmacy_id = ?");
        if ($chk_p) {
            $chk_p->bind_param("ii", $prdct_id, $pharmacy_id);
            $chk_p->execute();
            $p_res = $chk_p->get_result();
            if ($p_res && $p_res->num_rows > 0) {
                $p_row = $p_res->fetch_assoc();
                if ($mrp_price <= 0) {
                    $mrp_price = (float)$p_row['prdct_price'];
                }
                if ($purchase_cost <= 0) {
                    $purchase_cost = round($mrp_price * 0.70, 2);
                }

                // Insert into tbl_product_batches
                $stmt_ib = $conn->prepare("INSERT INTO tbl_product_batches (pharmacy_id, prdct_id, batch_number, mfg_date, exp_date, purchase_cost, mrp_price, quantity, initial_quantity, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
                if ($stmt_ib) {
                    $stmt_ib->bind_param("iisssddii", $pharmacy_id, $prdct_id, $batch_number, $mfg_date, $exp_date, $purchase_cost, $mrp_price, $quantity, $quantity);
                    if ($stmt_ib->execute()) {
                        // Increment total stock in tbl_products
                        $conn->query("UPDATE tbl_products SET stock_quantity = stock_quantity + $quantity, batch_number = '$batch_number' WHERE prdct_id = $prdct_id AND pharmacy_id = $pharmacy_id");
                        $msg = "Batch <strong>" . htmlspecialchars($batch_number) . "</strong> with <strong>$quantity units</strong> added successfully!";
                        $msg_type = 'success';
                    } else {
                        $msg = "Failed to add batch. Please check if batch number already exists.";
                        $msg_type = 'error';
                    }
                    $stmt_ib->close();
                }
            } else {
                $msg = "Invalid product selected.";
                $msg_type = 'error';
            }
            $chk_p->close();
        }
    } else {
        $msg = "Please fill in all required fields properly.";
        $msg_type = 'error';
    }
}

// Handle Adjust Stock / Dispose Batch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnAdjustBatch'])) {
    $batch_id = (int)($_POST['batch_id'] ?? 0);
    $adj_qty = (int)($_POST['adj_quantity'] ?? 0);
    $adj_action = trim($_POST['adj_action'] ?? 'set'); // 'set', 'add', 'subtract', 'dispose'

    if ($batch_id > 0) {
        $stmt_ab = $conn->prepare("SELECT b.*, p.stock_quantity FROM tbl_product_batches b JOIN tbl_products p ON b.prdct_id = p.prdct_id WHERE b.batch_id = ? AND b.pharmacy_id = ?");
        if ($stmt_ab) {
            $stmt_ab->bind_param("ii", $batch_id, $pharmacy_id);
            $stmt_ab->execute();
            $b_res = $stmt_ab->get_result();
            if ($b_res && $b_res->num_rows > 0) {
                $b_data = $b_res->fetch_assoc();
                $cur_batch_qty = (int)$b_data['quantity'];
                $prdct_id = (int)$b_data['prdct_id'];
                $new_batch_qty = $cur_batch_qty;

                if ($adj_action === 'dispose') {
                    $diff = -$cur_batch_qty;
                    $new_batch_qty = 0;
                    $status = 0;
                    $conn->query("UPDATE tbl_product_batches SET quantity = 0, status = 0 WHERE batch_id = $batch_id");
                    $conn->query("UPDATE tbl_products SET stock_quantity = GREATEST(0, stock_quantity + $diff) WHERE prdct_id = $prdct_id");
                    $msg = "Batch marked as disposed / expired and removed from active inventory.";
                    $msg_type = 'success';
                } elseif ($adj_action === 'set') {
                    $new_batch_qty = max(0, $adj_qty);
                    $diff = $new_batch_qty - $cur_batch_qty;
                    $conn->query("UPDATE tbl_product_batches SET quantity = $new_batch_qty, status = " . ($new_batch_qty > 0 ? 1 : 0) . " WHERE batch_id = $batch_id");
                    $conn->query("UPDATE tbl_products SET stock_quantity = GREATEST(0, stock_quantity + $diff) WHERE prdct_id = $prdct_id");
                    $msg = "Batch quantity updated to $new_batch_qty units.";
                    $msg_type = 'success';
                }
            }
            $stmt_ab->close();
        }
    }
}

// Filter Settings
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Calculate Global Metrics for this pharmacy
$today = date('Y-m-d');
$date_30d = date('Y-m-d', strtotime('+30 days'));
$date_90d = date('Y-m-d', strtotime('+90 days'));

$cnt_total = 0;
$cnt_expired = 0;
$cnt_critical = 0;
$cnt_warning = 0;
$cnt_safe = 0;
$total_batch_value = 0.00;

$stats_res = $conn->query("SELECT 
    COUNT(*) AS total_batches,
    SUM(CASE WHEN exp_date < '$today' THEN 1 ELSE 0 END) AS expired_cnt,
    SUM(CASE WHEN exp_date >= '$today' AND exp_date <= '$date_30d' THEN 1 ELSE 0 END) AS critical_cnt,
    SUM(CASE WHEN exp_date > '$date_30d' AND exp_date <= '$date_90d' THEN 1 ELSE 0 END) AS warning_cnt,
    SUM(CASE WHEN exp_date > '$date_90d' THEN 1 ELSE 0 END) AS safe_cnt,
    SUM(quantity * mrp_price) AS total_val
    FROM tbl_product_batches 
    WHERE pharmacy_id = $pharmacy_id AND status = 1");

if ($stats_res && $stats_res->num_rows > 0) {
    $st = $stats_res->fetch_assoc();
    $cnt_total = (int)$st['total_batches'];
    $cnt_expired = (int)$st['expired_cnt'];
    $cnt_critical = (int)$st['critical_cnt'];
    $cnt_warning = (int)$st['warning_cnt'];
    $cnt_safe = (int)$st['safe_cnt'];
    $total_batch_value = (float)$st['total_val'];
}

// Build query for list
$where = ["b.pharmacy_id = $pharmacy_id"];

if ($filter_status === 'expired') {
    $where[] = "b.exp_date < '$today'";
} elseif ($filter_status === 'critical') {
    $where[] = "b.exp_date >= '$today' AND b.exp_date <= '$date_30d'";
} elseif ($filter_status === 'warning') {
    $where[] = "b.exp_date > '$date_30d' AND b.exp_date <= '$date_90d'";
} elseif ($filter_status === 'safe') {
    $where[] = "b.exp_date > '$date_90d'";
}

if (!empty($search_query)) {
    $escaped_q = $conn->real_escape_string($search_query);
    $where[] = "(p.prdct_name LIKE '%$escaped_q%' OR b.batch_number LIKE '%$escaped_q%' OR p.prdct_company LIKE '%$escaped_q%')";
}

$where_sql = " WHERE " . implode(" AND ", $where);
$batches_sql = "SELECT b.*, p.prdct_name, p.prdct_company, p.prdct_img, c.cat_name 
                FROM tbl_product_batches b 
                JOIN tbl_products p ON b.prdct_id = p.prdct_id 
                LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id 
                $where_sql 
                ORDER BY b.exp_date ASC";
$batches_res = $conn->query($batches_sql);
$batches = [];
if ($batches_res && $batches_res->num_rows > 0) {
    while ($r = $batches_res->fetch_assoc()) {
        $batches[] = $r;
    }
}

// Fetch all products for Add Batch dropdown
$prod_list_res = $conn->query("SELECT prdct_id, prdct_name, prdct_price, stock_quantity FROM tbl_products WHERE pharmacy_id = $pharmacy_id ORDER BY prdct_name ASC");
$all_products = [];
if ($prod_list_res && $prod_list_res->num_rows > 0) {
    while ($p = $prod_list_res->fetch_assoc()) {
        $all_products[] = $p;
    }
}
?>

<link rel="stylesheet" href="css/product.css">
<style>
.batch-page-wrapper {
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
  transition: all 0.2s ease;
  text-decoration: none;
  color: inherit;
}
.kpi-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.08);
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
.kpi-icon.danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }
.kpi-icon.warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.kpi-icon.caution { background: rgba(234, 179, 8, 0.12); color: #eab308; }
.kpi-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.kpi-icon.primary { background: rgba(99, 102, 241, 0.12); color: #6366f1; }

.kpi-num {
  font-size: 24px;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
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
  display: inline-flex;
  align-items: center;
  gap: 6px;
}
.filter-tab:hover {
  background: #e2e8f0;
  color: #1e293b;
}
.filter-tab.active {
  background: #0f172a;
  color: #ffffff;
}

.badge-exp-critical {
  background: rgba(239, 68, 68, 0.12);
  color: #dc2626;
  border: 1px solid rgba(239, 68, 68, 0.3);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.badge-exp-warning {
  background: rgba(245, 158, 11, 0.12);
  color: #d97706;
  border: 1px solid rgba(245, 158, 11, 0.3);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.badge-exp-safe {
  background: rgba(16, 185, 129, 0.12);
  color: #059669;
  border: 1px solid rgba(16, 185, 129, 0.3);
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.badge-expired {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #f87171;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  animation: pulse-danger 2s infinite;
}
@keyframes pulse-danger {
  0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
  70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
  100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

/* Modal styling */
.custom-modal {
  display: none;
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.custom-modal.open {
  display: flex;
}
.modal-card {
  background: #ffffff;
  border-radius: 16px;
  max-width: 580px;
  width: 100%;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  animation: modalIn 0.25s ease-out;
}
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
.modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-header h3 {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
}
.modal-close {
  background: transparent;
  border: none;
  font-size: 22px;
  color: #64748b;
  cursor: pointer;
}
.modal-body {
  padding: 24px;
}
</style>

<div class="batch-page-wrapper">

    <!-- Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-barcode-reader' style="color: #059669;"></i> Batch & Expiry Date Intelligence
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                FEFO (First Expired, First Out) inventory tracking, near-expiry alerts, and batch allocation.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="openModal('addBatchModal')" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                <i class='bx bx-plus-circle'></i> Add Stock Batch
            </button>
        </div>
    </div>

    <!-- Alert Message -->
    <?php if (!empty($msg)): ?>
        <div style="padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; <?php echo $msg_type === 'success' ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
            <i class='bx <?php echo $msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>' style="font-size: 20px;"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Cards -->
    <div class="kpi-grid">
        <a href="batch_management.php?status=all" class="kpi-card">
            <div class="kpi-icon primary"><i class='bx bx-layer'></i></div>
            <div>
                <div class="kpi-num"><?php echo number_format($cnt_total); ?></div>
                <div class="kpi-label">Active Batches</div>
            </div>
        </a>

        <a href="batch_management.php?status=expired" class="kpi-card" style="<?php echo $cnt_expired > 0 ? 'border-color: #fca5a5; background: #fff5f5;' : ''; ?>">
            <div class="kpi-icon danger"><i class='bx bx-alarm-exclamation'></i></div>
            <div>
                <div class="kpi-num" style="<?php echo $cnt_expired > 0 ? 'color: #dc2626;' : ''; ?>"><?php echo number_format($cnt_expired); ?></div>
                <div class="kpi-label">Expired Items</div>
            </div>
        </a>

        <a href="batch_management.php?status=critical" class="kpi-card" style="<?php echo $cnt_critical > 0 ? 'border-color: #fdba74;' : ''; ?>">
            <div class="kpi-icon warning"><i class='bx bx-time-five'></i></div>
            <div>
                <div class="kpi-num" style="<?php echo $cnt_critical > 0 ? 'color: #ea580c;' : ''; ?>"><?php echo number_format($cnt_critical); ?></div>
                <div class="kpi-label">Expiring ≤ 30 Days</div>
            </div>
        </a>

        <a href="batch_management.php?status=warning" class="kpi-card">
            <div class="kpi-icon caution"><i class='bx bx-calendar-exclamation'></i></div>
            <div>
                <div class="kpi-num" style="color: #ca8a04;"><?php echo number_format($cnt_warning); ?></div>
                <div class="kpi-label">Expiring ≤ 90 Days</div>
            </div>
        </a>

        <div class="kpi-card">
            <div class="kpi-icon success"><i class='bx bx-shield-quarter'></i></div>
            <div>
                <div class="kpi-num" style="color: #059669;"><?php echo number_format($cnt_safe); ?></div>
                <div class="kpi-label">Safe & Good (>90d)</div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="filter-bar-box">
        <div class="filter-tabs">
            <a href="batch_management.php?status=all" class="filter-tab <?php echo $filter_status === 'all' ? 'active' : ''; ?>">
                All Batches (<?php echo $cnt_total; ?>)
            </a>
            <a href="batch_management.php?status=critical" class="filter-tab <?php echo $filter_status === 'critical' ? 'active' : ''; ?>" style="<?php echo $filter_status === 'critical' ? 'background: #ea580c;' : ''; ?>">
                <i class='bx bxs-error-circle'></i> Critical ≤30d (<?php echo $cnt_critical; ?>)
            </a>
            <a href="batch_management.php?status=warning" class="filter-tab <?php echo $filter_status === 'warning' ? 'active' : ''; ?>" style="<?php echo $filter_status === 'warning' ? 'background: #ca8a04;' : ''; ?>">
                Warning ≤90d (<?php echo $cnt_warning; ?>)
            </a>
            <a href="batch_management.php?status=expired" class="filter-tab <?php echo $filter_status === 'expired' ? 'active' : ''; ?>" style="<?php echo $filter_status === 'expired' ? 'background: #dc2626;' : ''; ?>">
                Expired (<?php echo $cnt_expired; ?>)
            </a>
            <a href="batch_management.php?status=safe" class="filter-tab <?php echo $filter_status === 'safe' ? 'active' : ''; ?>" style="<?php echo $filter_status === 'safe' ? 'background: #059669;' : ''; ?>">
                Safe (<?php echo $cnt_safe; ?>)
            </a>
        </div>

        <form method="GET" action="batch_management.php" style="display: flex; gap: 8px; flex: 1; max-width: 380px;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
            <div style="position: relative; width: 100%;">
                <i class='bx bx-search' style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px;"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search medicine, batch #..." style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none;">
            </div>
            <button type="submit" style="background: #0f172a; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="batch_management.php?status=<?php echo htmlspecialchars($filter_status); ?>" style="background: #e2e8f0; color: #475569; padding: 8px 12px; border-radius: 8px; font-size: 13px; text-decoration: none; display: flex; align-items: center;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Batches Table -->
    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 14px 18px;">Product & Manufacturer</th>
                        <th style="padding: 14px 18px;">Batch Number</th>
                        <th style="padding: 14px 18px;">Mfg Date</th>
                        <th style="padding: 14px 18px;">Expiry Date</th>
                        <th style="padding: 14px 18px;">Remaining Time</th>
                        <th style="padding: 14px 18px;">Available Qty</th>
                        <th style="padding: 14px 18px;">MRP Rate</th>
                        <th style="padding: 14px 18px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody style="divide-y: 1px solid #e2e8f0;">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="8" style="padding: 48px 20px; text-align: center; color: #94a3b8;">
                                <i class='bx bx-package' style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                <div style="font-size: 15px; font-weight: 600; color: #475569;">No Batches Found</div>
                                <div style="font-size: 13px; margin-top: 4px;">No batch inventory matches your active filter.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $b): 
                            $exp_ts = strtotime($b['exp_date']);
                            $today_ts = strtotime($today);
                            $diff_days = (int)ceil(($exp_ts - $today_ts) / (60 * 60 * 24));
                            $img_src = !empty($b['prdct_img']) ? 'medimg/' . $b['prdct_img'] : 'img/product-default.png';
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <td style="padding: 14px 18px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="" style="width: 38px; height: 38px; border-radius: 8px; object-fit: cover; background: #f1f5f9; border: 1px solid #e2e8f0;">
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($b['prdct_name']); ?></div>
                                            <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($b['prdct_company'] ?? 'Generic'); ?> &bull; <span style="color: #059669;"><?php echo htmlspecialchars($b['cat_name'] ?? ''); ?></span></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <span style="font-family: monospace; font-weight: 700; background: #f1f5f9; color: #0f172a; padding: 3px 8px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                        <?php echo htmlspecialchars($b['batch_number']); ?>
                                    </span>
                                </td>
                                <td style="padding: 14px 18px; color: #64748b;">
                                    <?php echo date('M d, Y', strtotime($b['mfg_date'])); ?>
                                </td>
                                <td style="padding: 14px 18px; font-weight: 600; color: #1e293b;">
                                    <?php echo date('M d, Y', strtotime($b['exp_date'])); ?>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <?php if ($diff_days < 0): ?>
                                        <span class="badge-expired"><i class='bx bx-x-circle'></i> Expired <?php echo abs($diff_days); ?>d ago</span>
                                    <?php elseif ($diff_days <= 30): ?>
                                        <span class="badge-exp-critical"><i class='bx bx-alarm-exclamation'></i> <?php echo $diff_days; ?> days left</span>
                                    <?php elseif ($diff_days <= 90): ?>
                                        <span class="badge-exp-warning"><i class='bx bx-time'></i> <?php echo $diff_days; ?> days left</span>
                                    <?php else: ?>
                                        <span class="badge-exp-safe"><i class='bx bx-check-circle'></i> <?php echo $diff_days; ?> days left</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 700; color: #0f172a;"><?php echo (int)$b['quantity']; ?> units</div>
                                    <div style="width: 80px; height: 5px; background: #e2e8f0; border-radius: 3px; margin-top: 4px; overflow: hidden;">
                                        <?php $pct = min(100, max(5, ($b['quantity'] / max(1, $b['initial_quantity'])) * 100)); ?>
                                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: <?php echo $b['quantity'] <= 10 ? '#ef4444' : '#10b981'; ?>;"></div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px; font-weight: 700; color: #0f172a;">
                                    रु. <?php echo number_format($b['mrp_price'], 2); ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: right;">
                                    <button onclick="openAdjustModal(<?php echo (int)$b['batch_id']; ?>, '<?php echo htmlspecialchars($b['batch_number'], ENT_QUOTES); ?>', <?php echo (int)$b['quantity']; ?>)" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class='bx bx-slider-alt'></i> Adjust
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Batch -->
<div id="addBatchModal" class="custom-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class='bx bx-plus-circle' style="color: #059669;"></i> Add New Medicine Stock Batch</h3>
            <button class="modal-close" onclick="closeModal('addBatchModal')">&times;</button>
        </div>
        <form method="POST" action="batch_management.php">
            <div class="modal-body">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Select Product <span style="color: #ef4444;">*</span></label>
                    <select name="prdct_id" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="" disabled selected>Choose a catalog medicine...</option>
                        <?php foreach ($all_products as $prod): ?>
                            <option value="<?php echo $prod['prdct_id']; ?>">
                                <?php echo htmlspecialchars($prod['prdct_name']); ?> (Current Total Stock: <?php echo (int)$prod['stock_quantity']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Batch Number <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="batch_number" value="BAT-<?php echo strtoupper(substr(uniqid(), -6)); ?>" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-family: monospace;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Batch Quantity (Units) <span style="color: #ef4444;">*</span></label>
                        <input type="number" name="quantity" min="1" value="50" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Manufacturing Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="mfg_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Expiry Date <span style="color: #ef4444;">*</span></label>
                        <input type="date" name="exp_date" value="<?php echo date('Y-m-d', strtotime('+18 months')); ?>" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Purchase Cost (रु.)</label>
                        <input type="number" step="0.01" name="purchase_cost" placeholder="0.00" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">MRP Selling Rate (रु.)</label>
                        <input type="number" step="0.01" name="mrp_price" placeholder="Leave empty for product rate" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('addBatchModal')" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="btnAddBatch" style="background: #059669; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Save Batch</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Adjust / Dispose Batch -->
<div id="adjustBatchModal" class="custom-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class='bx bx-slider-alt' style="color: #6366f1;"></i> Adjust Stock Batch Quantity</h3>
            <button class="modal-close" onclick="closeModal('adjustBatchModal')">&times;</button>
        </div>
        <form method="POST" action="batch_management.php">
            <input type="hidden" name="batch_id" id="modalBatchId">
            <div class="modal-body">
                <div style="background: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #64748b;">Batch Reference:</div>
                    <div style="font-size: 16px; font-weight: 700; color: #0f172a; font-family: monospace;" id="modalBatchNum">BAT-XXXX</div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Adjustment Type</label>
                    <select name="adj_action" id="adjActionSelect" onchange="toggleDisposalMode(this.value)" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="set">Set New Physical Count</option>
                        <option value="dispose">Dispose / Mark as Damaged/Expired (Zero Qty)</option>
                    </select>
                </div>

                <div id="qtyInputWrapper" style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Updated Available Quantity</label>
                    <input type="number" name="adj_quantity" id="modalBatchQty" min="0" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; font-weight: 700;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('adjustBatchModal')" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="btnAdjustBatch" style="background: #6366f1; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Update Inventory</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
function openAdjustModal(id, batchNum, qty) {
    document.getElementById('modalBatchId').value = id;
    document.getElementById('modalBatchNum').textContent = batchNum;
    document.getElementById('modalBatchQty').value = qty;
    document.getElementById('adjActionSelect').value = 'set';
    document.getElementById('qtyInputWrapper').style.display = 'block';
    openModal('adjustBatchModal');
}
function toggleDisposalMode(val) {
    if (val === 'dispose') {
        document.getElementById('qtyInputWrapper').style.display = 'none';
        document.getElementById('modalBatchQty').removeAttribute('required');
    } else {
        document.getElementById('qtyInputWrapper').style.display = 'block';
        document.getElementById('modalBatchQty').setAttribute('required', 'required');
    }
}
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('custom-modal')) {
        e.target.classList.remove('open');
    }
});
</script>
