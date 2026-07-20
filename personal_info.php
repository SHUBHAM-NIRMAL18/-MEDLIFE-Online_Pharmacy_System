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
                header('location:personal_info.php');
                exit();
            } else {
                $error_profile = "Something went wrong. Please try again.";
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

$page_title = "Personal Information";
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
                <a href="personal_info.php" class="sidebar-nav-link active">
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
                <h2>Personal Information</h2>
                
                <?php if (!empty($error_profile)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_profile, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" class="dashboard-form" novalidate>
                    
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
        </section>

    </div>
</main>

<?php include('footer.php'); ?>
