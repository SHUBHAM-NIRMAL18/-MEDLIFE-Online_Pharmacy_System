<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login_status']) || !isset($_SESSION['user_id'])) {
    header('location:customer_login.php?msg=1');
    exit();
}

$uid = (int)$_SESSION['user_id'];
$conn = get_db_connection();

// Fetch current user details
$user_stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
$user_data = [];
if ($user_stmt) {
    $user_stmt->bind_param("i", $uid);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    if ($user_res && $user_res->num_rows > 0) {
        $user_data = $user_res->fetch_assoc();
    }
    $user_stmt->close();
}

$page_title = "My Dashboard";
$page_css = "css/dashboard.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    <div class="dashboard-container">
        
        <!-- Sidebar Navigation -->
        <aside class="dashboard-sidebar">
            <div class="user-profile-header">
                <div class="user-avatar">
                    <?php echo substr($user_data['name'], 0, 1); ?>
                </div>
                <h3>Welcome, <?php echo htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="user_dashboard.php" class="sidebar-nav-link active">
                    <i class="bx bx-package"></i> Order History
                </a>
                <a href="personal_info.php" class="sidebar-nav-link">
                    <i class="bx bx-user-circle"></i> Personal Info
                </a>
                <a href="change_password.php" class="sidebar-nav-link">
                    <i class="bx bx-shield-quarter"></i> Change Password
                </a>
                <a href="user_logout.php" class="sidebar-nav-link logout">
                    <i class="bx bx-log-out"></i> Logout Account
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <section class="dashboard-content">
            <div class="tab-pane active">
                <h2>Order History</h2>
                
                <?php
                $order_stmt = $conn->prepare("SELECT * FROM tbl_order WHERE user_id = ? ORDER BY order_id DESC");
                if ($order_stmt) {
                    $order_stmt->bind_param("i", $uid);
                    $order_stmt->execute();
                    $order_run = $order_stmt->get_result();
                    if ($order_run && $order_run->num_rows > 0):
                ?>
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Tracking No</th>
                                    <th>Date</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($items = $order_run->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $items['order_id']; ?></td>
                                        <td style="font-family: monospace; font-weight: 500;"><?php echo htmlspecialchars($items['tracking_order'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo date("M d, Y", strtotime($items['created_at'])); ?></td>
                                        <td style="font-weight: 600; color: var(--text-main);">Rs. <?php echo number_format($items['total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            if ($items['status'] == 0) {
                                                echo "<span class='status-badge process'>Under Process</span>";
                                            } elseif ($items['status'] == 1) {
                                                echo "<span class='status-badge completed'>Completed</span>";
                                            } elseif ($items['status'] == 2) {
                                                echo "<span class='status-badge cancelled'>Cancelled</span>";
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                <?php 
                    else: 
                ?>
                        <div style="text-align: center; padding: 40px 0; color: var(--text-light);">
                            <i class="bx bx-receipt" style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                            <p>You haven't placed any pharmacy orders yet.</p>
                            <a href="index.php" class="btn btn-primary" style="margin-top: 16px;">Browse Medicines</a>
                        </div>
                <?php 
                    endif;
                    $order_stmt->close();
                }
                ?>
            </div>
        </section>

    </div>
</main>

<?php include('footer.php'); ?>