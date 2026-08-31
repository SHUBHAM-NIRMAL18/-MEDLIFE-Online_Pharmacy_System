<?php
/**
 * admin_payment_slip.php
 * Standalone Official Payment Slip & Transaction Receipt for Admin
 */

require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$active_admin_pharmacy_id = require_admin_tenant();
$conn = get_db_connection();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0 && isset($_GET['id'])) {
    $order_id = (int)$_GET['id'];
}

if ($order_id <= 0) {
    die("Invalid Order or Payment ID.");
}

// Fetch order scoped to pharmacy tenant
$stmt = $conn->prepare("SELECT o.*, d.name AS driver_name, d.phone AS driver_phone, d.vehicle_number 
                        FROM tbl_order o 
                        LEFT JOIN tbl_delivery_drivers d ON o.driver_id = d.driver_id 
                        WHERE o.order_id = ? AND o.pharmacy_id = ?");
if (!$stmt) {
    die("Database query error.");
}
$stmt->bind_param("ii", $order_id, $active_admin_pharmacy_id);
$stmt->execute();
$order_res = $stmt->get_result();

if (!$order_res || $order_res->num_rows === 0) {
    die("Payment slip not found or access denied.");
}
$order = $order_res->fetch_assoc();
$stmt->close();

$pharmacy = get_pharmacy_details($active_admin_pharmacy_id);

// Fetch order items
$items = [];
$items_stmt = $conn->prepare("SELECT * FROM tbl_orderitems WHERE order_id = ?");
if ($items_stmt) {
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    $items_res = $items_stmt->get_result();
    while ($it = $items_res->fetch_assoc()) {
        $items[] = $it;
    }
    $items_stmt->close();
}

$pay_mode = strtolower($order['payment'] ?? 'cod');
$pay_status = $order['payment_status'] ?? 'Pending';
$tx_id = !empty($order['transaction_id']) ? $order['transaction_id'] : 'N/A';
$delivery_fee = 100.00;
$items_subtotal = 0;
foreach ($items as $item) {
    $items_subtotal += ($item['price'] * $item['quantity']);
}
if ($order['total'] > 0 && ($order['total'] - $items_subtotal) > 0) {
    $delivery_fee = $order['total'] - $items_subtotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Slip #<?php echo $order['order_id']; ?> - <?php echo htmlspecialchars($pharmacy['name']); ?></title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --bg-slip: #ffffff;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f1f5f9;
            color: var(--text-dark);
            padding: 30px 15px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .slip-container {
            max-width: 780px;
            margin: 0 auto;
            background: var(--bg-slip);
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            overflow: hidden;
            position: relative;
        }

        /* Top Action Bar */
        .no-print-bar {
            background: #0f172a;
            color: #ffffff;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .no-print-bar .title {
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-print {
            background: #10b981;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #059669;
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }
        .btn-back:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        /* Slip Content */
        .slip-body {
            padding: 36px 40px;
            position: relative;
        }

        /* Slip Header */
        .slip-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 24px;
            margin-bottom: 24px;
        }

        .pharmacy-brand h1 {
            font-size: 22px;
            font-weight: 800;
            color: #059669;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
        }

        .pharmacy-brand .subtext {
            color: var(--text-muted);
            font-size: 12px;
        }

        .pharmacy-meta {
            margin-top: 8px;
            font-size: 12px;
            color: #475569;
            line-height: 1.4;
        }

        .slip-badge-box {
            text-align: right;
        }

        .slip-title-tag {
            display: inline-block;
            background: #0f172a;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 1px;
            padding: 5px 14px;
            border-radius: 6px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .slip-number {
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .slip-date {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* Gateway Banner Box */
        .gateway-banner {
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .gateway-banner.esewa {
            background: linear-gradient(135deg, rgba(96, 187, 70, 0.08) 0%, rgba(96, 187, 70, 0.18) 100%);
            border: 1.5px solid #60bb46;
        }

        .gateway-banner.khalti {
            background: linear-gradient(135deg, rgba(92, 45, 145, 0.08) 0%, rgba(92, 45, 145, 0.18) 100%);
            border: 1.5px solid #5c2d91;
        }

        .gateway-banner.cod {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.08) 0%, rgba(5, 150, 105, 0.18) 100%);
            border: 1.5px solid #059669;
        }

        .gateway-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .gateway-img-wrapper {
            width: 70px;
            height: 44px;
            background: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .gateway-img-wrapper img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .gateway-info .method-name {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }

        .gateway-info .tx-label {
            font-size: 12px;
            color: #475569;
            margin-top: 2px;
        }

        .gateway-info .tx-val {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: #0f172a;
            background: #ffffff;
            padding: 2px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            display: inline-block;
            margin-top: 2px;
        }

        .payment-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-paid {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 3px 10px rgba(5, 150, 105, 0.3);
        }

        .status-pending {
            background: #f59e0b;
            color: #ffffff;
        }

        .status-failed {
            background: #ef4444;
            color: #ffffff;
        }

        /* Two Column Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px;
        }

        .info-card h4 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-card p {
            font-size: 13px;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .info-card strong {
            color: #0f172a;
        }

        /* Table */
        .slip-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }

        .slip-table th {
            background: #f1f5f9;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 10px 14px;
            text-align: left;
            border-bottom: 1.5px solid #cbd5e1;
        }

        .slip-table th:last-child,
        .slip-table td:last-child {
            text-align: right;
        }

        .slip-table td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
            color: #1e293b;
        }

        .slip-table tbody tr:last-child td {
            border-bottom: 2px solid #cbd5e1;
        }

        /* Totals Grid */
        .summary-wrapper {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 30px;
        }

        .totals-box {
            width: 320px;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 13px;
            color: #475569;
        }

        .totals-row.grand-total {
            border-top: 2px solid #0f172a;
            margin-top: 8px;
            padding-top: 10px;
            font-size: 16px;
            font-weight: 800;
            color: #059669;
        }

        /* Official Stamp */
        .official-stamp {
            position: absolute;
            bottom: 40px;
            left: 45px;
            width: 130px;
            height: 130px;
            border: 3px dashed #059669;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #059669;
            transform: rotate(-12deg);
            opacity: 0.88;
            pointer-events: none;
            padding: 10px;
        }

        .official-stamp.stamp-cod {
            border-color: #0284c7;
            color: #0284c7;
        }

        .official-stamp.stamp-pending {
            border-color: #d97706;
            color: #d97706;
        }

        .official-stamp .stamp-title {
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .official-stamp .stamp-icon {
            font-size: 24px;
            margin: 2px 0;
        }

        .official-stamp .stamp-date {
            font-size: 9px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Footer */
        .slip-footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
            margin-top: 30px;
            font-size: 11.5px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .slip-container {
                box-shadow: none;
                border: none;
                max-width: 100%;
                border-radius: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .slip-body {
                padding: 20px 25px;
            }
        }
    </style>
</head>
<body>

<div class="slip-container">

    <!-- No-Print Admin Top Bar -->
    <div class="no-print-bar">
        <div class="title">
            <i class="bx bx-check-shield" style="color: #10b981; font-size: 18px;"></i>
            <span>Official Payment Slip — MedLife Pharmacy Systems</span>
        </div>
        <div class="btn-group">
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="bx bx-printer"></i> Print / Save Slip
            </button>
            <a href="admin_payments.php" class="btn-action btn-back">
                <i class="bx bx-arrow-back"></i> Back to Payment Logs
            </a>
        </div>
    </div>

    <div class="slip-body">

        <!-- Pharmacy Header -->
        <div class="slip-header">
            <div class="pharmacy-brand">
                <h1><i class="bx bx-plus-medical"></i> <?php echo htmlspecialchars($pharmacy['name']); ?></h1>
                <div class="subtext">Licensed Pharmacy & Healthcare Services</div>
                <div class="pharmacy-meta">
                    <div><strong>Address:</strong> <?php echo htmlspecialchars($pharmacy['address'] ?? 'Kathmandu, Nepal'); ?></div>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($pharmacy['phone'] ?? '+977-1-4400000'); ?> &middot; <strong>Email:</strong> <?php echo htmlspecialchars($pharmacy['email'] ?? 'support@medlife.com'); ?></div>
                    <div><strong>PAN/VAT No:</strong> <?php echo htmlspecialchars($pharmacy['pan_number'] ?? '600123456'); ?></div>
                </div>
            </div>
            <div class="slip-badge-box">
                <div class="slip-title-tag">Payment Slip</div>
                <div class="slip-number">SLIP-<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                <div class="slip-date">Date: <?php echo date("d M Y, h:i A", strtotime($order['created_at'])); ?></div>
            </div>
        </div>

        <!-- Payment Gateway Banner -->
        <div class="gateway-banner <?php echo $pay_mode; ?>">
            <div class="gateway-left">
                <div class="gateway-img-wrapper">
                    <?php if ($pay_mode === 'esewa'): ?>
                        <img src="img/esewa_logo.png" alt="eSewa">
                    <?php elseif ($pay_mode === 'khalti'): ?>
                        <img src="img/khalti_logo.png" alt="Khalti">
                    <?php else: ?>
                        <img src="img/cod_logo.png" alt="Cash on Delivery">
                    <?php endif; ?>
                </div>
                <div class="gateway-info">
                    <div class="method-name">
                        <?php 
                        if ($pay_mode === 'esewa') echo 'eSewa Mobile Wallet (ePay v2)';
                        elseif ($pay_mode === 'khalti') echo 'Khalti Digital Wallet (ePayment v2)';
                        else echo 'Cash on Delivery (COD)';
                        ?>
                    </div>
                    <div class="tx-label">
                        Tracking Ref: <span class="tx-val"><?php echo htmlspecialchars($order['tracking_order']); ?></span>
                        &nbsp;&middot;&nbsp;
                        Gateway Ref: <span class="tx-val"><?php echo htmlspecialchars($tx_id); ?></span>
                    </div>
                </div>
            </div>
            <div>
                <?php if (strcasecmp($pay_status, 'Paid') === 0 || strcasecmp($pay_status, 'COMPLETE') === 0): ?>
                    <span class="payment-status-badge status-paid">
                        <i class="bx bx-check-circle"></i> Paid Online
                    </span>
                <?php elseif (strcasecmp($pay_status, 'Pending') === 0): ?>
                    <span class="payment-status-badge status-pending">
                        <i class="bx bx-time-five"></i> Settlement Pending
                    </span>
                <?php else: ?>
                    <span class="payment-status-badge status-failed">
                        <i class="bx bx-x-circle"></i> <?php echo htmlspecialchars($pay_status); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Grid (Customer & Delivery) -->
        <div class="info-grid">
            <div class="info-card">
                <h4><i class="bx bx-user"></i> Customer Bill-To Details</h4>
                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['user_name']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><strong>Customer ID:</strong> #<?php echo htmlspecialchars($order['user_id']); ?></p>
            </div>
            <div class="info-card">
                <h4><i class="bx bx-map-pin"></i> Delivery & Dispatch Details</h4>
                <p><strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                <p><strong>Assigned Driver:</strong> <?php echo !empty($order['driver_name']) ? htmlspecialchars($order['driver_name']) . ' (' . htmlspecialchars($order['driver_phone'] ?? '') . ')' : 'Direct Pickup / Unassigned'; ?></p>
                <p><strong>Prescription:</strong> <?php echo !empty($order['prescription']) ? '<span style="color: #059669; font-weight: 700;">Verified Rx Attached</span>' : 'None Required / OTC'; ?></p>
            </div>
        </div>

        <!-- Table -->
        <table class="slip-table">
            <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th style="width: 52%;">Medicine / Product Item</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 12%; text-align: right;">Unit Price</th>
                    <th style="width: 13%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 1;
                foreach ($items as $it): 
                    $line_total = $it['price'] * $it['quantity'];
                ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($it['prdct_name']); ?></strong>
                    </td>
                    <td style="text-align: center;"><?php echo htmlspecialchars($it['quantity']); ?></td>
                    <td style="text-align: right;">रु. <?php echo number_format($it['price'], 2); ?></td>
                    <td>रु. <?php echo number_format($line_total, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals Summary -->
        <div class="summary-wrapper">
            <div class="totals-box">
                <div class="totals-row">
                    <span>Medicines Subtotal:</span>
                    <strong>रु. <?php echo number_format($items_subtotal, 2); ?></strong>
                </div>
                <div class="totals-row">
                    <span>Express Delivery Fee:</span>
                    <span>रु. <?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="totals-row grand-total">
                    <span>Total Settled:</span>
                    <span>रु. <?php echo number_format($order['total'], 2); ?></span>
                </div>
            </div>
        </div>

        <!-- Official Stamp -->
        <?php if (strcasecmp($pay_status, 'Paid') === 0 || strcasecmp($pay_status, 'COMPLETE') === 0): ?>
            <div class="official-stamp">
                <div class="stamp-title">MEDLIFE PHARMACY</div>
                <div class="stamp-icon"><i class="bx bx-check-circle"></i></div>
                <div class="stamp-title">VERIFIED & PAID</div>
                <div class="stamp-date"><?php echo date("Y-m-d H:i", strtotime($order['created_at'])); ?></div>
            </div>
        <?php elseif ($pay_mode === 'cod'): ?>
            <div class="official-stamp stamp-cod">
                <div class="stamp-title">MEDLIFE PHARMACY</div>
                <div class="stamp-icon"><i class="bx bx-money"></i></div>
                <div class="stamp-title">CASH ON DELIVERY</div>
                <div class="stamp-date"><?php echo date("Y-m-d", strtotime($order['created_at'])); ?></div>
            </div>
        <?php else: ?>
            <div class="official-stamp stamp-pending">
                <div class="stamp-title">MEDLIFE PHARMACY</div>
                <div class="stamp-icon"><i class="bx bx-time"></i></div>
                <div class="stamp-title">PENDING SETTLEMENT</div>
                <div class="stamp-date"><?php echo date("Y-m-d", strtotime($order['created_at'])); ?></div>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="slip-footer">
            <div>This is a computer-generated digital payment slip and requires no physical signature.</div>
            <div>Generated by MedLife SaaS Engine &middot; <?php echo date("Y-m-d H:i:s"); ?></div>
        </div>

    </div>

</div>

</body>
</html>
