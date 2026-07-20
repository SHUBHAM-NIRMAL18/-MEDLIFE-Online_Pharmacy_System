<?php
require_once 'config.php';
$name = $phone = $email = $address = $gender = $password = $message = '';

if (isset($_POST['btnRegister'])){
  $err = [];
  if (isset($_POST['name']) && !empty($_POST['name']) && trim($_POST['name'])){
    $name = $_POST['name'];
  } else {
    $err['name'] = 'Please enter your name';
  }
  
  if (isset($_POST['phone']) && !empty($_POST['phone']) && trim($_POST['phone'])){
    $phone = $_POST['phone'];
    if (!preg_match('/^[0-9]{10}+$/', $phone)) {
      $err['phone'] = 'Enter a valid 10-digit phone number';
    }
  } else {
    $err['phone'] = 'Please enter your phone number';
  }
  
  if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])){
    $email = $_POST['email'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err['email'] = 'Enter valid email';
    }
  } else {
    $err['email'] = 'Please enter your email';
  }
  
  if (isset($_POST['address']) && !empty($_POST['address']) && trim($_POST['address'])){
    $address = $_POST['address'];
  } else {
    $err['address'] = 'Please enter your address';
  }
  
  if (isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])){
    $password = md5($_POST['password']);
  } else {
    $err['password'] = 'Please enter your password';
  }
  
  if (isset($_POST['gender']) && !empty($_POST['gender']) && trim($_POST['gender'])){
    $gender = $_POST['gender'];
  } else {
    $err['gender'] = 'Please select your gender';
  }

  if (count($err) == 0) {
    try {
      $conn = get_db_connection();
      $sql = "insert into tbl_user(name,email,phone,address,password,gender) values ('$name','$email','$phone','$address','$password','$gender')";
      $conn->query($sql);
      if ($conn->affected_rows == 1 && $conn->insert_id > 0) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Registration Successful',
            'message' => 'Your account has been created, ' . $name . '! Please log in.'
        ];
        header('location:customer_login.php');
        exit();
      }
    } catch(Exception $e) {
      $message = '<div class="alert alert-error">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
  }
}
$page_title = "Signup";
include('header.php');
?>

<main class="auth-wrapper" style="padding: 40px 0;">
  <div class="content-container" style="display: flex; justify-content: center;">
    <div class="auth-card" style="min-height: 600px;">
      
      <!-- Form Side -->
      <div class="auth-form-side" style="padding: 30px 40px;">
        <h2>Register</h2>
        
        <?php echo $message; ?>
        
        <form action="" method="post" novalidate>
          
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" class="form-control" placeholder="John Doe" name="name" value="<?php echo htmlspecialchars($name); ?>">
            <?php if (isset($err['name'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['name']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="email">Email Address <span id="msg_email" style="font-size:12px; font-weight:600; margin-left:10px;"></span></label>
            <input type="email" id="email" class="form-control" placeholder="example@email.com" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <?php if (isset($err['email'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['email']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="number" id="phone" class="form-control" placeholder="98XXXXXXXX" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            <?php if (isset($err['phone'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['phone']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="address">Address</label>
            <input type="text" id="address" class="form-control" placeholder="Kathmandu, Nepal" name="address" value="<?php echo htmlspecialchars($address); ?>">
            <?php if (isset($err['address'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['address']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" class="form-control" placeholder="••••••••" name="password">
            <?php if (isset($err['password'])): ?>
              <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['password']; ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label>Gender</label>
            <div class="gender-options">
              <label class="gender-radio">
                <input type="radio" name="gender" value="male" id="male" <?php if($gender == 'male') echo 'checked'; ?>>
                <span>Male</span>
              </label>
              <label class="gender-radio">
                <input type="radio" name="gender" value="female" id="female" <?php if($gender == 'female') echo 'checked'; ?>>
                <span>Female</span>
              </label>
            </div>
            <?php if (isset($err['gender'])): ?>
              <span class="error-text" style="margin-top: -15px; margin-bottom: 10px;"><i class="bx bx-error-circle"></i> <?php echo $err['gender']; ?></span>
            <?php endif; ?>
          </div>
          
          <div class="auth-actions" style="margin-top: 15px;">
            <button type="submit" class="btn btn-primary" name="btnRegister">Register</button>
            <a href="customer_login.php" class="auth-link">Already have an account? Login</a>
          </div>
          
        </form>
      </div>

      <!-- Image Side -->
      <div class="auth-image-side" style="background-color: var(--primary-light);">
        <img src="img/image-22.png" alt="Medlife Register Illustration" style="margin-bottom: 0;">
      </div>

    </div>
  </div>
</main>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script type="text/javascript">
  $(document).ready(function(){
      $('#email').keyup(function(){
          var email = $(this).val();
          if (email.trim() !== '') {
              $.ajax({
                  url:'check_customer_email.php',
                  data: {'email': email},
                  dataType:'text',
                  method:'post',
                  success:function(resp){
                      $('#msg_email').html(resp);
                      if(resp.trim() == 'Email Available') {
                          $('#msg_email').css({color:'green'});
                      } else {
                          $('#msg_email').css({color:'red'});
                      }
                  }
              });
          } else {
              $('#msg_email').html('');
          }
      });
  });
</script>

<?php include('footer.php'); ?>