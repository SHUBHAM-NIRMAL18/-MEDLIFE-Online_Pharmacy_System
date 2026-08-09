<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

$login_error = '';

// Handle Super Admin Login
if (isset($_POST['btnSuperAdminLogin'])) {
    $email = trim($_POST['email'] ?? '');
    $password = md5($_POST['password'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM tbl_super_admin WHERE email = ? AND password = ?");
    if ($stmt) {
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $_SESSION['super_admin_login'] = true;
            $_SESSION['super_admin_id'] = $row['super_admin_id'];
            $_SESSION['super_admin_name'] = $row['name'];
            $_SESSION['super_admin_email'] = $row['email'];
            header("Location: saas_admin.php");
            exit();
        } else {
            $login_error = 'Invalid Super Admin credentials';
        }
    }
}

// Handle Exit Impersonation
if (isset($_GET['exit_impersonation'])) {
    unset($_SESSION['impersonating_super_admin'], $_SESSION['admin_login'], $_SESSION['admin_id'], $_SESSION['admin_email'], $_SESSION['admin_name'], $_SESSION['admin_pharmacy_id'], $_SESSION['admin_pharmacy_name']);
    header("Location: saas_admin.php");
    exit();
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['super_admin_login'], $_SESSION['super_admin_id'], $_SESSION['super_admin_name'], $_SESSION['super_admin_email'], $_SESSION['impersonating_super_admin']);
    header("Location: saas_admin.php");
    exit();
}

$is_super_admin = isset($_SESSION['super_admin_login']) && $_SESSION['super_admin_login'] === true;

