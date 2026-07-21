<?php
error_reporting(0);
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calculate total cart items
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += isset($item['quantity']) ? (int)$item['quantity'] : 0;
    }
}

// Determine active page
$active_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title . " - Medlife" : "Medlife - Convenient & Reliable Healthcare"; ?></title>
  
  <!-- Fonts & Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/header.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="css/footer.css?v=<?php echo time(); ?>">
  <?php if (isset($page_css)): ?>
      <link rel="stylesheet" href="<?php echo htmlspecialchars($page_css, ENT_QUOTES, 'UTF-8'); ?>?v=<?php echo time(); ?>">
  <?php endif; ?>
</head>
<body>
<?php 
$is_admin = isset($_SESSION['admin_login']) && isset($_SESSION['admin_id']);
$is_user = isset($_SESSION['user_login']) && isset($_SESSION['user_id']);
?>

<div class="site-wrapper">
  
  <!-- Top Bar -->
  <div class="top-bar">
    <div class="content-container top-bar-container">
      <div class="welcome-msg">
        <span>
          <?php 
          if ($is_admin) { 
              echo "<i class='bx bx-shield-quarter' style='color: #059669; font-size: 16px; margin-right: 4px;'></i> Welcome Admin, <strong>" . htmlspecialchars($_SESSION['admin_name'], ENT_QUOTES, 'UTF-8') . "</strong>";
          } elseif ($is_user) {
              echo "<i class='bx bx-user-circle' style='font-size: 16px; margin-right: 4px;'></i> Welcome, <strong>" . htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . "</strong>";
          } else {
              echo "<i class='bx bx-user-circle' style='font-size: 16px; margin-right: 4px;'></i> Welcome, <strong>Guest</strong>";
          } 
          ?>
        </span>
      </div>
      <div class="top-links">
        <a href="track_order.php" class="top-link"><i class="bx bx-map-pin"></i> Track Order</a>
        <span class="divider">|</span>
        <?php if ($is_admin): ?>
            <a href="admin_home.php" class="top-link" style="color: #059669; font-weight: 700;"><i class="bx bx-layout"></i> Admin Dashboard</a>
            <span class="divider">|</span>
            <a href="admin_logout.php" class="top-link" style="color: #dc2626;"><i class="bx bx-log-out"></i> Logout Admin</a>
        <?php elseif ($is_user): ?>
            <a href="user_dashboard.php" class="top-link"><i class="bx bx-user"></i> My Account</a>
        <?php else: ?>
            <a href="admin_login.php" class="top-link"><i class="bx bx-lock-alt"></i> Admin Login</a>
            <span class="divider">|</span>
            <a href="user_dashboard.php" class="top-link"><i class="bx bx-cog"></i> My Account</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Main Navigation Header -->
  <header class="site-header">
    <div class="content-container header-container">
      <a href="index.php" class="logo-link">
        <img src="logo/MEDLOGO.png" alt="Medlife Logo">
      </a>
      
