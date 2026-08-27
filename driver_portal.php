<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure driver is authenticated
if (!isset($_SESSION['driver_login']) || empty($_SESSION['driver_id'])) {
    header('Location: driver_login.php');
    exit();
}

$conn = get_db_connection();
$driver_id = (int)$_SESSION['driver_id'];
$pharmacy_id = (int)$_SESSION['driver_pharmacy_id'];

$toast = '';
$toast_type = '';

// Handle Driver Logout
if (isset($_GET['logout'])) {
    unset($_SESSION['driver_login'], $_SESSION['driver_id'], $_SESSION['driver_name'], $_SESSION['driver_phone'], $_SESSION['driver_email'], $_SESSION['driver_vehicle'], $_SESSION['driver_pharmacy_id'], $_SESSION['driver_pharmacy_name']);
    header('Location: driver_login.php');
    exit();
}

// Handle Order Picked Up Action (Status 3 -> 4)
if (isset($_POST['btnPickupOrder'])) {
    $odr_id = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE tbl_order SET status = 4 WHERE order_id = ? AND driver_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $odr_id, $driver_id);
        $stmt->execute();
        $stmt->close();
        $conn->query("UPDATE tbl_delivery_drivers SET status = 2 WHERE driver_id = $driver_id");
        $toast = "Order #$odr_id marked as Picked Up & Out for Delivery!";
        $toast_type = "success";
    }
}

// Handle Order Delivered Action (Status 4 -> 1)
if (isset($_POST['btnDeliverOrder'])) {
    $odr_id = (int)$_POST['order_id'];
    $proof_notes = trim($_POST['delivery_proof'] ?? '');
    
    $stmt = $conn->prepare("UPDATE tbl_order SET status = 1, delivered_at = NOW(), delivery_notes = CONCAT(IFNULL(delivery_notes, ''), '\n[Rider Note]: ', ?) WHERE order_id = ? AND driver_id = ?");
    if ($stmt) {
        $stmt->bind_param("sii", $proof_notes, $odr_id, $driver_id);
        $stmt->execute();
        $stmt->close();

        // Check if driver has any remaining active deliveries
        $chk_rem = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_order WHERE driver_id = $driver_id AND status IN (3, 4)");
        if ($chk_rem && (int)$chk_rem->fetch_assoc()['cnt'] === 0) {
            $conn->query("UPDATE tbl_delivery_drivers SET status = 1 WHERE driver_id = $driver_id");
        }

        $toast = "Order #$odr_id marked as Successfully Delivered!";
        $toast_type = "success";
    }
}

// Fetch Active Assigned Orders (Status 3 or 4)
$active_orders = [];
$act_sql = "SELECT o.*, p.name AS pharmacy_name, p.phone AS pharmacy_phone, p.address AS pharmacy_address 
            FROM tbl_order o 
            LEFT JOIN tbl_pharmacies p ON o.pharmacy_id = p.pharmacy_id 
            WHERE o.driver_id = $driver_id AND o.status IN (0, 3, 4) 
            ORDER BY FIELD(o.status, 4, 3, 0), o.order_id DESC";
$act_res = $conn->query($act_sql);
if ($act_res && $act_res->num_rows > 0) {
    while ($r = $act_res->fetch_assoc()) {
        $active_orders[] = $r;
    }
}

// Fetch Today's Completed Deliveries (Status 1)
$today_completed = [];
$today_cod_collected = 0.00;
$today_sql = "SELECT * FROM tbl_order 
              WHERE driver_id = $driver_id AND status = 1 AND DATE(delivered_at) = CURDATE() 
              ORDER BY delivered_at DESC";
$today_res = $conn->query($today_sql);
if ($today_res && $today_res->num_rows > 0) {
    while ($r = $today_res->fetch_assoc()) {
        $today_completed[] = $r;
        if (strtolower($r['payment']) === 'cash' || strtolower($r['payment']) === 'cod') {
            $today_cod_collected += (float)$r['total'];
        }
    }
}

