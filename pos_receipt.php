<?php
require_once 'config.php';

$active_admin_pharmacy_id = require_admin_tenant();
$conn = get_db_connection();

$invoice_no = isset($_GET['inv']) ? trim($_GET['inv']) : '';
$sale = null;
$items = [];

if (!empty($invoice_no)) {
    $stmt = $conn->prepare("SELECT * FROM tbl_pos_sales WHERE invoice_no = ? AND pharmacy_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $invoice_no, $active_admin_pharmacy_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $sale = $res->fetch_assoc();
        }
        $stmt->close();
    }

    if ($sale) {
        $sale_id = (int)$sale['sale_id'];
        $items_res = $conn->query("SELECT * FROM tbl_pos_items WHERE sale_id = $sale_id");
        if ($items_res && $items_res->num_rows > 0) {
            while ($it = $items_res->fetch_assoc()) {
                $items[] = $it;
            }
        }
    }
}

$pharmacy = get_pharmacy_details($active_admin_pharmacy_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - <?php echo htmlspecialchars($invoice_no); ?></title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto;
        }
        body {
            background-color: #f1f5f9;
            color: #000000;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .receipt-container {
            width: 80mm;
            background: #ffffff;
            padding: 16px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            font-size: 12px;
            line-height: 1.35;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #333333;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .store-name {
            font-size: 15px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .store-info {
            font-size: 11px;
            margin-top: 3px;
        }
        .invoice-title {
            margin-top: 6px;
            font-weight: 800;
            font-size: 13px;
            text-decoration: underline;
        }
        .meta-table {
            width: 100%;
            font-size: 11px;
            margin-bottom: 10px;
            border-bottom: 1px dashed #333333;
            padding-bottom: 6px;
        }
        .meta-table td {
            padding: 2px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 8px;
        }
        .items-table th {
            border-bottom: 1px solid #000000;
            border-top: 1px solid #000000;
            padding: 4px 0;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .item-batch {
            font-size: 9px;
            color: #333333;
            display: block;
        }
        .totals-table {
            width: 100%;
            font-size: 11px;
            border-top: 1px dashed #333333;
            padding-top: 6px;
            margin-bottom: 10px;
        }
        .totals-table td {
            padding: 2px 0;
        }
        .grand-total-row td {
            font-size: 13px;
            font-weight: 800;
            border-top: 1px solid #000000;
            border-bottom: 1px solid #000000;
            padding: 4px 0;
        }
        .receipt-footer {
            text-align: center;
            font-size: 10px;
            border-top: 1px dashed #333333;
            padding-top: 8px;
            margin-top: 6px;
        }
        .no-print-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f172a;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            display: flex;
            gap: 12px;
            z-index: 999;
        }
        .btn-action {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-secondary {
            background: #334155;
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }
            .receipt-container {
                width: 100%;
                box-shadow: none;
                padding: 4px;
            }
            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<?php if (!$sale): ?>
    <div style="text-align: center; padding: 40px; background: #ffffff; border-radius: 12px;">
        <h2>Invoice Not Found</h2>
        <p style="margin-top: 8px; color: #64748b;">The requested POS receipt could not be retrieved.</p>
        <a href="pos.php" class="btn-action" style="margin-top: 16px;">Return to POS</a>
    </div>
<?php else: ?>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="store-name"><?php echo htmlspecialchars($pharmacy['name'] ?? 'MedLife Pharmacy'); ?></div>
            <div class="store-info"><?php echo htmlspecialchars($pharmacy['address'] ?? 'Kathmandu, Nepal'); ?></div>
            <div class="store-info">Tel: <?php echo htmlspecialchars($pharmacy['phone'] ?? ''); ?></div>
            <div class="store-info" style="font-weight: 700;">PAN / VAT: <?php echo htmlspecialchars($pharmacy['pan_number'] ?? '609823145'); ?></div>
            <div class="invoice-title">TAX INVOICE / CASH MEMO</div>
        </div>

        <!-- Meta -->
        <table class="meta-table">
            <tr>
                <td><strong>Inv No:</strong> <?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                <td style="text-align: right;"><strong>Date:</strong> <?php echo date('d-m-Y H:i', strtotime($sale['sale_date'])); ?></td>
            </tr>
            <tr>
                <td><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name'] ?? 'Staff'); ?></td>
                <td style="text-align: right;"><strong>Pay Mode:</strong> <?php echo htmlspecialchars($sale['payment_method']); ?></td>
            </tr>
            <tr>
                <td colspan="2"><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?><?php if (!empty($sale['customer_pan'])) echo ' (PAN: ' . htmlspecialchars($sale['customer_pan']) . ')'; ?></td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Item / Batch</th>
                    <th style="width: 15%; text-align: center;">Qty</th>
                    <th style="width: 20%; text-align: right;">Rate</th>
                    <th style="width: 20%; text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['prdct_name']); ?></strong>
                            <?php if (!empty($item['batch_number'])): ?>
                                <span class="item-batch">Batch: <?php echo htmlspecialchars($item['batch_number']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;"><?php echo (int)$item['quantity']; ?></td>
                        <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td style="text-align: right; font-weight: 700;"><?php echo number_format($item['item_total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <table class="totals-table">
            <tr>
                <td>Gross Subtotal:</td>
                <td style="text-align: right;">रु. <?php echo number_format($sale['subtotal'], 2); ?></td>
            </tr>
            <?php if ($sale['discount_amount'] > 0): ?>
                <tr>
                    <td>Discount (<?php echo (float)$sale['discount_percent']; ?>%):</td>
                    <td style="text-align: right;">- रु. <?php echo number_format($sale['discount_amount'], 2); ?></td>
                </tr>
            <?php endif; ?>
            <?php if ($sale['tax_amount'] > 0): ?>
                <tr>
                    <td>VAT (<?php echo (float)$sale['tax_percent']; ?>%):</td>
                    <td style="text-align: right;">+ रु. <?php echo number_format($sale['tax_amount'], 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr class="grand-total-row">
                <td>NET PAYABLE:</td>
                <td style="text-align: right;">रु. <?php echo number_format($sale['grand_total'], 2); ?></td>
            </tr>
            <?php if ($sale['payment_method'] === 'Cash' && $sale['tendered_amount'] > 0): ?>
                <tr>
                    <td>Cash Tendered:</td>
                    <td style="text-align: right;">रु. <?php echo number_format($sale['tendered_amount'], 2); ?></td>
                </tr>
                <tr>
                    <td>Change Return:</td>
                    <td style="text-align: right; font-weight: 700;">रु. <?php echo number_format($sale['change_amount'], 2); ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <!-- QR verification & footer -->
        <div class="receipt-footer">
            <div>*** THANK YOU FOR VISITING ***</div>
            <div style="font-size: 9px; margin-top: 3px;">Medicines once sold cannot be returned without original cash memo. Keep refrigerated medicines below 25°C.</div>
            <div style="margin-top: 6px; font-weight: 700;">Powered by MedLife SaaS</div>
        </div>
    </div>

    <!-- Floating Action Toolbar -->
    <div class="no-print-bar">
        <button onclick="window.print()" class="btn-action">
            <i class='bx bx-printer'></i> Print Receipt
        </button>
        <a href="pos.php" class="btn-secondary">
            <i class='bx bx-plus'></i> New Bill (POS)
        </a>
        <a href="pos_history.php" class="btn-secondary">
            <i class='bx bx-history'></i> History
        </a>
    </div>

    <script>
        // Auto trigger print if requested in URL
        <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
            window.onload = function() {
                window.print();
            };
        <?php endif; ?>
    </script>
<?php endif; ?>

</body>
</html>
