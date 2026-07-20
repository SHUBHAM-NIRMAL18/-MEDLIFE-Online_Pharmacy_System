<?php 
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();

// Fetch all stat counts in a single connection
$totalProducts = 0;
$totalCategories = 0;
$totalOrders = 0;
$totalCustomers = 0;

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_products");
if ($res) { $totalProducts = $res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_categories");
if ($res) { $totalCategories = $res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_order");
if ($res) { $totalOrders = $res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_user");
if ($res) { $totalCustomers = $res->fetch_assoc()['cnt']; }
?>

  <div class="admin-page-wrapper">

    <div class="admin-page-header">
      <h1>Dashboard</h1>
      <p>Welcome back, <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?>. Here's an overview of your pharmacy.</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">

      <div class="stat-card">
        <div class="stat-card-icon products">
          <i class="bx bx-package"></i>
        </div>
        <div class="stat-card-info">
          <h3><?php echo $totalProducts; ?></h3>
          <p>Total Products</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card-icon categories">
          <i class="bx bx-category-alt"></i>
        </div>
        <div class="stat-card-info">
          <h3><?php echo $totalCategories; ?></h3>
          <p>Categories</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card-icon orders">
          <i class="bx bx-receipt"></i>
        </div>
        <div class="stat-card-info">
          <h3><?php echo $totalOrders; ?></h3>
          <p>Total Orders</p>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-card-icon customers">
          <i class="bx bx-group"></i>
        </div>
        <div class="stat-card-info">
          <h3><?php echo $totalCustomers; ?></h3>
          <p>Registered Customers</p>
        </div>
      </div>

    </div>

    <!-- Recent Orders Table -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Recent Orders</h3>
        <a href="admin_order.php" class="admin-btn view">View All <i class="bx bx-right-arrow-alt"></i></a>
      </div>

      <table class="admin-table">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Tracking No</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $orders = $conn->query("SELECT * FROM tbl_order ORDER BY order_id DESC LIMIT 5");
          if ($orders && $orders->num_rows > 0):
            while ($order = $orders->fetch_assoc()):
          ?>
            <tr>
              <td>#<?php echo $order['order_id']; ?></td>
              <td style="font-family: monospace; font-weight: 500;"><?php echo htmlspecialchars($order['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($order['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo date("M d, Y", strtotime($order['created_at'])); ?></td>
              <td style="font-weight: 600;">Rs. <?php echo number_format($order['total'], 2); ?></td>
              <td>
                <?php
                if ($order['status'] == 0) {
                  echo '<span class="admin-badge process">Under Process</span>';
                } elseif ($order['status'] == 1) {
                  echo '<span class="admin-badge completed">Completed</span>';
                } elseif ($order['status'] == 2) {
                  echo '<span class="admin-badge cancelled">Cancelled</span>';
                }
                ?>
              </td>
            </tr>
          <?php 
            endwhile;
          else:
          ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 30px; color: var(--admin-text-light);">
                No orders found yet.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

  </main>
  </body>
</html>
