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
$error_password = '';

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
                header('location:change_password.php');
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

$page_title = "Change Password";
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
                <a href="user_dashboard.php" class="sidebar-nav-link">
                    <i class="bx bx-package"></i> Order History
                </a>
                <a href="personal_info.php" class="sidebar-nav-link">
                    <i class="bx bx-user-circle"></i> Personal Info
                </a>
                <a href="change_password.php" class="sidebar-nav-link active">
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
                <h2>Security Settings</h2>
                
                <?php if (!empty($error_password)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_password, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-form" novalidate>
                    
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

<?php include('footer.php'); ?>
