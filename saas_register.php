<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

$err = [];
$name = $owner_name = $email = $phone = $address = $plan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRegisterPharmacy'])) {
    $name = trim($_POST['pharmacy_name'] ?? '');
    $owner_name = trim($_POST['owner_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $plan = trim($_POST['plan'] ?? 'Pro');

    if (empty($name)) {
        $err['pharmacy_name'] = 'Pharmacy name is required';
    }
    if (empty($owner_name)) {
        $err['owner_name'] = 'Owner name is required';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'Valid email address is required';
    } else {
        // Check if email already registered in tbl_admin or tbl_pharmacies
        $stmt_check = $conn->prepare("SELECT admin_id FROM tbl_admin WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            $err['email'] = 'This email is already registered as an admin';
        }
    }
    if (empty($phone)) {
        $err['phone'] = 'Phone number is required';
    }
    if (empty($password) || strlen($password) < 6) {
        $err['password'] = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $err['confirm_password'] = 'Passwords do not match';
    }

    if (count($err) === 0) {
        // Create URL slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug_check = $conn->query("SELECT pharmacy_id FROM tbl_pharmacies WHERE slug = '$slug'");
        if ($slug_check && $slug_check->num_rows > 0) {
            $slug .= '-' . rand(100, 999);
        }

        // Insert Pharmacy record
        $stmt_p = $conn->prepare("INSERT INTO tbl_pharmacies (name, slug, email, phone, address, plan, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
        if ($stmt_p) {
            $stmt_p->bind_param("ssssss", $name, $slug, $email, $phone, $address, $plan);
            if ($stmt_p->execute()) {
                $pharmacy_id = $conn->insert_id;

                // Insert Admin User record
                $hashed_pass = md5($password);
                $stmt_a = $conn->prepare("INSERT INTO tbl_admin (name, email, password, status, pharmacy_id) VALUES (?, ?, ?, 1, ?)");
                if ($stmt_a) {
                    $stmt_a->bind_param("sssi", $owner_name, $email, $hashed_pass, $pharmacy_id);
                    if ($stmt_a->execute()) {
                        $admin_id = $conn->insert_id;

                        // Log admin in automatically
                        $_SESSION['admin_login'] = true;
                        $_SESSION['admin_id'] = $admin_id;
                        $_SESSION['admin_email'] = $email;
                        $_SESSION['admin_name'] = $owner_name;
                        $_SESSION['admin_pharmacy_id'] = $pharmacy_id;
                        $_SESSION['admin_pharmacy_name'] = $name;

                        // Unset customer session keys if any
                        unset($_SESSION['user_login'], $_SESSION['user_id'], $_SESSION['email'], $_SESSION['name']);

                        $_SESSION['toast'] = [
                            'type' => 'success',
                            'title' => 'Welcome to MedLife SaaS!',
                            'message' => "Congratulations! Your pharmacy '$name' has been successfully created."
                        ];

                        header("Location: admin_home.php");
                        exit();
                    }
                }
            } else {
                $err['general'] = 'Failed to create pharmacy account. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Your Pharmacy - MedLife SaaS Platform</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #f8fafc;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      font-family: 'Poppins', sans-serif;
    }
    .saas-header {
      padding: 20px 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(12px);
    }
    .saas-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #fff;
      font-size: 24px;
      font-weight: 700;
    }
    .saas-logo i {
      color: #10b981;
      font-size: 32px;
    }
    .saas-badge {
      background: linear-gradient(135deg, #10b981, #059669);
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #fff;
    }
    .saas-container {
      max-width: 900px;
      margin: 40px auto;
      padding: 0 20px;
      width: 100%;
    }
    .saas-card {
      background: rgba(30, 41, 59, 0.85);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
      padding: 40px;
      backdrop-filter: blur(16px);
    }
    .saas-card-header {
      text-align: center;
      margin-bottom: 35px;
    }
    .saas-card-header h2 {
      font-size: 28px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 8px;
    }
    .saas-card-header p {
      color: #94a3b8;
      font-size: 15px;
    }
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-group-full {
      grid-column: span 2;
    }
    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      font-size: 14px;
      color: #cbd5e1;
    }
    .form-control {
      width: 100%;
      padding: 12px 16px;
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid #334155;
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      transition: all 0.3s ease;
      outline: none;
    }
    .form-control:focus {
      border-color: #10b981;
      box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }
    .error-text {
      color: #ef4444;
      font-size: 12px;
      margin-top: 5px;
      display: block;
    }
    .plan-selector {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-top: 8px;
    }
    .plan-option {
      background: rgba(15, 23, 42, 0.6);
      border: 2px solid #334155;
      border-radius: 10px;
      padding: 15px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    .plan-option input[type="radio"] {
      display: none;
    }
    .plan-option.selected, .plan-option:hover {
      border-color: #10b981;
      background: rgba(16, 185, 129, 0.1);
    }
    .plan-name {
      font-weight: 600;
      color: #fff;
      font-size: 15px;
    }
    .plan-price {
      color: #10b981;
      font-size: 13px;
      margin-top: 4px;
    }
    .btn-submit {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 25px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
    }
    .footer-link {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: #94a3b8;
    }
    .footer-link a {
      color: #10b981;
      text-decoration: none;
      font-weight: 500;
    }
    .footer-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <header class="saas-header">
    <a href="index.php" class="saas-logo">
      <i class='bx bx-plus-medical'></i> MedLife <span class="saas-badge">SaaS Platform</span>
    </a>
    <div>
      <a href="admin_login.php" style="color: #cbd5e1; text-decoration: none; font-size: 14px; margin-right: 20px;">Pharmacy Admin Login</a>
      <a href="saas_admin.php" style="color: #10b981; text-decoration: none; font-size: 14px; font-weight: 600;"><i class='bx bx-shield-quarter'></i> Super Admin Portal</a>
    </div>
  </header>

  <div class="saas-container">
    <div class="saas-card">
      <div class="saas-card-header">
        <h2>Register Your Pharmacy Store</h2>
        <p>Join our online pharmacy network and start serving patients digitally in minutes.</p>
      </div>

      <?php if (!empty($err['general'])): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
          <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($err['general']); ?>
        </div>
      <?php endif; ?>

      <form action="saas_register.php" method="POST">
        <div class="form-grid">
          
          <div class="form-group-full">
            <label class="form-label"><i class='bx bx-store-alt'></i> Pharmacy Store Name *</label>
            <input type="text" name="pharmacy_name" class="form-control" placeholder="e.g. City Care Pharmacy" value="<?php echo htmlspecialchars($name); ?>" required>
            <?php if (!empty($err['pharmacy_name'])): ?><span class="error-text"><?php echo $err['pharmacy_name']; ?></span><?php endif; ?>
          </div>

          <div>
            <label class="form-label"><i class='bx bx-user'></i> Pharmacy Owner / Manager Name *</label>
            <input type="text" name="owner_name" class="form-control" placeholder="Full Name" value="<?php echo htmlspecialchars($owner_name); ?>" required>
            <?php if (!empty($err['owner_name'])): ?><span class="error-text"><?php echo $err['owner_name']; ?></span><?php endif; ?>
          </div>

          <div>
            <label class="form-label"><i class='bx bx-envelope'></i> Business Email Address *</label>
            <input type="email" name="email" class="form-control" placeholder="owner@pharmacy.com" value="<?php echo htmlspecialchars($email); ?>" required>
            <?php if (!empty($err['email'])): ?><span class="error-text"><?php echo $err['email']; ?></span><?php endif; ?>
          </div>

          <div>
            <label class="form-label"><i class='bx bx-phone'></i> Phone / Mobile Number *</label>
            <input type="text" name="phone" class="form-control" placeholder="98XXXXXXXX" value="<?php echo htmlspecialchars($phone); ?>" required>
            <?php if (!empty($err['phone'])): ?><span class="error-text"><?php echo $err['phone']; ?></span><?php endif; ?>
          </div>

          <div>
            <label class="form-label"><i class='bx bx-map'></i> Physical Store Address</label>
            <input type="text" name="address" class="form-control" placeholder="Street, City" value="<?php echo htmlspecialchars($address); ?>">
          </div>

          <div>
            <label class="form-label"><i class='bx bx-lock-alt'></i> Admin Password *</label>
            <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            <?php if (!empty($err['password'])): ?><span class="error-text"><?php echo $err['password']; ?></span><?php endif; ?>
          </div>

          <div>
            <label class="form-label"><i class='bx bx-check-double'></i> Confirm Password *</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
            <?php if (!empty($err['confirm_password'])): ?><span class="error-text"><?php echo $err['confirm_password']; ?></span><?php endif; ?>
          </div>

          <div class="form-group-full">
            <label class="form-label"><i class='bx bx-layer'></i> Select Subscription Plan</label>
            <div class="plan-selector">
              <label class="plan-option selected" id="plan-starter">
                <input type="radio" name="plan" value="Starter" onchange="selectPlan(this)">
                <div class="plan-name">Starter</div>
                <div class="plan-price">Up to 50 Products</div>
              </label>
              <label class="plan-option selected" id="plan-pro">
                <input type="radio" name="plan" value="Pro" checked onchange="selectPlan(this)">
                <div class="plan-name">Professional</div>
                <div class="plan-price">Unlimited Products</div>
              </label>
              <label class="plan-option" id="plan-enterprise">
                <input type="radio" name="plan" value="Enterprise" onchange="selectPlan(this)">
                <div class="plan-name">Enterprise</div>
                <div class="plan-price">Custom Solutions</div>
              </label>
            </div>
          </div>

        </div>

        <button type="submit" name="btnRegisterPharmacy" class="btn-submit">
          <i class='bx bx-rocket'></i> Register & Launch Pharmacy Store
        </button>
      </form>

      <div class="footer-link">
        Already registered? <a href="admin_login.php">Log in to Pharmacy Dashboard</a>
      </div>
    </div>
  </div>

  <script>
    function selectPlan(radio) {
      document.querySelectorAll('.plan-option').forEach(el => el.classList.remove('selected'));
      radio.closest('.plan-option').classList.add('selected');
    }
  </script>
</body>
</html>
