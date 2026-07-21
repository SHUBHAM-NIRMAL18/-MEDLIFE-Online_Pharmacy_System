<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_COOKIE['name'])) {
    $_SESSION['name'] = $_COOKIE['name'];
    header('location:dashboard.php');
    exit();
}

$email = '';
$error = '';
$err = [];

if (isset($_POST['login'])) {
    if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Enter a valid email address';
        }
    } else {
        $err['email'] = 'Email is required';
    }

    if (isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])) {
        $password = md5($_POST['password']);
    } else {
        $err['password'] = 'Password is required';
    }

    if (count($err) == 0) {
        try {
            $connection = get_db_connection();
            $stmt = $connection->prepare("SELECT * FROM tbl_admin WHERE email = ? AND password = ? AND status = 1");
            if ($stmt) {
                $stmt->bind_param("ss", $email, $password);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    $_SESSION['admin_login'] = true;
                    $_SESSION['admin_id'] = (int)$row['admin_id'];
                    $_SESSION['admin_email'] = $row['email'];
                    $_SESSION['admin_name'] = $row['name'];
                    $_SESSION['admin_role'] = (int)$row['status'];
                    
                    // Unset any conflicting customer session keys
                    unset($_SESSION['user_login']);
                    unset($_SESSION['user_id']);
                    unset($_SESSION['email']);
                    unset($_SESSION['name']);
                    unset($_SESSION['login_status']);
                    
                    $_SESSION['toast'] = [
                        'type' => 'success',
                        'title' => 'Admin Access',
                        'message' => 'Logged in successfully! Welcome, ' . $row['name'] . '.'
                    ];

                    if (isset($_POST['remember'])) {
                        setcookie('emailcookie', $email, time() + 86400);
                    }
                    header('location:admin_home.php');
                    exit();
                } else {
                    $error = 'Incorrect email or password';
                }
                $stmt->close();
            }
        } catch (Exception $ex) {
            die('Database Error: ' . $ex->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Medlife Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" />
    <link rel="stylesheet" href="css/adminlogin.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="admin-login-wrapper">
    <!-- Admin Portal Header Logo/Icon -->
    <div class="admin-logo-container">
        <img src="logo/admin.png" alt="Admin Portal">
    </div>

    <!-- Login Card -->
    <div class="admin-card">
        <h2>Admin Access</h2>
        <div class="subtitle">Enter your credentials to manage Medlife operations</div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 1): ?>
            <div class="alert-admin">Session expired. Please log in again to continue.</div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-admin"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="post" novalidate>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="admin@medlife.com" required value="<?php echo htmlspecialchars(isset($_COOKIE['emailcookie']) ? $_COOKIE['emailcookie'] : $email, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if (isset($err['email'])): ?>
                    <span style="color: #f87171; font-size: 11.5px; margin-top: 4px; display: block;"><?php echo $err['email']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                <?php if (isset($err['password'])): ?>
                    <span style="color: #f87171; font-size: 11.5px; margin-top: 4px; display: block;"><?php echo $err['password']; ?></span>
                <?php endif; ?>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" name="remember" id="remember" <?php echo isset($_COOKIE['emailcookie']) ? 'checked' : ''; ?>>
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" name="login" class="btn-admin">Sign In to Dashboard</button>

            <div class="admin-actions">
                <a href="#" class="admin-link">Forgot password credentials?</a>
                <a href="index.php" class="admin-link" style="color: var(--primary); font-weight: 500;">← Back to Pharmacy Shop</a>
            </div>
            
        </form>
    </div>
</div>

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
        <div class="toast-icon"><i class="<?php echo $icon_class; ?>"></i></div>
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
      </script>
    <?php 
        unset($_SESSION['toast']);
    endif; 
    ?>
</div>

</body>
</html>