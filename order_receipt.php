<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$order_id = 0;
$tracking = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = (int)$_GET['id'];
} elseif (isset($_GET['tracking'])) {
    $tracking = trim($_GET['tracking']);
}

$order = null;
if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE order_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) $order = $res->fetch_assoc();
        $stmt->close();
    }
} elseif (!empty($tracking)) {
    $stmt = $conn->prepare("SELECT * FROM tbl_order WHERE tracking_order = ?");
    if ($stmt) {
        $stmt->bind_param("s", $tracking);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) $order = $res->fetch_assoc();
        $stmt->close();
    }
}

// Fallback to most recent order if no parameter supplied
if (!$order) {
    $uid = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    if ($uid > 0) {
        $res = $conn->query("SELECT * FROM tbl_order WHERE user_id = $uid ORDER BY order_id DESC LIMIT 1");
        if ($res && $res->num_rows > 0) $order = $res->fetch_assoc();
    }
}

if (!$order) {
    header("Location: index.php");
    exit();
}

$order_id = (int)$order['order_id'];

// Fetch Order Items with product catalog name fallback
$order_items = [];
$items_res = $conn->query("SELECT i.*, p.prdct_name AS catalog_name FROM tbl_orderitems i LEFT JOIN tbl_products p ON (i.prdct_id = p.prdct_id OR i.prdct_name = p.prdct_id) WHERE i.order_id = $order_id");
if ($items_res && $items_res->num_rows > 0) {
    while ($it = $items_res->fetch_assoc()) {
        $name = '';
        if (!empty($it['catalog_name']) && $it['catalog_name'] !== '0' && !is_numeric($it['catalog_name'])) {
            $name = $it['catalog_name'];
        } elseif (!empty($it['prdct_name']) && $it['prdct_name'] !== '0' && !is_numeric($it['prdct_name'])) {
            $name = $it['prdct_name'];
        } else {
            $pid = (int)$it['prdct_id'];
            if ($pid === 0 && is_numeric($it['prdct_name']) && (int)$it['prdct_name'] > 0) {
                $pid = (int)$it['prdct_name'];
            }
            if ($pid > 0) {
                $lookup = $conn->query("SELECT prdct_name FROM tbl_products WHERE prdct_id = $pid");
                if ($lookup && $lookup->num_rows > 0) {
                    $p_data = $lookup->fetch_assoc();
                    $name = $p_data['prdct_name'];
                }
            }
        }
        if (empty($name) || $name === '0') {
            $name = 'Medicine Item #' . ($it['prdct_id'] > 0 ? $it['prdct_id'] : $order_id);
        }
        $it['prdct_display_name'] = $name;
        $order_items[] = $it;
    }
}

$page_title = "Pharmacy Tax Invoice #" . $order_id;
$page_css = "css/checkout.css";
include('header.php');
?>

<style>
/* Receipt Dedicated Styles */
.receipt-wrapper {
  max-width: 800px;
  margin: 30px auto;
  padding: 0 16px;
}

.receipt-top-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.receipt-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
  color: #0f172a;
}

.receipt-header-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding-bottom: 24px;
  border-bottom: 2px solid #059669;
  margin-bottom: 24px;
}

.receipt-logo img {
  height: 48px;
  width: auto;
}

.receipt-title-box {
  text-align: right;
}

.receipt-title-box h2 {
  font-size: 24px;
  font-weight: 800;
  color: #059669;
  margin: 0 0 4px;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.receipt-meta-line {
  font-size: 13px;
  color: #64748b;
  margin-bottom: 2px;
}

.receipt-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 30px;
  background: #f8fafc;
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #f1f5f9;
}

.info-block h4 {
  font-size: 13px;
  font-weight: 700;
  color: #059669;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin: 0 0 8px;
}

.info-block p {
  font-size: 13.5px;
  color: #334155;
  margin: 0 0 4px;
  line-height: 1.45;
}

.receipt-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 24px;
}

.receipt-table th {
  background: #f1f5f9;
  color: #334155;
  font-size: 12.5px;
  font-weight: 700;
  text-transform: uppercase;
  padding: 12px;
  text-align: left;
  border-bottom: 2px solid #cbd5e1;
}

.receipt-table td {
  padding: 12px;
  font-size: 13.5px;
  color: #334155;
  border-bottom: 1px solid #e2e8f0;
}

.receipt-summary-box {
  width: 320px;
  margin-left: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-top: 12px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
  color: #475569;
}

.summary-item.grand-total {
  font-size: 17px;
  font-weight: 800;
  color: #059669;
  border-top: 2px solid #059669;
  padding-top: 10px;
  margin-top: 6px;
}

