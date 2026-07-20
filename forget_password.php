<?php 
require_once 'config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'vendor/autoload.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$email = '';
$err = [];
$message = '';

if (isset($_POST['btnOTP'])) {
    if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err['email'] = "Please enter a valid email address.";
        }
    } else {
        $err['email'] = "Email is required.";
    }

    if (count($err) == 0) {
        try {
            $conn = get_db_connection();
            $stmt = $conn->prepare("SELECT * FROM tbl_user WHERE email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res && $res->num_rows > 0) {
                    $otp = rand(11111, 99999);
                    
                    // Update OTP in database
                    $update_stmt = $conn->prepare("UPDATE tbl_user SET otp = ? WHERE email = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("ss", $otp, $email);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    $_SESSION['user_email'] = $email;

                    // Send email containing OTP via PHPMailer
                    $mail = new PHPMailer(true);
                    
                    // Configure SMTP parameters from env variables
                    $mail->isSMTP();
                    $mail->Host = env('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
                    $mail->SMTPAuth = true;
                    $mail->Username = env('MAIL_USER', 'bddc191603848d');
                    $mail->Password = env('MAIL_PASS', 'e4ee0e941c3351');
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = (int)env('MAIL_PORT', 587);

                    $mail->setFrom('medlife@gmail.com', 'Medlife Pharmacy');
                    $mail->addAddress($email);
                    $mail->Subject = 'Password Reset OTP';
                    $mail->isHTML(true);
                    $mail->Body = "<div style='font-family: sans-serif; padding: 20px; line-height: 1.6;'>
                                    <h2>Medlife Password Reset Request</h2>
                                    <p>Hello,</p>
                                    <p>We received a request to reset the password associated with your account.</p>
                                    <p>Please enter the following verification OTP code to proceed:</p>
                                    <h3 style='background-color: #f1f5f9; padding: 12px 20px; border-radius: 6px; display: inline-block; letter-spacing: 2px; font-size: 22px; color: #059669;'>{$otp}</h3>
                                    <p>If you did not request this, you can safely ignore this email.</p>
                                    <p>Best regards,<br>The Medlife Team</p>
                                   </div>";

                    $mail->send();
                    
                    $_SESSION['toast'] = [
                        'type' => 'success',
                        'title' => 'OTP Sent',
                        'message' => 'Verification code sent! Please check your mailbox.'
                    ];
                    
                    header('location:check_otp.php');
                    exit();
                } else {
                    $err['email'] = "This email does not exist in our pharmacy database.";
                }
                $stmt->close();
            }
        } catch (Exception $e) {
            $err['email'] = "Mailer error: Please check your configuration.";
        }
    }
}

$page_title = "Forgot Password";
$page_css = "css/register.css";
include('header.php');
?>

<main class="auth-wrapper" style="padding: 40px 0;">
  <div class="content-container" style="display: flex; justify-content: center;">
    <div class="auth-card" style="max-width: 500px; min-height: auto;">
      
      <div class="auth-form-side" style="padding: 40px;">
        <h2>Reset Password</h2>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px; margin-top: -12px;">
            Enter your registered email and we will send you a 5-digit verification code to reset your password.
        </p>

        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" novalidate>
          <div class="form-group">
            <label for="email">Registered Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@domain.com" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (isset($err['email'])): ?>
                <span class="error-text"><?php echo $err['email']; ?></span>
            <?php endif; ?>
          </div>
          
          <div class="auth-actions">
            <button class="btn btn-primary" name="btnOTP" type="submit" style="width: 100%; height: 42px; font-weight: 600;">
                <i class="bx bx-paper-plane" style="font-size: 16px;"></i> Send Verification Code
            </button>
            <a href="customer_login.php" class="auth-link">← Back to Log In</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</main>

<?php include('footer.php'); ?>
