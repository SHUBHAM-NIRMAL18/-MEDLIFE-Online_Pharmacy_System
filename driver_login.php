<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as driver
if (isset($_SESSION['driver_login']) && $_SESSION['driver_login'] === true) {
    header('Location: driver_portal.php');
    exit();
}

$error = '';
$login_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['driver_login'])) {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($login_input) && !empty($password)) {
        $conn = get_db_connection();
        $hashed = md5($password);

        $stmt = $conn->prepare("SELECT d.*, p.name AS pharmacy_name, p.status AS pharmacy_status 
                                FROM tbl_delivery_drivers d 
                                LEFT JOIN tbl_pharmacies p ON d.pharmacy_id = p.pharmacy_id 
                                WHERE (d.email = ? OR d.phone = ?) AND d.password = ?");
        if ($stmt) {
            $stmt->bind_param("sss", $login_input, $login_input, $hashed);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $driver = $res->fetch_assoc();

                if ((int)$driver['status'] === 0) {
                    $error = 'Your courier account is currently inactive. Please contact store management.';
                } else {
                    $_SESSION['driver_login'] = true;
                    $_SESSION['driver_id'] = (int)$driver['driver_id'];
                    $_SESSION['driver_name'] = $driver['name'];
                    $_SESSION['driver_phone'] = $driver['phone'];
                    $_SESSION['driver_email'] = $driver['email'];
                    $_SESSION['driver_vehicle'] = $driver['vehicle_type'] . ' (' . ($driver['vehicle_number'] ?? '') . ')';
                    $_SESSION['driver_pharmacy_id'] = (int)$driver['pharmacy_id'];
                    $_SESSION['driver_pharmacy_name'] = $driver['pharmacy_name'] ?? 'MedLife Pharmacy';

                    header('Location: driver_portal.php');
                    exit();
                }
            } else {
                $error = 'Invalid email/phone or password.';
            }
            $stmt->close();
        }
    } else {
        $error = 'Please enter both login and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Driver Delivery Portal Login - Medlife</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            padding: 32px 28px;
            text-align: center;
        }
        .driver-icon {
            width: 68px;
            height: 68px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 36px;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.35);
            margin-bottom: 18px;
        }
        h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
        p.subtitle { font-size: 13px; color: #64748b; margin-bottom: 24px; }
        .form-group { text-align: left; margin-bottom: 18px; }
        label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper i {
            position: absolute;
            left: 14px;
            color: #94a3b8;
            font-size: 18px;
        }
        input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            outline: none;
            transition: all 0.2s;
        }
        input:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15); }
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35);
            transition: transform 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        .btn-login:active { transform: scale(0.98); }
        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 18px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .portal-switch {
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #f1f5f9;
            font-size: 12.5px;
            color: #64748b;
        }
        .portal-switch a { color: #059669; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="driver-icon">
        <i class='bx bx-cycling'></i>
    </div>
    <h1>Courier Portal</h1>
    <p class="subtitle">Log in to view assigned pharmacy deliveries & live routes.</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error">
            <i class='bx bx-error-circle'></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="driver_login.php">
        <div class="form-group">
            <label>Phone Number or Email</label>
            <div class="input-wrapper">
                <i class='bx bx-user'></i>
                <input type="text" name="login_input" placeholder="e.g. 9841234567 or driver@store.com" value="<?php echo htmlspecialchars($login_input); ?>" required autofocus>
            </div>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <i class='bx bx-lock-alt'></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" name="driver_login" class="btn-login">
            <i class='bx bx-log-in-circle'></i> Launch Driver Workspace
        </button>
    </form>

    <div class="portal-switch">
        Store Admin / Pharmacist? <a href="admin_login.php">Admin Login &rarr;</a>
    </div>
</div>

</body>
</html>
