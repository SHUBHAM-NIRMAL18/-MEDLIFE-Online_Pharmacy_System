<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$otp = '';
$err = [];
$email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

if (empty($email)) {
    header('location:forget_password.php');
    exit();
}

if (isset($_POST['btnOTP'])) {
    if (isset($_POST['otp']) && !empty($_POST['otp']) && trim($_POST['otp'])) {
        $otp = trim($_POST['otp']);
    } else {
        $err['otp'] = "Verification code is required.";
    }

    if (count($err) == 0) {
        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE email = ? AND otp = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $email, $otp);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res && $res->num_rows > 0) {
                    // Reset the user's OTP code in the database
                    $update_stmt = $conn->prepare("UPDATE tbl_user SET otp = '' WHERE email = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("s", $email);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    $_SESSION['IS_LOGIN'] = $email;
                    
                    $_SESSION['toast'] = [
                        'type' => 'success',
                        'title' => 'Code Verified',
                        'message' => 'Verification code confirmed. Choose a new secure password.'
                    ];
                    
                    header('location:reset_password.php');
                    exit();
                } else {
                    $err['otp'] = "Incorrect verification code. Please check and try again.";
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $err['otp'] = "Database verification error: " . $e->getMessage();
        }
    }
}

$page_title = "Verify OTP";
$page_css = "css/register.css";
include('header.php');
?>

<main class="auth-wrapper" style="padding: 40px 0;">
  <div class="content-container" style="display: flex; justify-content: center;">
    <div class="auth-card" style="max-width: 500px; min-height: auto;">
      
      <div class="auth-form-side" style="padding: 40px;">
        <h2>Verify Code</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px; margin-top: -12px;">
            We have sent a verification code to <strong style="color: var(--text-main);"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></strong>. Enter it below to verify.
        </p>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" novalidate>
          <div class="form-group">
            <label for="otp">Verification Code (OTP)</label>
            <input type="text" id="otp" name="otp" class="form-control" placeholder="Enter 5-digit code" required value="<?php echo htmlspecialchars($otp, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (isset($err['otp'])): ?>
                <span class="error-text"><?php echo $err['otp']; ?></span>
            <?php endif; ?>
          </div>
          
          <div class="auth-actions">
            <button class="btn btn-primary" name="btnOTP" type="submit" style="width: 100%; height: 42px; font-weight: 600;">
                <i class="bx bx-shield-quarter" style="font-size: 16px;"></i> Verify Code
            </button>
            <a href="forget_password.php" class="auth-link">Didn't receive code? Resend Email</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</main>

<?php include('footer.php'); ?>