// Active Tab
$tab = isset($_GET['tab']) && $_GET['tab'] === 'history' ? 'history' : 'active';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Courier Workspace - <?php echo htmlspecialchars($_SESSION['driver_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: #f8fafc;
            color: #0f172a;
            padding-bottom: 70px;
            -webkit-tap-highlight-color: transparent;
        }

        /* Top Header */
        .app-header {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .driver-profile-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .driver-avatar {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 12px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.25);
        }
        .driver-meta h2 { font-size: 15px; font-weight: 800; color: #0f172a; }
        .driver-meta p { font-size: 12px; color: #64748b; font-weight: 500; }
        .btn-logout {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Navigation Pills */
        .nav-pills {
            display: flex;
            background: #ffffff;
            padding: 8px 16px;
            border-bottom: 1px solid #e2e8f0;
            gap: 10px;
        }
        .nav-pill {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            color: #64748b;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .nav-pill.active {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25);
        }
        .badge-count {
            background: rgba(255, 255, 255, 0.25);
            color: inherit;
            padding: 1px 7px;
            border-radius: 10px;
            font-size: 11px;
        }
        .nav-pill:not(.active) .badge-count {
            background: #e2e8f0;
            color: #334155;
        }

        /* Main Container */
        .content-wrap {
            padding: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Order Cards */
        .order-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            position: relative;
        }
        .order-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .order-id { font-size: 15px; font-weight: 800; color: #0f172a; }
        .order-tracking { font-family: monospace; font-size: 12px; color: #64748b; }
        
        .status-pill {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .status-pill.out { background: #e0e7ff; color: #4338ca; }
        .status-pill.ready { background: #dbeafe; color: #1d4ed8; }
        .status-pill.done { background: #ecfdf5; color: #065f46; }

        .customer-info-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
        }
        .cust-name { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px; }
        .cust-address { font-size: 12.5px; color: #475569; line-height: 1.4; display: flex; align-items: flex-start; gap: 6px; }

        /* Quick Action Bar */
        .action-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }
        .btn-quick {
            padding: 10px;
            border-radius: 10px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            cursor: pointer;
        }
        .btn-call { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-maps { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }

        .payment-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fefce8;
            border: 1px solid #fef08a;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 13px;
        }
        .payment-banner strong { font-size: 15px; color: #854d0e; }

        .btn-primary-action {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            border: none;
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .btn-pickup { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .btn-deliver { background: linear-gradient(135deg, #059669 0%, #10b981 100%); }

        /* KPI Card */
        .stats-summary {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
            color: #ffffff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(6, 78, 59, 0.4);
        }
        .stat-item strong { display: block; font-size: 22px; font-weight: 800; }
        .stat-item span { font-size: 12px; opacity: 0.85; }

        /* Toast Alert */
        .toast-banner {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="app-header">
    <div class="driver-profile-info">
        <div class="driver-avatar"><i class='bx bx-cycling'></i></div>
        <div class="driver-meta">
            <h2><?php echo htmlspecialchars($_SESSION['driver_name']); ?></h2>
            <p><i class='bx bx-store'></i> <?php echo htmlspecialchars($_SESSION['driver_pharmacy_name']); ?></p>
        </div>
    </div>
    <a href="driver_portal.php?logout=1" class="btn-logout">
        <i class='bx bx-log-out'></i> Logout
    </a>
</header>

<!-- Navigation Tabs -->
<div class="nav-pills">
    <a href="driver_portal.php?tab=active" class="nav-pill <?php echo $tab === 'active' ? 'active' : ''; ?>">
        <i class='bx bx-package'></i> Assigned Deliveries
        <span class="badge-count"><?php echo count($active_orders); ?></span>
    </a>
    <a href="driver_portal.php?tab=history" class="nav-pill <?php echo $tab === 'history' ? 'active' : ''; ?>">
        <i class='bx bx-check-double'></i> Completed Today
        <span class="badge-count"><?php echo count($today_completed); ?></span>
    </a>
</div>

<main class="content-wrap">

    <?php if (!empty($toast)): ?>
        <div class="toast-banner">
            <i class='bx bx-check-circle' style="font-size: 18px;"></i>
            <span><?php echo htmlspecialchars($toast); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'active'): ?>

        <?php if (empty($active_orders)): ?>
            <div style="text-align: center; padding: 48px 20px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 10px;">
                <i class='bx bx-coffee' style="font-size: 48px; color: #10b981; margin-bottom: 12px; display: block;"></i>
                <h3 style="font-size: 17px; font-weight: 800; color: #0f172a;">All Caught Up!</h3>
                <p style="font-size: 13px; color: #64748b; margin-top: 6px;">No pending orders assigned to you right now. New dispatch orders from the pharmacy will appear here automatically.</p>
            </div>
        <?php else: ?>

            <?php foreach ($active_orders as $ord): ?>
                <div class="order-card">
                    <div class="order-card-header">
                        <div>
                            <div class="order-id">Order #<?php echo $ord['order_id']; ?></div>
                            <div class="order-tracking"><?php echo htmlspecialchars($ord['tracking_order']); ?></div>
                        </div>
                        <div>
                            <?php if ($ord['status'] == 4): ?>
                                <span class="status-pill out"><i class='bx bx-cycling'></i> Out for Delivery</span>
                            <?php elseif ($ord['status'] == 3): ?>
                                <span class="status-pill ready"><i class='bx bx-package'></i> Packed & Ready</span>
                            <?php else: ?>
                                <span class="status-pill ready"><i class='bx bx-time'></i> Processing</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="customer-info-box">
                        <div class="cust-name">
                            <i class='bx bx-user' style="color: #059669;"></i> <?php echo htmlspecialchars($ord['user_name']); ?>
                        </div>
                        <div class="cust-address">
                            <i class='bx bx-map' style="color: #ef4444; font-size: 16px; margin-top: 2px;"></i>
                            <span><?php echo htmlspecialchars($ord['address']); ?></span>
                        </div>
                        <?php if (!empty($ord['delivery_notes'])): ?>
                            <div style="margin-top: 8px; font-size: 12px; color: #b45309; background: #fffbeb; padding: 6px 10px; border-radius: 6px; border: 1px solid #fef3c7;">
                                <i class='bx bx-info-circle'></i> Note: <?php echo htmlspecialchars($ord['delivery_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Navigation & Call Buttons -->
                    <div class="action-row">
                        <a href="tel:<?php echo htmlspecialchars($ord['phone']); ?>" class="btn-quick btn-call">
                            <i class='bx bx-phone-call'></i> Call Customer
                        </a>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($ord['address']); ?>" target="_blank" class="btn-quick btn-maps">
                            <i class='bx bx-navigation'></i> Open in Maps
                        </a>
                    </div>

                    <!-- Payment Details -->
                    <div class="payment-banner">
                        <div>
                            <span style="font-size: 11.5px; text-transform: uppercase; color: #713f12; font-weight: 700; display: block;">Payment Mode</span>
                            <span style="font-weight: 600; color: #854d0e;"><?php echo strtoupper(htmlspecialchars($ord['payment'])); ?></span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 11.5px; text-transform: uppercase; color: #713f12; font-weight: 700; display: block;">Collect Cash</span>
                            <strong>रु. <?php echo number_format($ord['total'], 2); ?></strong>
                        </div>
                    </div>

                    <!-- Action Button Form -->
                    <?php if ($ord['status'] == 3 || $ord['status'] == 0): ?>
                        <form method="POST" action="driver_portal.php">
                            <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                            <button type="submit" name="btnPickupOrder" class="btn-primary-action btn-pickup">
                                <i class='bx bx-cycling'></i> Start Delivery (Picked Up)
                            </button>
                        </form>
                    <?php elseif ($ord['status'] == 4): ?>
                        <form method="POST" action="driver_portal.php" onsubmit="return confirm('Confirm delivery completion for Order #<?php echo $ord['order_id']; ?>?')">
                            <input type="hidden" name="order_id" value="<?php echo $ord['order_id']; ?>">
                            <div style="margin-bottom: 10px;">
                                <input type="text" name="delivery_proof" placeholder="Optional: Received by customer / gate" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12.5px;">
                            </div>
                            <button type="submit" name="btnDeliverOrder" class="btn-primary-action btn-deliver">
                                <i class='bx bx-check-circle'></i> Mark Successfully Delivered
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    <?php else: ?>

        <!-- Today's Summary KPI -->
        <div class="stats-summary">
            <div class="stat-item">
                <strong><?php echo count($today_completed); ?></strong>
                <span>Orders Delivered Today</span>
            </div>
            <div class="stat-item">
                <strong>रु. <?php echo number_format($today_cod_collected, 2); ?></strong>
                <span>COD Cash Collected</span>
            </div>
        </div>

        <?php if (empty($today_completed)): ?>
            <div style="text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;">
                <i class='bx bx-history' style="font-size: 40px; color: #94a3b8; margin-bottom: 8px; display: block;"></i>
                <div style="font-size: 14px; font-weight: 600; color: #475569;">No deliveries completed yet today.</div>
            </div>
        <?php else: ?>
            <?php foreach ($today_completed as $comp): ?>
                <div class="order-card" style="padding: 14px 16px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div style="font-weight: 700; font-size: 14px; color: #0f172a;">Order #<?php echo $comp['order_id']; ?> &bull; <?php echo htmlspecialchars($comp['user_name']); ?></div>
                        <span class="status-pill done"><i class='bx bx-check'></i> Delivered</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; display: flex; justify-content: space-between;">
                        <span>Delivered at: <?php echo date('h:i A', strtotime($comp['delivered_at'])); ?></span>
                        <strong style="color: #059669;">रु. <?php echo number_format($comp['total'], 2); ?> (<?php echo strtoupper($comp['payment']); ?>)</strong>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

</main>

</body>
</html>
