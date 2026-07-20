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

$error_profile = '';
$error_password = '';

// Handle Profile Info Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdateProfile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';

    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        $error_profile = "All fields except gender are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_profile = "Please enter a valid email address.";
    } else {
        // Check if email is already taken by another user
        $email_check = $conn->prepare("SELECT user_id FROM tbl_user WHERE email = ? AND user_id != ?");
        if ($email_check) {
            $email_check->bind_param("si", $email, $uid);
            $email_check->execute();
            $email_check->store_result();
            if ($email_check->num_rows > 0) {
                $error_profile = "This email is already in use by another account.";
            }
            $email_check->close();
        }
    }

    if (empty($error_profile)) {
        $stmt = $conn->prepare("UPDATE tbl_user SET name = ?, email = ?, phone = ?, address = ?, gender = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $gender, $uid);
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'title' => 'Profile Updated',
                    'message' => 'Your personal details have been updated successfully.'
                ];
                header('location:user_dashboard.php?tab=profile');
                exit();
            } else {
                $error_profile = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Handle Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdatePassword'])) {
    $current_pass = md5($_POST['current_password']);
    $new_pass_val = $_POST['new_password'];
    $confirm_pass = md5($_POST['confirm_password']);

    // Check if current password is correct
    $pass_check = $conn->prepare("SELECT password FROM tbl_user WHERE user_id = ?");
    if ($pass_check) {
        $pass_check->bind_param("i", $uid);
        $pass_check->execute();
        $pass_res = $pass_check->get_result();
        if ($pass_res && $pass_res->num_rows > 0) {
            $pass_row = $pass_res->fetch_assoc();
            if ($pass_row['password'] !== $current_pass) {
                $error_password = "Your current password is incorrect.";
            }
        }
        $pass_check->close();
    }

    if (empty($error_password)) {
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})/", $new_pass_val)) {
            $error_password = "New password must be 8+ chars with uppercase, lowercase, and digits.";
        } elseif (md5($new_pass_val) !== $confirm_pass) {
            $error_password = "New password and confirmation do not match.";
        }
    }

    if (empty($error_password)) {
        $new_pass = md5($new_pass_val);
        $stmt = $conn->prepare("UPDATE tbl_user SET password = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_pass, $uid);
            if ($stmt->execute()) {
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'title' => 'Password Changed',
                    'message' => 'Your security password has been changed successfully.'
                ];
                header('location:user_dashboard.php?tab=password');
                exit();
            } else {
                $error_password = "Something went wrong. Please try again.";
            }
            $stmt->close();
        }
    }
}

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
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'orders';
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    <div class="dashboard-container">
        
        <!-- Sidebar -->
        <aside class="dashboard-sidebar">
            <div class="user-profile-header">
                <div class="user-avatar">
                    <?php echo substr($user_data['name'], 0, 1); ?>
                </div>
                <h3>Welcome, <?php echo htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            
            <nav class="sidebar-nav">
                <button class="sidebar-nav-link <?php echo $active_tab === 'orders' ? 'active' : ''; ?>" onclick="switchTab('orders')">
                    <i class="bx bx-package"></i> Order History
                </button>
                <button class="sidebar-nav-link <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" onclick="switchTab('profile')">
                    <i class="bx bx-user-circle"></i> Personal Info
                </button>
                <button class="sidebar-nav-link <?php echo $active_tab === 'password' ? 'active' : ''; ?>" onclick="switchTab('password')">
                    <i class="bx bx-shield-quarter"></i> Change Password
                </button>
                <a href="user_logout.php" class="sidebar-nav-link logout">
                    <i class="bx bx-log-out"></i> Logout Account
                </a>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <section class="dashboard-content">
            
            <!-- Tab Pane 1: Orders -->
            <div class="tab-pane <?php echo $active_tab === 'orders' ? 'active' : ''; ?>" id="pane-orders">
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

            <!-- Tab Pane 2: Profile Settings -->
            <div class="tab-pane <?php echo $active_tab === 'profile' ? 'active' : ''; ?>" id="pane-profile">
                <h2>Personal Information</h2>
                
                <?php if (!empty($error_profile)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_profile, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="user_dashboard.php?tab=profile" class="dashboard-form">
                    
                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Enter Full Name" required value="<?php echo htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Enter Email" required value="<?php echo htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter Phone" required value="<?php echo htmlspecialchars($user_data['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Gender</label>
                            <div style="display: flex; gap: 20px; align-items: center; height: 42px;">
                                <label class="gender-radio" style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                                    <input type="radio" name="gender" value="male" <?php echo strtolower($user_data['gender']) === 'male' ? 'checked' : ''; ?> style="accent-color: var(--primary);"> Male
                                </label>
                                <label class="gender-radio" style="display: flex; align-items: center; gap: 6px; font-weight: normal; margin-bottom: 0; cursor: pointer;">
                                    <input type="radio" name="gender" value="female" <?php echo strtolower($user_data['gender']) === 'female' ? 'checked' : ''; ?> style="accent-color: var(--primary);"> Female
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <textarea id="address" name="address" class="form-control" placeholder="Street Address, City" style="height: 100px; resize: none;" required><?php echo htmlspecialchars($user_data['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <button type="submit" name="btnUpdateProfile" class="btn btn-primary" style="margin-top: 10px;">
                        <i class="bx bx-save"></i> Save Changes
                    </button>
                    
                </form>
            </div>

            <!-- Tab Pane 3: Password Update -->
            <div class="tab-pane <?php echo $active_tab === 'password' ? 'active' : ''; ?>" id="pane-password">
                <h2>Security Settings</h2>
                
                <?php if (!empty($error_password)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_password, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="user_dashboard.php?tab=password" class="dashboard-form">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Minimum 8 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-type new password" required>
                    </div>

                    <button type="submit" name="btnUpdatePassword" class="btn btn-primary" style="margin-top: 10px;">
                        <i class="bx bx-key"></i> Update Password
                    </button>
                    
                </form>
            </div>

        </section>
    </div>
</main>

<script>
function switchTab(tabId) {
    // Update active class on nav links
    document.querySelectorAll('.sidebar-nav-link').forEach(function(link) {
        link.classList.remove('active');
    });
    
    // Add active to selected link
    var eventBtn = Array.from(document.querySelectorAll('.sidebar-nav-link')).find(btn => btn.getAttribute('onclick').includes(tabId));
    if (eventBtn) eventBtn.classList.add('active');

    // Hide all panes
    document.querySelectorAll('.tab-pane').forEach(function(pane) {
        pane.classList.remove('active');
    });

    // Show active pane
    var targetPane = document.getElementById('pane-' + tabId);
    if (targetPane) {
        targetPane.classList.add('active');
    }

    // Update query parameter without reload
    var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabId;
    window.history.pushState({ path: newUrl }, '', newUrl);
}
</script>

<?php include('footer.php'); ?>