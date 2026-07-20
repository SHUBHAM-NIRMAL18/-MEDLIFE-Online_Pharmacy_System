<?php 
require_once 'config.php';

if (isset($_COOKIE['name'])) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
  $_SESSION['name'] = $_COOKIE['name'];
}

$email = '';
$error = '';
if (isset($_POST['btnlogin'])) {
  $err = [];
  if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
      $email = $_POST['email'];
    if (!preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $_POST['email'])) {
      $err['email'] = 'Enter valid email';
    } else {
      $email = $_POST['email'];
    }
  } else {
    $err['email'] = 'Enter email';
  }

  if (isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])) {
    $password = md5($_POST['password']);
  } else {
    $err['password'] = 'Enter password';
  }

  if (count($err) == 0) {
    try {
      $connection = get_db_connection();
      $sql = "select * from tbl_user where email='$email' and password='$password'";
      $result = $connection->query($sql);
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($row['email'] == $email && $row['password'] == $password) {
          if (session_status() === PHP_SESSION_NONE) {
            session_start();
          }
          $_SESSION['email'] = $row['email'];
          $_SESSION['name'] = $row['name'];
          $_SESSION['login_status'] = true;
          $_SESSION['user_id'] = $row['user_id'];
    
          if (isset($_POST['remember'])) {
            setcookie('emailcookie', $email, time() + 86400);
          }
          header('location:user_dashboard.php');
          exit();
        } 
      } else {
        $error = 'Incorrect email or password';
      }
    } catch(Exception $ex) {
      die('Database Error:.' . $ex->getMessage());
    }
  }
} 

$page_title = "Login";
include('header.php');
?>

<main class="auth-wrapper">
  <div class="content-container" style="display: flex; justify-content: center;">
    <div class="auth-card">
      
      <!-- Form Side -->
      <div class="auth-form-side">
        <h2>Login</h2>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 1): ?>
          <div class="alert alert-error">Please login to continue</div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
          <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" novalidate>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" class="form-control" placeholder="example@email.com" name="email" value="<?php if(isset($_COOKIE['emailcookie'])){ echo htmlspecialchars($_COOKIE['emailcookie']); } elseif(isset($_POST['email'])) { echo htmlspecialchars($_POST['email']); } ?>">
            <?php if (isset($err['email'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['email']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" class="form-control" placeholder="••••••••" name="password">
            <?php if (isset($err['password'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['password']; ?></span>
            <?php endif; ?>
          </div>
          
          <a href="forget_password.php" class="forgot-pass-link">Forgot Password?</a>
          
          <div class="form-checkbox-group">
            <input type="checkbox" id="remember-me" name="remember" <?php if(isset($_COOKIE['emailcookie'])) echo 'checked'; ?>>
            <label for="remember-me">Remember me</label>
          </div>
          
          <div class="auth-actions">
            <button type="submit" class="btn btn-primary" name="btnlogin">Login</button>
            <a href="customer_register.php" class="auth-link">Don't have an account? Sign up</a>
          </div>
          
        </form>
      </div>

      <!-- Image Side -->
      <div class="auth-image-side">
        <img src="img/image-22.png" alt="Medlife Login Illustration">
      </div>

    </div>
  </div>
</main>

<?php include ('footer.php'); ?>