<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$password = $conpassword = '';
$err = [];
$email = isset($_SESSION['IS_LOGIN']) ? $_SESSION['IS_LOGIN'] : '';

if (empty($email)) {
    header('location:forget_password.php');
    exit();
}

if (isset($_POST['btnReset'])) {
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $pass_val = $_POST['password'];
        // Require at least 8 chars, 1 uppercase, 1 lowercase, and 1 number
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})/", $pass_val)) {
            $err['password'] = "Must be 8+ characters with uppercase, lowercase & digits.";
        } else {
            $password = md5($pass_val);
        }
    } else {
        $err['password'] = 'Please enter your password.';
    }

    if (isset($_POST['conpassword']) && !empty($_POST['conpassword']) && trim($_POST['conpassword'])) {
        $conpassword = md5(trim($_POST['conpassword']));
    } else {
        $err['conpassword'] = 'Please confirm your password.';
    }

    if (empty($err['password']) && empty($err['conpassword']) && $password !== $conpassword) {
        $err['conpassword'] = 'Confirm password does not match.';
    }

    if (count($err) == 0) {
        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("UPDATE tbl_user SET password = ? WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $password, $email);
                if ($stmt->execute()) {
                    unset($_SESSION['IS_LOGIN']);
                    unset($_SESSION['user_email']);
                    
                    $_SESSION['toast'] = [
                        'type' => 'success',
                        'title' => 'Password Reset',
                        'message' => 'Your password has been changed successfully. Please log in.'
                    ];
                    
                    header('location:customer_login.php');
                    exit();
                } else {
                    $err['error'] = "Something went wrong. Please try again.";
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $err['error'] = "Database update error: " . $e->getMessage();
        }
    }
}

$page_title = "Reset Password";
$page_css = "css/register.css";
include('header.php');
?>

<main class="auth-wrapper" style="padding: 40px 0;">
  <div class="content-container" style="display: flex; justify-content: center;">
    <div class="auth-card" style="max-width: 500px; min-height: auto;">
      
      <div class="auth-form-side" style="padding: 40px;">
        <h2>New Password</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px; margin-top: -12px;">
            Set a strong and secure password for <strong style="color: var(--text-main);"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>.
        </p>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" novalidate>
          
          <?php if (isset($err['error'])): ?>
              <div class="alert alert-error"><?php echo $err['error']; ?></div>
          <?php endif; ?>

          <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
            <?php if (isset($err['password'])): ?>
                <span class="error-text"><?php echo $err['password']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="conpassword">Confirm Password</label>
            <input type="password" id="conpassword" name="conpassword" class="form-control" placeholder="Re-type password" required>
            <?php if (isset($err['conpassword'])): ?>
                <span class="error-text"><?php echo $err['conpassword']; ?></span>
            <?php endif; ?>
          </div>
          
          <div class="auth-actions">
            <button class="btn btn-primary" name="btnReset" type="submit" style="width: 100%; height: 42px; font-weight: 600;">
                <i class="bx bx-check-double" style="font-size: 16px;"></i> Update Password
            </button>
            <a href="customer_login.php" class="auth-link">← Cancel and Back to Log In</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</main>

<?php include('footer.php'); ?>
