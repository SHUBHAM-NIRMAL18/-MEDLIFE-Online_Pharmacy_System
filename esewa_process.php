<?php
/**
 * esewa_process.php
 * Auto-submits eSewa ePay v2 payment request form with HMAC-SHA256 signature
 */

require_once 'config.php';
require_once 'includes/PaymentGateways.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    header("Location: checkout.php");
    exit();
}

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT * FROM tbl_order WHERE order_id = ?");
if (!$stmt) {
    die("Database query error.");
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_res = $stmt->get_result();
$order = $order_res ? $order_res->fetch_assoc() : null;
$stmt->close();

if (!$order) {
    header("Location: checkout.php");
    exit();
}

$grand_total = (float)$order['total'];
$pharmacy_details = get_pharmacy_details($order['pharmacy_id'] ?? 1);
$delivery_charge = isset($pharmacy_details['delivery_fee']) ? (float)$pharmacy_details['delivery_fee'] : 100.00;
$product_amount = $grand_total - $delivery_charge;
if ($product_amount < 0) {
    $product_amount = $grand_total;
    $delivery_charge = 0;
}

$esewa_cfg = PaymentGateways::getEsewaConfig();
$base_url = PaymentGateways::getBaseUrl();

// Unique transaction UUID format (e.g., MEDLIFE-12-1725187263)
$transaction_uuid = "MEDLIFE-" . $order['order_id'] . "-" . time();

// Update order with transaction UUID so callback can match it
$up_stmt = $conn->prepare("UPDATE tbl_order SET transaction_id = ? WHERE order_id = ?");
if ($up_stmt) {
    $up_stmt->bind_param("si", $transaction_uuid, $order['order_id']);
    $up_stmt->execute();
    $up_stmt->close();
}

// Generate eSewa v2 signature
$signed_field_names = "total_amount,transaction_uuid,product_code";
$signature = PaymentGateways::generateEsewaSignature(
    number_format($grand_total, 2, '.', ''),
    $transaction_uuid,
    $esewa_cfg['merchant_code'],
    $esewa_cfg['secret_key']
);

$success_url = $base_url . "/esewa_success.php";
$failure_url = $base_url . "/esewa_failure.php?order_id=" . $order['order_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to eSewa Payment Gateway - MedLife</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --esewa-green: #60bb46;
            --esewa-dark: #438f2f;
            --bg-dark: #0f172a;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            padding: 20px;
        }
        .redirect-card {
            background: rgba(30, 41, 59, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            border-radius: 20px;
            padding: 40px 32px;
            width: 100%;
            max-width: 440px;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .esewa-badge-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 76px;
            height: 76px;
            background: #ffffff;
            border-radius: 50%;
            margin-bottom: 24px;
            box-shadow: 0 0 30px rgba(96, 187, 70, 0.4);
            position: relative;
        }
        .esewa-badge-logo::after {
            content: '';
            position: absolute;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 2px dashed #60bb46;
            animation: rotate 6s linear infinite;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .esewa-icon-text {
            font-size: 28px;
            font-weight: 900;
            color: #60bb46;
            letter-spacing: -1px;
        }
        h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #ffffff;
        }
        p.subtitle {
            font-size: 13.5px;
            color: #94a3b8;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .amount-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(96, 187, 70, 0.3);
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .amount-box span {
            font-size: 13px;
            color: #cbd5e1;
        }
        .amount-box strong {
            font-size: 18px;
            color: #60bb46;
            font-weight: 800;
        }
        .spinner-bar-track {
            height: 6px;
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 999px;
            overflow: hidden;
            margin-bottom: 20px;
            position: relative;
        }
        .spinner-bar-fill {
            height: 100%;
            width: 40%;
            background: linear-gradient(90deg, #60bb46, #a3e635);
            border-radius: 999px;
            animation: slide 1.2s ease-in-out infinite alternate;
        }
        @keyframes slide {
            from { transform: translateX(-50%); }
            to { transform: translateX(200%); }
        }
        .footer-note {
            font-size: 12px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>

    <div class="redirect-card">
        <div class="esewa-badge-logo">
            <span class="esewa-icon-text">e</span>
        </div>

        <h2>Connecting to eSewa...</h2>
        <p class="subtitle">Please wait while we securely transfer you to eSewa to complete your medicine order payment.</p>

        <div class="amount-box">
            <span>Amount Payable</span>
            <strong>रु. <?php echo number_format($grand_total, 2); ?></strong>
        </div>

        <div class="spinner-bar-track">
            <div class="spinner-bar-fill"></div>
        </div>

        <div class="footer-note">
            <i class="bx bx-shield-quarter" style="color: #60bb46; font-size: 16px;"></i> 256-bit Encrypted Payment Gateway
        </div>

        <!-- Hidden eSewa Auto-Submit Form -->
        <form id="esewaForm" action="<?php echo htmlspecialchars($esewa_cfg['form_url'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" style="display: none;">
            <input type="hidden" name="amount" value="<?php echo number_format($product_amount, 2, '.', ''); ?>">
            <input type="hidden" name="tax_amount" value="0">
            <input type="hidden" name="total_amount" value="<?php echo number_format($grand_total, 2, '.', ''); ?>">
            <input type="hidden" name="transaction_uuid" value="<?php echo htmlspecialchars($transaction_uuid, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="product_code" value="<?php echo htmlspecialchars($esewa_cfg['merchant_code'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="product_service_charge" value="0">
            <input type="hidden" name="product_delivery_charge" value="<?php echo number_format($delivery_charge, 2, '.', ''); ?>">
            <input type="hidden" name="success_url" value="<?php echo htmlspecialchars($success_url, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="failure_url" value="<?php echo htmlspecialchars($failure_url, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="signed_field_names" value="<?php echo htmlspecialchars($signed_field_names, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="signature" value="<?php echo htmlspecialchars($signature, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit">Submit</button>
        </form>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('esewaForm').submit();
            }, 600);
        });
    </script>
</body>
</html>
