<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_login']) || !isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
  header('location:admin_login.php?msg=1');
  exit();
}
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

    <!-- Sidebar -->
    <nav class="admin-sidebar" id="adminSidebar">
      <div class="sidebar-brand">
        <div class="brand-icon"><i class="bx bx-plus-medical"></i></div>
        <div class="brand-text">Medlife<small>Admin Panel</small></div>
      </div>

      <div class="sidebar-nav-section">

        <!-- Overview -->
        <div class="sidebar-section-title">Overview</div>
        <a href="admin_home.php" class="sidebar-link active">
          <i class="bx bx-grid-alt"></i><span>Dashboard</span>
        </a>

        <!-- Catalog -->
        <div class="sidebar-section-title">Catalog</div>

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

        <!-- Operations -->
        <div class="sidebar-section-title">Operations</div>

        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bx-cart"></i><span>Orders</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="admin_order.php" class="sidebar-sublink">View Orders</a>
        </div>

        <div class="sidebar-link sidebar-submenu-toggle" onclick="toggleSubmenu(this)">
          <i class="bx bx-user"></i><span>Accounts</span>
          <i class="bx bx-chevron-right submenu-arrow"></i>
        </div>
        <div class="sidebar-submenu">
          <a href="admin_register1.php" class="sidebar-sublink">Add Admin / Manager</a>
          <a href="view_user.php" class="sidebar-sublink">Manage Accounts</a>
        </div>

      </div>
    </nav>

    <!-- Top Bar -->
    <header class="admin-topbar">
      <div class="topbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="bx bx-menu"></i>
        </button>
        <h2>Admin Panel</h2>
      </div>
      <div class="topbar-right">
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