<?php 
require_once 'config.php';
$active_admin_pharmacy_id = require_admin_tenant();
$active_pharmacy_info = get_pharmacy_details($active_admin_pharmacy_id);
$current_staff_role = get_current_admin_role();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/admin.css" />
  </head>
  <body class="admin-shell">

    <?php if (isset($_SESSION['impersonating_super_admin']) && $_SESSION['impersonating_super_admin'] === true): ?>
      <!-- Super Admin Impersonation Notice Bar -->
      <div style="background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%); color: #ffffff; padding: 8px 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; z-index: 9999; position: relative;">
        <div>
          <i class='bx bx-user-pin' style='font-size: 16px; vertical-align: middle;'></i>
          SUPER ADMIN IMPERSONATION: Currently managing <strong><?php echo htmlspecialchars($active_pharmacy_info['name']); ?></strong> (Tenant #<?php echo $active_admin_pharmacy_id; ?>)
        </div>
        <a href="saas_admin.php?exit_impersonation=1" style="background: #ffffff; color: #7c3aed; padding: 4px 12px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
          <i class='bx bx-exit'></i> Exit Impersonation & Return to SaaS Portal
        </a>
      </div>
    <?php endif; ?>

    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
      <div class="sidebar-brand">
        <div class="brand-icon"><i class="bx bx-plus-medical"></i></div>
        <div class="brand-text">Medlife<small>Tenant Admin</small></div>
      </div>

      <div class="sidebar-nav-section">

        <!-- Overview -->
        <div class="sidebar-section-title">Overview</div>
        <a href="admin_home.php" class="sidebar-link active">
          <i class="bx bx-grid-alt"></i><span>Dashboard</span>
        </a>

        <!-- Point of Sale (POS) -->
        <div class="sidebar-section-title">Point of Sale (POS)</div>
        <a href="pos.php" class="sidebar-link" style="background: linear-gradient(90deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.05) 100%); border-left: 3px solid #10b981; color: #10b981; font-weight: 700;">
          <i class="bx bx-desktop" style="color: #10b981; font-size: 18px;"></i>
          <span>POS Terminal</span>
          <span style="margin-left: auto; background: #10b981; color: #ffffff; font-size: 10px; padding: 2px 6px; border-radius: 4px; font-weight: 800;">LIVE</span>
        </a>

        <?php if ($current_staff_role !== 'cashier'): ?>
        <!-- Catalog & Inventory -->
        <div class="sidebar-section-title">Catalog & Inventory</div>

        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bx-category-alt"></i><span>Categories</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="categories.php" class="sidebar-sublink">Add Category</a>
          <a href="viewcat.php" class="sidebar-sublink">View Categories</a>
        </div>

        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bxs-component"></i><span>Products</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="products.php" class="sidebar-sublink">Add Product</a>
          <a href="view_products.php" class="sidebar-sublink">Manage Products</a>
        </div>

        <a href="batch_management.php" class="sidebar-link">
          <i class="bx bx-barcode-reader"></i><span>Batch & Expiry Tracker</span>
        </a>
        <?php else: ?>
        <div class="sidebar-section-title">Product Catalog</div>
        <a href="view_products.php" class="sidebar-link">
          <i class="bx bxs-component"></i><span>View Products</span>
        </a>
        <?php endif; ?>

        <!-- Operations -->
        <div class="sidebar-section-title">Operations & Sales</div>

        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bx-cart"></i><span>Orders & Billing</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="pos_history.php" class="sidebar-sublink"><i class="bx bx-receipt"></i> POS Sales History</a>
          <?php if ($current_staff_role !== 'cashier'): ?>
            <?php
            $conn_dash = get_db_connection();
            $dash_pending_rx_cnt = 0;
            $dash_rx_res = $conn_dash->query("SELECT (
                (SELECT COUNT(*) FROM tbl_order WHERE pharmacy_id = $active_admin_pharmacy_id AND prescription IS NOT NULL AND prescription != '' AND (prescription_status = 0 OR prescription_status IS NULL)) + 
                (SELECT COUNT(*) FROM tbl_customer_prescriptions WHERE pharmacy_id = $active_admin_pharmacy_id AND status = 0)
            ) AS cnt");
            if ($dash_rx_res) {
                $dash_pending_rx_cnt = (int)$dash_rx_res->fetch_assoc()['cnt'];
            }
            ?>
            <a href="prescription_management.php" class="sidebar-sublink">
              <i class="bx bx-plus-medical"></i> Prescription Approvals
              <?php if ($dash_pending_rx_cnt > 0): ?>
                <span style="margin-left: auto; background: #f59e0b; color: #ffffff; font-size: 10px; padding: 1px 6px; border-radius: 10px; font-weight: 800;"><?php echo $dash_pending_rx_cnt; ?></span>
              <?php endif; ?>
            </a>
            <a href="admin_order.php" class="sidebar-sublink"><i class="bx bx-globe"></i> Online Orders</a>
            <a href="driver_management.php" class="sidebar-sublink"><i class="bx bx-cycling"></i> Delivery Fleet (Riders)</a>
          <?php endif; ?>
        </div>

        <?php if ($current_staff_role === 'admin'): ?>
        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bx-user"></i><span>Accounts & Store</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="pharmacy_settings.php" class="sidebar-sublink"><i class="bx bx-cog"></i> Store Settings</a>
          <a href="admin_register1.php" class="sidebar-sublink">Add Staff User</a>
          <a href="view_user.php" class="sidebar-sublink">Manage Accounts</a>
        </div>
        <?php endif; ?>

      </div>
    </nav>

    <!-- Top Bar -->
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bx bx-menu"></i>
        </button>
        <h2>Admin Panel</h2>
        <?php if (!empty($active_pharmacy_info['name'])): ?>
          <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 13px; padding: 4px 12px; border-radius: 20px; font-weight: 500; margin-left: 12px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="bx bx-store-alt"></i> <?php echo htmlspecialchars($active_pharmacy_info['name']); ?>
            <span style="font-size: 10px; background: #10b981; color: #fff; padding: 1px 6px; border-radius: 10px; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($active_pharmacy_info['plan'] ?? 'Pro'); ?></span>
          </span>
        <?php endif; ?>

        <!-- Role Badge -->
        <?php 
          $role_badge_style = 'background: rgba(99, 102, 241, 0.15); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.3);';
          $role_icon = 'bx-shield-quarter';
          if ($current_staff_role === 'pharmacist') {
              $role_badge_style = 'background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3);';
              $role_icon = 'bx-plus-medical';
          } elseif ($current_staff_role === 'cashier') {
              $role_badge_style = 'background: rgba(245, 158, 11, 0.15); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.3);';
              $role_icon = 'bx-purchase-tag-alt';
          }
        ?>
        <span style="<?php echo $role_badge_style; ?> font-size: 12px; padding: 3px 10px; border-radius: 16px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; text-transform: capitalize;">
          <i class='bx <?php echo $role_icon; ?>'></i> <?php echo htmlspecialchars($current_staff_role); ?>
        </span>
      </div>
      <div class="topbar-right" style="display: flex; align-items: center; gap: 12px;">
        <a href="pos.php" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);">
          <i class="bx bx-desktop"></i> Open POS Cashier
        </a>
        <a href="index.php?pharmacy=<?php echo $active_admin_pharmacy_id; ?>" target="_blank" class="topbar-store-link" style="color: #059669; background: rgba(5, 150, 105, 0.12); border: 1px solid rgba(5, 150, 105, 0.25); padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
          <i class="bx bx-link-external"></i> Storefront
        </a>
        <span class="admin-name"><span>Welcome,</span> <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?></span>
        <a href="admin_logout.php" class="topbar-logout">
          <i class="bx bx-log-out"></i> Logout
        </a>
      </div>
    </header>

    <!-- Main Content Area (child pages render inside here) -->
    <main class="admin-main-content">

    <!-- Sidebar Toggle & Submenu Script -->
    <script>
      // Sidebar collapse toggle
      document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('adminSidebar').classList.toggle('collapsed');
      });

      // Submenu accordion
      function toggleSubmenu(el) {
        // Close other open submenus
        document.querySelectorAll('.sidebar-submenu-toggle.open').forEach(function(item) {
          if (item !== el) item.classList.remove('open');
        });
        el.classList.toggle('open');
      }

      // Highlight active sidebar link based on current URL
      (function() {
        var currentPage = window.location.pathname.split('/').pop();
        if (!currentPage) return;

        // Check direct links
        document.querySelectorAll('.sidebar-link').forEach(function(link) {
          var href = link.getAttribute('href');
          if (href === currentPage) {
            link.classList.add('active');
          } else {
            link.classList.remove('active');
          }
        });

        // Check sublinks and auto-open parent submenu
        document.querySelectorAll('.sidebar-sublink').forEach(function(link) {
          if (link.getAttribute('href') === currentPage) {
            link.style.color = '#e2e8f0';
            link.style.fontWeight = '500';
            var parentSubmenu = link.closest('.sidebar-submenu');
            if (parentSubmenu) {
              var toggle = parentSubmenu.previousElementSibling;
              if (toggle) toggle.classList.add('open');
            }
          }
        });
      })();
    </script>