// Handle Super Admin Actions if logged in
if ($is_super_admin) {

    // 1. One-Click Impersonation ("Login as Pharmacy Admin")
    if (isset($_GET['impersonate_pharmacy']) && is_numeric($_GET['impersonate_pharmacy'])) {
        $target_pharm_id = (int)$_GET['impersonate_pharmacy'];
        $p_res = $conn->query("SELECT * FROM tbl_pharmacies WHERE pharmacy_id = $target_pharm_id");
        if ($p_res && $p_res->num_rows > 0) {
            $p_data = $p_res->fetch_assoc();
            
            // Find or pick an admin for this pharmacy
            $a_res = $conn->query("SELECT * FROM tbl_admin WHERE pharmacy_id = $target_pharm_id ORDER BY admin_id ASC LIMIT 1");
            $admin_name = 'Store Administrator';
            $admin_id = 1;
            $admin_email = $p_data['email'];
            if ($a_res && $a_res->num_rows > 0) {
                $a_data = $a_res->fetch_assoc();
                $admin_name = $a_data['name'];
                $admin_id = (int)$a_data['admin_id'];
                $admin_email = $a_data['email'];
            } else {
                // If no admin exists for this pharmacy yet, create a default owner
                $pass_hash = md5('admin123');
                $conn->query("INSERT INTO tbl_admin (name, email, password, status, pharmacy_id) VALUES ('" . $conn->real_escape_string($p_data['name']) . " Admin', '" . $conn->real_escape_string($p_data['email']) . "', '$pass_hash', 1, $target_pharm_id)");
                $admin_id = $conn->insert_id;
            }

            // Set Admin session with Super Admin impersonation flag
            $_SESSION['impersonating_super_admin'] = true;
            $_SESSION['admin_login'] = true;
            $_SESSION['admin_id'] = $admin_id;
            $_SESSION['admin_email'] = $admin_email;
            $_SESSION['admin_name'] = $admin_name;
            $_SESSION['admin_pharmacy_id'] = $target_pharm_id;
            $_SESSION['admin_pharmacy_name'] = $p_data['name'];

            header("Location: admin_home.php");
            exit();
        }
    }

    // 2. Toggle Pharmacy Status (Activate / Suspend)
    if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
        $p_id = (int)$_GET['toggle_status'];
        $curr_status = (int)($_GET['current'] ?? 1);
        $new_status = $curr_status === 1 ? 0 : 1;
        
        $stmt_u = $conn->prepare("UPDATE tbl_pharmacies SET status = ? WHERE pharmacy_id = ?");
        if ($stmt_u) {
            $stmt_u->bind_param("ii", $new_status, $p_id);
            $stmt_u->execute();
            $stmt_u->close();
        }
        header("Location: saas_admin.php?status_updated=1");
        exit();
    }

    // 3. Edit Pharmacy Details Modal Submission
    if (isset($_POST['btnUpdatePharmacyModal'])) {
        $p_id = (int)$_POST['edit_pharmacy_id'];
        $p_name = trim($_POST['edit_name'] ?? '');
        $p_email = trim($_POST['edit_email'] ?? '');
        $p_phone = trim($_POST['edit_phone'] ?? '');
        $p_address = trim($_POST['edit_address'] ?? '');
        $p_pan = trim($_POST['edit_pan'] ?? '609823145');
        $p_plan = trim($_POST['edit_plan'] ?? 'Pro');
        $p_fee = (float)($_POST['edit_delivery_fee'] ?? 100.00);
        $p_status = (int)($_POST['edit_status'] ?? 1);

        if (!empty($p_name) && !empty($p_email) && $p_id > 0) {
            $stmt_up = $conn->prepare("UPDATE tbl_pharmacies SET name = ?, email = ?, phone = ?, address = ?, pan_number = ?, plan = ?, delivery_fee = ?, status = ? WHERE pharmacy_id = ?");
            if ($stmt_up) {
                $stmt_up->bind_param("ssssssdii", $p_name, $p_email, $p_phone, $p_address, $p_pan, $p_plan, $p_fee, $p_status, $p_id);
                $stmt_up->execute();
                $stmt_up->close();
            }
        }
        header("Location: saas_admin.php?updated=1");
        exit();
    }

    // 4. Add New Pharmacy Manually & Seed Default Categories
    if (isset($_POST['btnCreatePharmacy'])) {
        $p_name = trim($_POST['p_name'] ?? '');
        $p_email = trim($_POST['p_email'] ?? '');
        $p_phone = trim($_POST['p_phone'] ?? '');
        $p_address = trim($_POST['p_address'] ?? '');
        $p_pan = trim($_POST['p_pan'] ?? '609823145');
        $p_plan = trim($_POST['p_plan'] ?? 'Pro');
        $p_fee = (float)($_POST['p_fee'] ?? 100);

        if (!empty($p_name) && !empty($p_email)) {
            $p_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p_name), '-'));
            $chk_slug = $conn->query("SELECT pharmacy_id FROM tbl_pharmacies WHERE slug = '$p_slug'");
            if ($chk_slug && $chk_slug->num_rows > 0) {
                $p_slug .= '-' . rand(100, 999);
            }

            $stmt_cp = $conn->prepare("INSERT INTO tbl_pharmacies (name, slug, email, phone, address, pan_number, plan, delivery_fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            if ($stmt_cp) {
                $stmt_cp->bind_param("sssssssd", $p_name, $p_slug, $p_email, $p_phone, $p_address, $p_pan, $p_plan, $p_fee);
                if ($stmt_cp->execute()) {
                    $new_id = $conn->insert_id;
                    // Seed starter categories for new tenant
                    seed_default_pharmacy_categories($conn, $new_id);

                    // Create owner admin account
                    $pass_hash = md5('admin123');
                    $stmt_adm = $conn->prepare("INSERT INTO tbl_admin (name, email, password, status, pharmacy_id) VALUES (?, ?, ?, 1, ?)");
                    if ($stmt_adm) {
                        $admin_label = $p_name . " Admin";
                        $stmt_adm->bind_param("sssi", $admin_label, $p_email, $pass_hash, $new_id);
                        $stmt_adm->execute();
                        $stmt_adm->close();
                    }
                }
                $stmt_cp->close();
            }
        }
        header("Location: saas_admin.php?created=1");
        exit();
    }

    // Fetch Stats
    $total_pharmacies = 0;
    $active_pharmacies = 0;
    $suspended_pharmacies = 0;
    $total_platform_orders = 0;
    $total_platform_revenue = 0.0;
    $total_platform_products = 0;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_pharmacies");
    if ($res) $total_pharmacies = (int)$res->fetch_assoc()['cnt'];

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_pharmacies WHERE status = 1");
    if ($res) $active_pharmacies = (int)$res->fetch_assoc()['cnt'];

    $suspended_pharmacies = $total_pharmacies - $active_pharmacies;

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_order");
    if ($res) $total_platform_orders = (int)$res->fetch_assoc()['cnt'];

    $res = $conn->query("SELECT SUM(total) AS rev FROM tbl_order WHERE status != 2");
    if ($res) {
        $row = $res->fetch_assoc();
        $total_platform_revenue = !empty($row['rev']) ? (float)$row['rev'] : 0.0;
    }

    $res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_products");
    if ($res) $total_platform_products = (int)$res->fetch_assoc()['cnt'];

    // Fetch All Pharmacies with metric counts
    $pharmacies_list = [];
    $sql_list = "SELECT p.*, 
                 (SELECT COUNT(*) FROM tbl_products pr WHERE pr.pharmacy_id = p.pharmacy_id) AS prod_count,
                 (SELECT COUNT(*) FROM tbl_order o WHERE o.pharmacy_id = p.pharmacy_id) AS order_count,
                 (SELECT COALESCE(SUM(o.total), 0) FROM tbl_order o WHERE o.pharmacy_id = p.pharmacy_id AND o.status != 2) AS revenue
                 FROM tbl_pharmacies p ORDER BY p.pharmacy_id ASC";
    $res_list = $conn->query($sql_list);
    if ($res_list && $res_list->num_rows > 0) {
        while ($row = $res_list->fetch_assoc()) {
            $pharmacies_list[] = $row;
        }
    }

    // Fetch Recent Platform Orders (Global Live Feed)
    $recent_orders = [];
    $sql_recent = "SELECT o.*, p.name AS pharmacy_name FROM tbl_order o LEFT JOIN tbl_pharmacies p ON o.pharmacy_id = p.pharmacy_id ORDER BY o.order_id DESC LIMIT 6";
    $res_recent = $conn->query($sql_recent);
    if ($res_recent && $res_recent->num_rows > 0) {
        while ($ro = $res_recent->fetch_assoc()) {
            $recent_orders[] = $ro;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SaaS Super Admin Control Portal - MedLife Platform</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    :root {
      --bg-dark: #090d16;
      --card-bg: #111827;
      --card-inner: #1f2937;
      --primary: #10b981;
      --primary-hover: #059669;
      --text-light: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #374151;
      --purple: #8b5cf6;
      --blue: #3b82f6;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
    }
    body {
      background-color: var(--bg-dark);
      color: var(--text-light);
      min-height: 100vh;
    }
    .top-header {
      background: rgba(17, 24, 39, 0.95);
      border-bottom: 1px solid var(--border-color);
      padding: 16px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      backdrop-filter: blur(12px);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 20px;
      font-weight: 700;
      color: #fff;
    }
    .logo-area i {
      color: var(--primary);
      font-size: 26px;
    }
    .badge-super {
      background: linear-gradient(135deg, #7c3aed, #4f46e5);
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 700;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .btn-logout {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid #ef4444;
      color: #fca5a5;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-logout:hover {
      background: #ef4444;
      color: #fff;
    }

    /* Login Card */
    .login-wrapper {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: calc(100vh - 80px);
      padding: 20px;
    }
    .login-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 40px;
      width: 100%;
      max-width: 440px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
    }
    .login-title {
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 6px;
      text-align: center;
    }
    .login-subtitle {
      color: var(--text-muted);
      font-size: 13.5px;
      text-align: center;
      margin-bottom: 24px;
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-label {
      display: block;
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 6px;
      font-weight: 500;
    }
    .form-control {
      width: 100%;
      padding: 11px 14px;
      background: #0f172a;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      outline: none;
    }
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
    }
    .btn-primary {
      padding: 11px 20px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      text-decoration: none;
      transition: background 0.2s;
    }
    .btn-primary:hover {
      background: var(--primary-hover);
    }

    /* Dashboard Layout */
    .main-container {
      max-width: 1360px;
      margin: 30px auto;
      padding: 0 24px;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 18px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 22px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.2);
    }
    .stat-icon {
      width: 52px;
      height: 52px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 26px;
      flex-shrink: 0;
    }
    .icon-pharmacy { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .icon-active { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
    .icon-orders { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .icon-revenue { background: rgba(139, 92, 246, 0.15); color: #8b5cf6; }

    .stat-value {
      font-size: 24px;
      font-weight: 800;
      color: #fff;
    }
    .stat-label {
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 500;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .section-title {
      font-size: 18px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .data-table-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      margin-bottom: 35px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    th {
      background: #0d131f;
      padding: 14px 18px;
      font-size: 12.5px;
      color: var(--text-muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-bottom: 1px solid var(--border-color);
    }
    td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-color);
      font-size: 13.5px;
    }
    tr:last-child td {
      border-bottom: none;
    }
    tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    .status-active { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); }
    .status-suspended { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }

    .btn-action {
      padding: 6px 11px;
      border-radius: 6px;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s;
      cursor: pointer;
      border: none;
    }
    .btn-impersonate {
      background: rgba(139, 92, 246, 0.15);
      color: #a78bfa;
      border: 1px solid rgba(139, 92, 246, 0.3);
    }
    .btn-impersonate:hover {
      background: #8b5cf6;
      color: #fff;
    }
    .btn-edit-tenant {
      background: rgba(59, 130, 246, 0.15);
      color: #93c5fd;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }
    .btn-edit-tenant:hover {
      background: #3b82f6;
      color: #fff;
    }
    .btn-toggle-suspend {
      background: rgba(239, 68, 68, 0.15);
      color: #fca5a5;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }
    .btn-toggle-suspend:hover {
      background: #ef4444;
      color: #fff;
    }
    .btn-toggle-activate {
      background: rgba(16, 185, 129, 0.15);
      color: #6ee7b7;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }
    .btn-toggle-activate:hover {
      background: #10b981;
      color: #fff;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.75);
      backdrop-filter: blur(6px);
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 20px;
    }
    .modal.active { display: flex; }
    .modal-content {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 14px;
      padding: 28px;
      width: 100%;
      max-width: 540px;
      box-shadow: 0 25px 50px rgba(0,0,0,0.6);
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>
<body>

  <header class="top-header">
    <div class="logo-area">
      <i class='bx bx-shield-quarter'></i> MedLife SaaS <span class="badge-super">Super Admin Command Center</span>
    </div>
    <div class="header-right">
      <?php if ($is_super_admin): ?>
        <span style="font-size: 13px; color: var(--text-muted);"><i class='bx bx-user-circle' style="color: var(--primary);"></i> <?php echo htmlspecialchars($_SESSION['super_admin_name']); ?></span>
        <a href="index.php" target="_blank" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
          <i class='bx bx-link-external'></i> Marketplace
        </a>
        <a href="saas_admin.php?action=logout" class="btn-logout"><i class='bx bx-log-out'></i> Logout</a>
      <?php else: ?>
        <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px;">Back to Marketplace</a>
      <?php endif; ?>
    </div>
  </header>

  <?php if (!$is_super_admin): ?>

    <div class="login-wrapper">
      <div class="login-card">
        <h2 class="login-title">Super Admin Portal</h2>
        <p class="login-subtitle">Sign in to manage all registered pharmacy tenants</p>

        <?php if (!empty($login_error)): ?>
          <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($login_error); ?>
          </div>
        <?php endif; ?>

        <form action="saas_admin.php" method="POST">
          <div class="form-group">
            <label class="form-label">Super Admin Email</label>
            <input type="email" name="email" class="form-control" placeholder="admin@medlifesaas.com" required value="admin@medlifesaas.com">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
          </div>
          <button type="submit" name="btnSuperAdminLogin" class="btn-primary" style="width: 100%;">
            <i class='bx bx-log-in-circle'></i> Login to SaaS Command Center
          </button>
        </form>
      </div>
    </div>

  <?php else: ?>

    <div class="main-container">
      
      <!-- Platform Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon icon-pharmacy"><i class='bx bx-store-alt'></i></div>
          <div>
            <div class="stat-value"><?php echo $total_pharmacies; ?></div>
            <div class="stat-label">Total Pharmacy Tenants</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-active"><i class='bx bx-check-circle'></i></div>
          <div>
            <div class="stat-value"><?php echo $active_pharmacies; ?></div>
            <div class="stat-label">Active Stores</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-orders"><i class='bx bx-package'></i></div>
          <div>
            <div class="stat-value"><?php echo $total_platform_orders; ?></div>
            <div class="stat-label">Platform Orders</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-revenue"><i class='bx bx-dollar-circle'></i></div>
          <div>
            <div class="stat-value">रु. <?php echo number_format($total_platform_revenue, 2); ?></div>
            <div class="stat-label">Platform Gross GMV</div>
          </div>
        </div>
      </div>

      <!-- Pharmacy Management Table -->
      <div class="section-header">
        <h2 class="section-title"><i class='bx bx-building'></i> Pharmacy Tenants & Subscriptions</h2>
        <div style="display: flex; gap: 10px;">
          <button class="btn-primary" onclick="document.getElementById('addModal').classList.add('active')">
            <i class='bx bx-plus-circle'></i> Provision New Pharmacy
          </button>
        </div>
      </div>

      <div class="data-table-card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Pharmacy Store</th>
              <th>Owner Contact</th>
              <th>Plan</th>
              <th>Products</th>
              <th>Orders</th>
              <th>Gross Sales</th>
              <th>Status</th>
              <th style="text-align: right;">Admin Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pharmacies_list as $p): ?>
              <tr>
                <td><strong>#<?php echo $p['pharmacy_id']; ?></strong></td>
                <td>
                  <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                  <small style="color: var(--text-muted);"><i class='bx bx-map-pin'></i> <?php echo htmlspecialchars($p['address'] ?? 'Kathmandu'); ?> &middot; PAN: <?php echo htmlspecialchars($p['pan_number'] ?? '609823145'); ?></small>
                </td>
                <td>
                  <div><?php echo htmlspecialchars($p['email']); ?></div>
                  <small style="color: var(--text-muted);"><?php echo htmlspecialchars($p['phone']); ?></small>
                </td>
                <td>
                  <span style="background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                    <?php echo htmlspecialchars($p['plan'] ?? 'Pro'); ?>
                  </span>
                </td>
                <td><strong style="color: #cbd5e1;"><?php echo $p['prod_count']; ?></strong> items</td>
                <td><strong style="color: #cbd5e1;"><?php echo $p['order_count']; ?></strong></td>
                <td><strong style="color: #10b981;">रु. <?php echo number_format($p['revenue'], 2); ?></strong></td>
                <td>
                  <?php if ((int)$p['status'] === 1): ?>
                    <span class="status-badge status-active"><i class='bx bx-check'></i> Active</span>
                  <?php else: ?>
                    <span class="status-badge status-suspended"><i class='bx bx-block'></i> Suspended</span>
                  <?php endif; ?>
                </td>
                <td style="text-align: right;">
                  <div style="display: inline-flex; gap: 6px; align-items: center;">
                    
                    <!-- Impersonate Tenant Button -->
                    <a href="saas_admin.php?impersonate_pharmacy=<?php echo $p['pharmacy_id']; ?>" class="btn-action btn-impersonate" title="Manage store dashboard as this pharmacy admin">
                      <i class='bx bx-log-in-circle'></i> Manage Store
                    </a>

                    <!-- Edit Tenant Modal Button -->
                    <button type="button" class="btn-action btn-edit-tenant" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)" title="Edit tenant details">
                      <i class='bx bx-edit'></i> Edit
                    </button>

                    <!-- Toggle Status Button -->
                    <?php if ((int)$p['status'] === 1): ?>
                      <a href="saas_admin.php?toggle_status=<?php echo $p['pharmacy_id']; ?>&current=1" class="btn-action btn-toggle-suspend" onclick="return confirm('Suspend <?php echo htmlspecialchars(addslashes($p['name'])); ?>? Their admin access will be immediately blocked.')" title="Suspend pharmacy">
                        <i class='bx bx-pause'></i> Suspend
                      </a>
                    <?php else: ?>
                      <a href="saas_admin.php?toggle_status=<?php echo $p['pharmacy_id']; ?>&current=0" class="btn-action btn-toggle-activate" title="Activate pharmacy">
                        <i class='bx bx-play'></i> Activate
                      </a>
                    <?php endif; ?>

                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Live Platform Order Transactions Stream -->
      <div class="section-header">
        <h2 class="section-title"><i class='bx bx-pulse'></i> Recent Platform Transactions (Global Feed)</h2>
      </div>

      <div class="data-table-card">
        <table>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Tracking Ref</th>
              <th>Pharmacy Tenant</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Total Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($recent_orders)): ?>
              <?php foreach ($recent_orders as $ro): ?>
                <tr>
                  <td><strong>#<?php echo $ro['order_id']; ?></strong></td>
                  <td><code style="background: #0f172a; color: #38bdf8; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($ro['tracking_order']); ?></code></td>
                  <td>
                    <span style="background: rgba(16, 185, 129, 0.12); color: #10b981; padding: 2px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                      <i class='bx bx-store-alt'></i> <?php echo htmlspecialchars($ro['pharmacy_name'] ?? 'MedLife Central'); ?>
                    </span>
                  </td>
                  <td><?php echo htmlspecialchars($ro['user_name']); ?></td>
                  <td><?php echo date("M d, Y, g:i A", strtotime($ro['created_at'])); ?></td>
                  <td><strong>रु. <?php echo number_format($ro['total'], 2); ?></strong></td>
                  <td>
                    <?php 
                    if ($ro['status'] == 0) echo "<span style='color: #f59e0b; font-weight: 600;'><i class='bx bx-loader-circle'></i> Processing</span>";
                    elseif ($ro['status'] == 1) echo "<span style='color: #10b981; font-weight: 600;'><i class='bx bx-check-circle'></i> Delivered</span>";
                    elseif ($ro['status'] == 2) echo "<span style='color: #ef4444; font-weight: 600;'><i class='bx bx-x-circle'></i> Cancelled</span>";
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">No platform transactions recorded yet.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Modal for Edit Pharmacy Tenant -->
    <div class="modal" id="editModal">
      <div class="modal-content">
        <h3 style="margin-bottom: 18px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
          <i class='bx bx-edit' style="color: var(--primary);"></i> Edit Pharmacy Tenant
        </h3>
        <form action="saas_admin.php" method="POST">
          <input type="hidden" name="edit_pharmacy_id" id="edit_pharmacy_id">
          
          <div class="form-group">
            <label class="form-label">Pharmacy Name *</label>
            <input type="text" name="edit_name" id="edit_name" class="form-control" required>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label">Email *</label>
              <input type="email" name="edit_email" id="edit_email" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Phone</label>
              <input type="text" name="edit_phone" id="edit_phone" class="form-control">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Physical Address</label>
            <textarea name="edit_address" id="edit_address" rows="2" class="form-control"></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label">PAN Number</label>
              <input type="text" name="edit_pan" id="edit_pan" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Delivery Fee (रु.)</label>
              <input type="number" step="0.01" name="edit_delivery_fee" id="edit_delivery_fee" class="form-control">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label">Subscription Plan</label>
              <select name="edit_plan" id="edit_plan" class="form-control">
                <option value="Starter">Starter</option>
                <option value="Pro">Pro</option>
                <option value="Enterprise">Enterprise</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Account Status</label>
              <select name="edit_status" id="edit_status" class="form-control">
                <option value="1">Active</option>
                <option value="0">Suspended</option>
              </select>
            </div>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 24px;">
            <button type="submit" name="btnUpdatePharmacyModal" class="btn-primary" style="flex: 1;">Save Changes</button>
            <button type="button" class="btn-logout" style="cursor: pointer;" onclick="document.getElementById('editModal').classList.remove('active')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal for Manual Pharmacy Creation -->
    <div class="modal" id="addModal">
      <div class="modal-content">
        <h3 style="margin-bottom: 18px; font-size: 18px; display: flex; align-items: center; gap: 8px;">
          <i class='bx bx-plus-circle' style="color: var(--primary);"></i> Provision New Pharmacy Tenant
        </h3>
        <form action="saas_admin.php" method="POST">
          <div class="form-group">
            <label class="form-label">Pharmacy Name *</label>
            <input type="text" name="p_name" class="form-control" placeholder="e.g. Apex Health Pharmacy" required>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label">Owner Email *</label>
              <input type="email" name="p_email" class="form-control" placeholder="admin@apexhealth.com" required>
            </div>
            <div class="form-group">
              <label class="form-label">Contact Phone</label>
              <input type="text" name="p_phone" class="form-control" placeholder="98XXXXXXXX">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Physical Address</label>
            <textarea name="p_address" rows="2" class="form-control" placeholder="City, Location"></textarea>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
            <div class="form-group">
              <label class="form-label">PAN Number</label>
              <input type="text" name="p_pan" class="form-control" value="609823145">
            </div>
            <div class="form-group">
              <label class="form-label">Delivery Fee (रु.)</label>
              <input type="number" name="p_fee" class="form-control" value="100">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Subscription Plan</label>
            <select name="p_plan" class="form-control">
              <option value="Starter">Starter</option>
              <option value="Pro" selected>Pro</option>
              <option value="Enterprise">Enterprise</option>
            </select>
          </div>
          <div style="display: flex; gap: 10px; margin-top: 24px;">
            <button type="submit" name="btnCreatePharmacy" class="btn-primary" style="flex: 1;">Provision Store</button>
            <button type="button" class="btn-logout" style="cursor: pointer;" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <script>
      function openEditModal(pharmacy) {
        document.getElementById('edit_pharmacy_id').value = pharmacy.pharmacy_id;
        document.getElementById('edit_name').value = pharmacy.name || '';
        document.getElementById('edit_email').value = pharmacy.email || '';
        document.getElementById('edit_phone').value = pharmacy.phone || '';
        document.getElementById('edit_address').value = pharmacy.address || '';
        document.getElementById('edit_pan').value = pharmacy.pan_number || '609823145';
        document.getElementById('edit_delivery_fee').value = pharmacy.delivery_fee || 100;
        document.getElementById('edit_plan').value = pharmacy.plan || 'Pro';
        document.getElementById('edit_status').value = pharmacy.status !== undefined ? pharmacy.status : 1;
        document.getElementById('editModal').classList.add('active');
      }
    </script>

  <?php endif; ?>

</body>
</html>
