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

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['super_admin_login'], $_SESSION['super_admin_id'], $_SESSION['super_admin_name'], $_SESSION['super_admin_email']);
    header("Location: saas_admin.php");
    exit();
}

$is_super_admin = isset($_SESSION['super_admin_login']) && $_SESSION['super_admin_login'] === true;

// Handle Super Admin Actions if logged in
if ($is_super_admin) {

    // Toggle Pharmacy Status (Activate / Suspend)
    if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
        $p_id = (int)$_GET['toggle_status'];
        $curr_status = (int)($_GET['current'] ?? 1);
        $new_status = $curr_status === 1 ? 0 : 1;
        
        $stmt_u = $conn->prepare("UPDATE tbl_pharmacies SET status = ? WHERE pharmacy_id = ?");
        if ($stmt_u) {
            $stmt_u->bind_param("ii", $new_status, $p_id);
            $stmt_u->execute();
        }
        header("Location: saas_admin.php");
        exit();
    }

    // Add New Pharmacy Manually
    if (isset($_POST['btnCreatePharmacy'])) {
        $p_name = trim($_POST['p_name'] ?? '');
        $p_email = trim($_POST['p_email'] ?? '');
        $p_phone = trim($_POST['p_phone'] ?? '');
        $p_plan = trim($_POST['p_plan'] ?? 'Pro');
        $p_fee = (float)($_POST['p_fee'] ?? 100);

        if (!empty($p_name) && !empty($p_email)) {
            $p_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p_name), '-'));
            $stmt_cp = $conn->prepare("INSERT INTO tbl_pharmacies (name, slug, email, phone, plan, delivery_fee, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
            if ($stmt_cp) {
                $stmt_cp->bind_param("sssssd", $p_name, $p_slug, $p_email, $p_phone, $p_plan, $p_fee);
                $stmt_cp->execute();
            }
        }
        header("Location: saas_admin.php");
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SaaS Super Admin Control Portal - MedLife</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <style>
    :root {
      --bg-dark: #0f172a;
      --card-bg: #1e293b;
      --primary: #10b981;
      --primary-hover: #059669;
      --text-light: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #334155;
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
      background: rgba(15, 23, 42, 0.9);
      border-bottom: 1px solid var(--border-color);
      padding: 16px 32px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      backdrop-filter: blur(10px);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 22px;
      font-weight: 700;
      color: #fff;
    }
    .logo-area i {
      color: var(--primary);
      font-size: 28px;
    }
    .badge-super {
      background: linear-gradient(135deg, #6366f1, #4f46e5);
      font-size: 11px;
      padding: 4px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .btn-logout {
      background: rgba(239, 68, 68, 0.15);
      border: 1px solid #ef4444;
      color: #fca5a5;
      padding: 8px 16px;
      border-radius: 6px;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s;
    }
    .btn-logout:hover {
      background: #ef4444;
      color: #fff;
    }

    /* Login Form Styles */
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
      box-shadow: 0 20px 40px rgba(0,0,0,0.5);
    }
    .login-title {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 8px;
      text-align: center;
    }
    .login-subtitle {
      color: var(--text-muted);
      font-size: 14px;
      text-align: center;
      margin-bottom: 24px;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 8px;
    }
    .form-control {
      width: 100%;
      padding: 12px 16px;
      background: #0f172a;
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: #fff;
      font-size: 14px;
      outline: none;
    }
    .form-control:focus {
      border-color: var(--primary);
    }
    .btn-primary {
      width: 100%;
      padding: 12px;
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 15px;
      cursor: pointer;
      transition: background 0.2s;
    }
    .btn-primary:hover {
      background: var(--primary-hover);
    }

    /* Dashboard Layout */
    .main-container {
      max-width: 1320px;
      margin: 40px auto;
      padding: 0 24px;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 35px;
    }
    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }
    .icon-pharmacy { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .icon-active { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
    .icon-orders { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .icon-revenue { background: rgba(168, 85, 247, 0.15); color: #a855f7; }

    .stat-value {
      font-size: 22px;
      font-weight: 700;
      color: #fff;
    }
    .stat-label {
      font-size: 13px;
      color: var(--text-muted);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }
    .section-title {
      font-size: 20px;
      font-weight: 600;
    }

    .data-table-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }
    th {
      background: #0f172a;
      padding: 16px 20px;
      font-size: 13px;
      color: var(--text-muted);
      font-weight: 600;
      border-bottom: 1px solid var(--border-color);
    }
    td {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border-color);
      font-size: 14px;
    }
    tr:last-child td {
      border-bottom: none;
    }
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }
    .status-active { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .status-suspended { background: rgba(239, 68, 68, 0.15); color: #ef4444; }

    .btn-action {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .btn-toggle-suspend {
      background: rgba(239, 68, 68, 0.15);
      color: #fca5a5;
      border: 1px solid #ef4444;
    }
    .btn-toggle-suspend:hover {
      background: #ef4444;
      color: #fff;
    }
    .btn-toggle-activate {
      background: rgba(16, 185, 129, 0.15);
      color: #6ee7b7;
      border: 1px solid #10b981;
    }
    .btn-toggle-activate:hover {
      background: #10b981;
      color: #fff;
    }
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.7);
      backdrop-filter: blur(5px);
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal.active { display: flex; }
    .modal-content {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 30px;
      width: 100%;
      max-width: 500px;
    }
  </style>
</head>
<body>

  <header class="top-header">
    <div class="logo-area">
      <i class='bx bx-shield-quarter'></i> MedLife SaaS <span class="badge-super">Super Admin</span>
    </div>
    <div class="header-right">
      <?php if ($is_super_admin): ?>
        <span><i class='bx bx-user-circle'></i> <?php echo htmlspecialchars($_SESSION['super_admin_name']); ?></span>
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
          <button type="submit" name="btnSuperAdminLogin" class="btn-primary">
            <i class='bx bx-log-in-circle'></i> Login to SaaS Portal
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
            <div class="stat-label">Total Pharmacies</div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon icon-active"><i class='bx bx-check-circle'></i></div>
          <div>
            <div class="stat-value"><?php echo $active_pharmacies; ?></div>
            <div class="stat-label">Active Tenants</div>
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
            <div class="stat-label">Platform Gross Volume</div>
          </div>
        </div>
      </div>

      <!-- Pharmacy Management Table -->
      <div class="section-header">
        <h2 class="section-title"><i class='bx bx-list-ul'></i> Registered Pharmacy Tenants</h2>
        <button class="btn-primary" style="width: auto; padding: 10px 20px;" onclick="document.getElementById('addModal').classList.add('active')">
          <i class='bx bx-plus-circle'></i> Add New Pharmacy
        </button>
      </div>

      <div class="data-table-card">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Pharmacy Name</th>
              <th>Owner Email</th>
              <th>Plan</th>
              <th>Products</th>
              <th>Orders</th>
              <th>Revenue</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pharmacies_list as $p): ?>
              <tr>
                <td><strong>#<?php echo $p['pharmacy_id']; ?></strong></td>
                <td>
                  <strong><?php echo htmlspecialchars($p['name']); ?></strong><br>
                  <small style="color: var(--text-muted);">Slug: /<?php echo htmlspecialchars($p['slug']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td><span style="background: rgba(99, 102, 241, 0.15); color: #818cf8; padding: 2px 8px; border-radius: 4px; font-size: 12px;"><?php echo htmlspecialchars($p['plan']); ?></span></td>
                <td><?php echo $p['prod_count']; ?></td>
                <td><?php echo $p['order_count']; ?></td>
                <td><strong>रु. <?php echo number_format($p['revenue'], 2); ?></strong></td>
                <td>
                  <?php if ((int)$p['status'] === 1): ?>
                    <span class="status-badge status-active"><i class='bx bx-check'></i> Active</span>
                  <?php else: ?>
                    <span class="status-badge status-suspended"><i class='bx bx-block'></i> Suspended</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ((int)$p['status'] === 1): ?>
                    <a href="saas_admin.php?toggle_status=<?php echo $p['pharmacy_id']; ?>&current=1" class="btn-action btn-toggle-suspend" onclick="return confirm('Are you sure you want to suspend this pharmacy store?')">
                      <i class='bx bx-pause-circle'></i> Suspend
                    </a>
                  <?php else: ?>
                    <a href="saas_admin.php?toggle_status=<?php echo $p['pharmacy_id']; ?>&current=0" class="btn-action btn-toggle-activate">
                      <i class='bx bx-play-circle'></i> Activate
                    </a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Modal for Manual Pharmacy Creation -->
    <div class="modal" id="addModal">
      <div class="modal-content">
        <h3 style="margin-bottom: 16px; font-size: 18px;"><i class='bx bx-plus-circle'></i> Add Pharmacy Tenant</h3>
        <form action="saas_admin.php" method="POST">
          <div class="form-group">
            <label class="form-label">Pharmacy Name</label>
            <input type="text" name="p_name" class="form-control" placeholder="e.g. Apex Pharma" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="p_email" class="form-control" placeholder="admin@apexpharma.com" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="p_phone" class="form-control" placeholder="98XXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label">Subscription Plan</label>
            <select name="p_plan" class="form-control">
              <option value="Starter">Starter</option>
              <option value="Pro" selected>Pro</option>
              <option value="Enterprise">Enterprise</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Delivery Fee (रु.)</label>
            <input type="number" name="p_fee" class="form-control" value="100">
          </div>
          <div style="display: flex; gap: 10px; margin-top: 24px;">
            <button type="submit" name="btnCreatePharmacy" class="btn-primary">Create Tenant</button>
            <button type="button" class="btn-logout" style="cursor: pointer;" onclick="document.getElementById('addModal').classList.remove('active')">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  <?php endif; ?>

</body>
</html>
