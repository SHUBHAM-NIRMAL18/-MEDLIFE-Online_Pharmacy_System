<?php
error_reporting(0);
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'vendor/autoload.php';

$errors = [];
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnFeedback'])) {
    $name = sanitizeFooterInput($_POST['name']);
    $email = sanitizeFooterInput($_POST['email']);
    $message = sanitizeFooterInput($_POST['message']);
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    if (empty($email)) {
        $errors[] = 'Email is required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email is invalid';
    }
    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (!empty($errors)) {
        $allErrors = join('<br/>', $errors);
        $errorMessage = "<div class='alert alert-error'>{$allErrors}</div>";
    } else {
        $toEmail = 'medlife@gmail.com';
        $emailSubject = 'New email from your Feedback Form';

        $mail = new PHPMailer(true);
        try {
            // Configure the PHPMailer instance
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST', 'sandbox.smtp.mailtrap.io');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USER', 'bddc191603848d');
            $mail->Password = env('MAIL_PASS', 'e4ee0e941c3351');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)env('MAIL_PORT', 587);

            // Set the sender, recipient, subject, and body of the message
            $mail->setFrom($email);
            $mail->addAddress($toEmail);
            $mail->Subject = $emailSubject;
            $mail->isHTML(true);
            $mail->Body = "<p>Name: {$name}</p><p>Email: {$email}</p><p>Message: {$message}</p>";

            // Send the message
            $mail->send();

            $successMessage = "<div class='alert alert-success'>Thank you for contacting us :)</div>";
        } catch (Exception $e) {
            $errorMessage = "<div class='alert alert-error'>Oops, something went wrong. Please try again later.</div>";
        }
    }
}

if (!function_exists('sanitizeFooterInput')) {
    function sanitizeFooterInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
}
?>

<footer class="site-footer">
  <div class="content-container">
    <div class="footer-grid">
      
      <!-- Column 1: Brand & Contact Info -->
      <div class="footer-col">
        <h3>About Medlife</h3>
        <p>
          Dedicated to providing you with the highest quality of healthcare service and care. Find genuine medicines, supplements, and clinical devices.
        </p>
        <p style="font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
          <i class="bx bx-phone" style="color: var(--primary); font-size: 16px;"></i> +977 1 5521234
        </p>
        <p style="font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
          <i class="bx bx-envelope" style="color: var(--primary); font-size: 16px;"></i> support@medlife.com
        </p>
        
        <!-- Social Icons -->
        <div class="footer-social-links">
          <a href="#" class="social-icon-btn" aria-label="Facebook"><i class="bx bxl-facebook"></i></a>
          <a href="#" class="social-icon-btn" aria-label="Twitter"><i class="bx bxl-twitter"></i></a>
          <a href="#" class="social-icon-btn" aria-label="Instagram"><i class="bx bxl-instagram"></i></a>
          <a href="#" class="social-icon-btn" aria-label="LinkedIn"><i class="bx bxl-linkedin"></i></a>
        </div>
      </div>

      <!-- Column 2: Quick Links -->
      <div class="footer-col">
        <h3>Quick Links</h3>
        <ul class="footer-links-list">
          <li><a href="index.php"><i class="bx bx-chevron-right"></i> Home</a></li>
          <li><a href="u_medicines.php"><i class="bx bx-chevron-right"></i> Medicines</a></li>
          <li><a href="u_supplements.php"><i class="bx bx-chevron-right"></i> Supplements</a></li>
          <li><a href="u_devices.php"><i class="bx bx-chevron-right"></i> Devices</a></li>
          <li><a href="admin_login.php"><i class="bx bx-chevron-right"></i> Admin Login</a></li>
          <li><a href="user_dashboard.php"><i class="bx bx-chevron-right"></i> My Account</a></li>
        </ul>
      </div>

      <!-- Column 3: Feedback Form -->
      <div class="footer-col">
        <h3>Feedback Form</h3>
        <form action="" method="post" id="contact-form" class="footer-feedback-form">
          <?php echo $errorMessage; ?>
          <?php echo $successMessage; ?>
          
          <div class="form-group-footer">
            <input type="text" name="name" placeholder="Your Name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES) : ''; ?>">
          </div>
          
          <div class="form-group-footer">
            <input type="email" name="email" placeholder="Your Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES) : ''; ?>">
          </div>
          
          <div class="form-group-footer">
            <textarea name="message" placeholder="Your Message" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message'], ENT_QUOTES) : ''; ?></textarea>
          </div>
          
          <button type="submit" name="btnFeedback" class="btn btn-secondary" style="width: 100%;">Submit Feedback</button>
        </form>
      </div>

      <!-- Column 4: Location Map -->
      <div class="footer-col">
        <h3>Location</h3>
        <div class="map-container">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4024.5793175587314!2d85.32190731783429!3d27.676611715094968!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb19c9a5cf34fb%3A0x9184a86a77dac5e5!2sPatan%20Multiple%20Campus!5e0!3m2!1sen!2snp!4v1687273958510!5m2!1sen!2snp" 
            allowfullscreen="" 
            loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade">
          </iframe>
        </div>
      </div>

    </div>

    <!-- Footer Copyrights -->
    <div class="footer-bottom">
      <p>&copy; <?php echo date('Y'); ?> Medlife. All Rights Reserved. Designed for premium healthcare services.</p>
    </div>
  </div>
</footer>

</div> <!-- Close .site-wrapper (opened in header.php) -->
</body>
</html>