<?php 
// Fetch active top-level root categories and their subcategories for header menu
$conn_header = get_db_connection();
$root_categories = [];
$root_res = $conn_header->query("SELECT * FROM tbl_categories WHERE parent_id = 0 AND cat_status = 1 ORDER BY cat_name ASC");
if ($root_res && $root_res->num_rows > 0) {
    while ($rc = $root_res->fetch_assoc()) {
        $rc_id = (int)$rc['cat_id'];
        $subs_res = $conn_header->query("SELECT * FROM tbl_categories WHERE parent_id = $rc_id AND cat_status = 1 ORDER BY cat_name ASC");
        $subs = [];
        if ($subs_res && $subs_res->num_rows > 0) {
            while ($sc = $subs_res->fetch_assoc()) {
                $subs[] = $sc;
            }
        }
        $rc['subcategories'] = $subs;
        $root_categories[] = $rc;
    }
}
?>

      <nav>
        <ul class="nav-menu">
          <li><a href="index.php" class="nav-link <?php echo ($active_page == 'index.php') ? 'active' : ''; ?>">HOME</a></li>
          <li><a href="search_products.php" class="nav-link <?php echo ($active_page == 'search_products.php') ? 'active' : ''; ?>">ALL PRODUCTS</a></li>
          <li><a href="u_medicines.php" class="nav-link <?php echo ($active_page == 'u_medicines.php') ? 'active' : ''; ?>">MEDICINES</a></li>
          <li><a href="u_supplements.php" class="nav-link <?php echo ($active_page == 'u_supplements.php') ? 'active' : ''; ?>">SUPPLEMENTS</a></li>
          <li><a href="u_devices.php" class="nav-link <?php echo ($active_page == 'u_devices.php') ? 'active' : ''; ?>">DEVICES</a></li>

          <!-- Categories Hover Dropdown -->
          <li class="nav-dropdown-item">
              <a href="search_products.php" class="nav-link">
                  CATEGORIES <i class="bx bx-chevron-down dropdown-arrow"></i>
              </a>

              <?php if (!empty($root_categories)): ?>
                  <ul class="nav-sub-menu">
                      <?php foreach ($root_categories as $rcat): ?>
                          <li>
                              <a href="search_products.php?cat=<?php echo $rcat['cat_id']; ?>" style="font-weight: 700; color: #0f172a;">
                                  <i class="bx bx-folder"></i> <?php echo htmlspecialchars($rcat['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                              </a>
                          </li>
                          <?php if (!empty($rcat['subcategories'])): ?>
                              <?php foreach ($rcat['subcategories'] as $subcat): ?>
                                  <li>
                                      <a href="search_products.php?cat=<?php echo $subcat['cat_id']; ?>" style="padding-left: 28px; font-size: 13px;">
                                          <i class="bx bx-subdirectory-right"></i> <?php echo htmlspecialchars($subcat['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                      </a>
                                  </li>
                              <?php endforeach; ?>
                          <?php endif; ?>
                      <?php endforeach; ?>
                      <li style="border-top: 1px solid #f1f5f9; margin-top: 4px; padding-top: 4px;">
                          <a href="search_products.php" style="color: #059669; font-weight: 700;">
                              <i class="bx bx-filter-alt"></i> Browse All & Filter
                          </a>
                      </li>
                  </ul>
              <?php endif; ?>
          </li>
        </ul>
      </nav>
      
      <div class="nav-actions">
        <?php if ($is_admin): ?>
            <a href="admin_home.php" class="btn btn-primary" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none;"><i class="bx bx-layout"></i> DASHBOARD</a>
            <a href="admin_logout.php" class="btn btn-outline">LOGOUT</a>
        <?php elseif ($is_user): ?>
            <a href="user_dashboard.php" class="btn btn-primary">MY ACCOUNT</a>
            <a href="user_logout.php" class="btn btn-outline">LOGOUT</a>
        <?php else: ?>
            <a href="customer_login.php" class="btn btn-outline">LOGIN</a>
            <a href="customer_register.php" class="btn btn-primary">SIGNUP</a>
        <?php endif; ?>
        
        <?php 
        $wishlist_count = isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
        ?>
        <a href="wishlist.php" class="wishlist-icon-btn" title="View Saved Wishlist">
          <i class="bx bx-heart"></i>
          <span class="wishlist-badge" id="headerWishlistBadge"><?php echo $wishlist_count; ?></span>
        </a>

        <a href="cart.php" class="cart-icon-btn" title="View Cart">
          <i class="bx bx-cart"></i>
          <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?php echo $cart_count; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </header>

  <!-- Toast Notifications Container -->
  <div class="toast-container" id="toastContainer">
    <?php if (isset($_SESSION['toast'])): 
        $toast = $_SESSION['toast'];
        $toast_type = isset($toast['type']) ? $toast['type'] : 'success';
        $toast_title = isset($toast['title']) ? $toast['title'] : 'Success';
        $toast_msg = isset($toast['message']) ? $toast['message'] : '';
        
        $icon_class = 'bx bx-check-circle success';
        $border_class = 'success-border';
        if ($toast_type == 'error') {
            $icon_class = 'bx bx-error-circle error';
            $border_class = 'error-border';
        } elseif ($toast_type == 'info') {
            $icon_class = 'bx bx-info-circle info';
            $border_class = 'info-border';
        }
    ?>
      <div class="toast-card <?php echo $border_class; ?>" id="sessionToast">
        <div class="toast-icon <?php echo $toast_type; ?>"><i class="<?php echo $icon_class; ?>"></i></div>
        <div class="toast-content">
          <div class="toast-title"><?php echo htmlspecialchars($toast_title, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="toast-message"><?php echo htmlspecialchars($toast_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
        <button class="toast-close" onclick="closeToast(this)"><i class="bx bx-x"></i></button>
      </div>
      
      <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toast = document.getElementById('sessionToast');
            if (toast) {
                // Slide in
                setTimeout(function() {
                    toast.classList.add('show');
                }, 100);
                
                // Auto close after 4.5 seconds
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 4500);
            }
        });
        
        function closeToast(btn) {
            var card = btn.closest('.toast-card');
            if (card) {
                card.classList.remove('show');
            }
        }

        function toggleWishlist(btn, event) {
            if (event) event.preventDefault();
            var icon = btn.querySelector('i');
            if (btn.classList.contains('active')) {
                btn.classList.remove('active');
                if (icon) icon.className = 'bx bx-heart';
                showDynamicToast('info', 'Wishlist', 'Item removed from your wishlist.');
            } else {
                btn.classList.add('active');
                if (icon) icon.className = 'bx bxs-heart';
                showDynamicToast('success', 'Wishlist', 'Item saved to your wishlist!');
            }
        }

        function showDynamicToast(type, title, message) {
            var container = document.getElementById('sessionToast');
            if (!container) {
                container = document.createElement('div');
                container.id = 'sessionToast';
                container.className = 'toast-container';
                document.body.appendChild(container);
            }
            
            var iconClass = type === 'success' ? 'bx-check-circle success' : 'bx-info-circle info';
            var borderClass = type === 'success' ? 'success-border' : 'info-border';
            
            var toastHtml = document.createElement('div');
            toastHtml.className = 'toast-card ' + borderClass;
            toastHtml.innerHTML = '<div class="toast-icon ' + type + '"><i class="bx ' + iconClass + '"></i></div>' +
                '<div class="toast-content"><div class="toast-title">' + title + '</div><div class="toast-message">' + message + '</div></div>' +
                '<button class="toast-close" onclick="closeToast(this)"><i class="bx bx-x"></i></button>';
                
            container.appendChild(toastHtml);
            setTimeout(function() { toastHtml.classList.add('show'); }, 50);
            setTimeout(function() {
                toastHtml.classList.remove('show');
                setTimeout(function() { toastHtml.remove(); }, 400);
            }, 3500);
        }
      </script>
    <?php 
        unset($_SESSION['toast']);
    endif; 
    ?>
  </div>

  <!-- Custom Logout Confirmation Modal Overlay -->
  <div class="modal-overlay" id="confirmLogoutModal">
      <div class="confirm-modal-card" style="max-width: 380px;">
          <div class="confirm-modal-icon" style="color: var(--danger);">
              <i class="bx bx-log-out-circle"></i>
          </div>
          <h4>End Session?</h4>
          <p>Are you sure you want to log out of your Medlife account?</p>
          <div class="confirm-modal-actions">
              <button class="btn btn-outline" id="btnCancelLogout">Cancel</button>
              <a href="user_logout.php" class="btn btn-primary" style="background-color: var(--danger); border-color: var(--danger);">Logout</a>
          </div>
      </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Intercept all customer logout links (except the confirm link inside the modal)
        var logoutLinks = document.querySelectorAll('a[href="user_logout.php"]');
        var logoutModal = document.getElementById('confirmLogoutModal');
        var cancelLogoutBtn = document.getElementById('btnCancelLogout');

        if (logoutModal && cancelLogoutBtn) {
            logoutLinks.forEach(function(link) {
                if (link.closest('#confirmLogoutModal')) return;
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    logoutModal.classList.add('show');
                });
            });

            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.classList.remove('show');
            });

            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.remove('show');
                }
            });
        }
    });

    // Global Wishlist Toggle Function
    function toggleWishlist(btn, event, productId) {
        if (event) {
            event.stopPropagation();
            event.preventDefault();
        }
        if (!productId) {
            var card = btn.closest('.product-card');
            if (card) {
                var link = card.querySelector('a[href^="single.php?id="]');
                if (link) {
                    var match = link.href.match(/id=(\d+)/);
                    if (match) productId = match[1];
                }
            }
        }
        if (!productId) {
            var urlParams = new URLSearchParams(window.location.search);
            productId = urlParams.get('id');
        }
        if (!productId) return;

        fetch('toggle_wishlist.php?id=' + productId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    var icon = btn.querySelector('i');
                    if (data.action === 'added') {
                        btn.classList.add('active');
                        if (icon) icon.className = 'bx bxs-heart';
                    } else {
                        btn.classList.remove('active');
                        if (icon) icon.className = 'bx bx-heart';
                    }
                    var badge = document.getElementById('headerWishlistBadge');
                    if (badge) badge.textContent = data.count;
                }
            })
            .catch(err => console.error("Wishlist error:", err));
    }
  </script>