.receipt-footer-stamp {
  margin-top: 40px;
  padding-top: 20px;
  border-top: 1px dashed #cbd5e1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}

.stamp-box {
  border: 2px dashed #059669;
  padding: 8px 16px;
  border-radius: 8px;
  color: #059669;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: flex;
  align-items: center;
  gap: 6px;
}

@media print {
  .site-header, .top-bar, footer, .no-print, .toast-container {
    display: none !important;
  }
  body {
    background: #ffffff !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .receipt-wrapper {
    max-width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .receipt-card {
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
  }
}
</style>

<main class="content-container">
    
    <?php if ($order['status'] != 1 && !isset($_SESSION['admin_login'])): ?>
        <div class="receipt-wrapper" style="min-height: 50vh; padding: 40px 16px;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 50px 30px; text-align: center; max-width: 540px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.03);">
                <i class="bx bx-receipt" style="font-size: 54px; color: #f59e0b; margin-bottom: 14px; display: block;"></i>
                <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Receipt Pending Order Completion</h3>
                <p style="color: #64748b; font-size: 14px; margin-bottom: 24px; line-height: 1.55;">
                    Official pharmacy tax receipts are generated only after your order is verified and marked <strong>Completed</strong> by the pharmacist.<br>
                    Current Order Status: <span style="font-weight: 600; color: #d97706;"><?php echo $order['status'] == 0 ? 'Under Processing' : 'Cancelled'; ?></span>
                </p>
                <a href="user_dashboard.php" class="btn btn-primary" style="padding: 10px 24px; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none;">
                    <i class="bx bx-arrow-back"></i> Back to Order History
                </a>
            </div>
        </div>
    <?php else: ?>

    <div class="receipt-wrapper">
        
        <!-- Top Action Toolbar (Hidden in Print) -->
        <div class="receipt-top-actions no-print">
            <a href="user_dashboard.php" class="btn btn-outline">
                <i class="bx bx-arrow-back"></i> Back to Order History
            </a>
            
            <div style="display: flex; gap: 10px;">
                <button onclick="window.print()" class="btn btn-primary" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; font-weight: 600;">
                    <i class="bx bx-printer" style="font-size: 18px;"></i> Print / Download PDF Receipt
                </button>
            </div>
        </div>

        <!-- Official Pharmacy Tax Receipt Card -->
        <div class="receipt-card">
            
            <!-- Header Row -->
            <div class="receipt-header-row">
                <div class="receipt-logo">
                    <img src="logo/MEDLOGO.png" alt="Medlife Pharmacy">
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Medlife Care Pharmacy Ltd.</div>
                </div>

                <div class="receipt-title-box">
                    <h2>OFFICIAL RECEIPT</h2>
                    <div class="receipt-meta-line"><strong>Receipt #:</strong> REC-<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                    <div class="receipt-meta-line"><strong>Tracking Ref:</strong> <span style="font-family: monospace; font-weight: 700; color: #059669;"><?php echo htmlspecialchars($order['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></span></div>
                    <div class="receipt-meta-line"><strong>Date:</strong> <?php echo date("F d, Y, g:i A", strtotime($order['created_at'])); ?></div>
                </div>
            </div>

            <!-- Customer & Pharmacy Info Grid -->
            <div class="receipt-info-grid">
                <div class="info-block">
                    <h4>Pharmacy Supplier</h4>
                    <p><strong>Medlife Care Pharmacy</strong></p>
                    <p>Kathmandu, Nepal</p>
                    <p><strong>PAN Reg No:</strong> 609823145</p>
                    <p><strong>Phone:</strong> +977 1-4228999</p>
                </div>

                <div class="info-block">
                    <h4>Billed To Customer</h4>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['user_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($order['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Payment Mode:</strong> <span style="text-transform: uppercase; font-weight: 600; color: #059669;"><?php echo htmlspecialchars($order['payment'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                </div>
            </div>

            <!-- Itemized Table -->
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Item Description</th>
                        <th style="text-align: center; width: 60px;">Qty</th>
                        <th style="text-align: right; width: 120px;">Unit Price</th>
                        <th style="text-align: right; width: 130px;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    if (!empty($order_items)):
                        foreach ($order_items as $idx => $it):
                            $line = $it['price'] * $it['quantity'];
                            $subtotal += $line;
                    ?>
                            <tr>
                                <td><?php echo $idx + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars(!empty($it['prdct_display_name']) ? $it['prdct_display_name'] : $it['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td style="text-align: center;"><?php echo $it['quantity']; ?></td>
                                <td style="text-align: right;">रु. <?php echo number_format($it['price'], 2); ?></td>
                                <td style="text-align: right; font-weight: 600;">रु. <?php echo number_format($line, 2); ?></td>
                            </tr>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </tbody>
            </table>

            <!-- Summary Totals Box -->
            <div class="receipt-summary-box">
                <div class="summary-item">
                    <span>Items Subtotal</span>
                    <span>रु. <?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Delivery Charge</span>
                    <span>रु. 100.00</span>
                </div>
                <div class="summary-item grand-total">
                    <span>Grand Total Paid</span>
                    <span>रु. <?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>

            <!-- Stamp & Authorization Footer -->
            <div class="receipt-footer-stamp">
                <div class="stamp-box">
                    <i class="bx bx-check-shield"></i> Official Pharmacy Verified Dispatch
                </div>
                <div style="font-size: 12px; color: #64748b; text-align: right;">
                    Thank you for choosing Medlife Care! For inquiries call +977 1-4228999
                </div>
            </div>

        </div>

    </div>
    <?php endif; ?>

</main>

<?php include('footer.php'); ?>
