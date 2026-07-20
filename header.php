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
  
  <!-- Global Stylesheet -->
  <link rel="stylesheet" href="css/global.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="site-wrapper">
  
  <!-- Top Bar -->
  <div class="top-bar">
    <div class="content-container top-bar-container">
      <div class="welcome-msg">
        <i class="bx bx-user-circle"></i>
        <span>
          <?php 
          if (isset($_SESSION['email'])) { 
              echo "Welcome, <strong>" . htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') . "</strong>";
          } else {
              echo "Welcome, <strong>Guest</strong>";
          } 
          ?>
        </span>
      </div>
      <div class="top-links">
        <a href="admin_login.php" class="top-link"><i class="bx bx-lock-alt"></i> Admin Login</a>
        <span class="divider">|</span>
        <a href="user_dashboard.php" class="top-link"><i class="bx bx-cog"></i> My Account</a>
      </div>
    </div>
  </div>

  <!-- Main Navigation Header -->
  <header class="site-header">
    <div class="content-container header-container">
      <a href="index.php" class="logo-link">
        <img src="logo/MEDLOGO.png" alt="Medlife Logo">
      </a>
      
      <nav>
        <ul class="nav-menu">
          <li><a href="index.php" class="nav-link <?php echo ($active_page == 'index.php') ? 'active' : ''; ?>">HOME</a></li>
          <li><a href="u_medicines.php" class="nav-link <?php echo ($active_page == 'u_medicines.php') ? 'active' : ''; ?>">MEDICINES</a></li>
          <li><a href="u_supplements.php" class="nav-link <?php echo ($active_page == 'u_supplements.php') ? 'active' : ''; ?>">SUPPLEMENTS</a></li>
          <li><a href="u_devices.php" class="nav-link <?php echo ($active_page == 'u_devices.php') ? 'active' : ''; ?>">DEVICES</a></li>
        </ul>
      </nav>
      
      <div class="nav-actions">
        <?php if (!isset($_SESSION['email'])): ?>
            <a href="customer_login.php" class="btn btn-outline">LOGIN</a>
            <a href="customer_register.php" class="btn btn-primary">SIGNUP</a>
        <?php else: ?>
            <a href="user_logout.php" class="btn btn-outline">LOGOUT</a>
        <?php endif; ?>
        
        <a href="cart.php" class="cart-icon-btn" title="View Cart">
          <i class="bx bx-cart"></i>
          <?php if ($cart_count > 0): ?>
              <span class="cart-badge"><?php echo $cart_count; ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </header>