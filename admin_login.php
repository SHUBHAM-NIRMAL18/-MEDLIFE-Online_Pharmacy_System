<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
</head>
<body>
<?php 
if (isset($_COOKIE['name'])) {
  session_start();
  $_SESSION['name'] = $_COOKIE['name'];
  header('location:dashboard.php');
}

$email = '';
$error = '';
if (isset($_POST['login'])) {
  $err = [];
  if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
      $email = $_POST['email'];
    if (!preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/',$_POST['email'])) {
      $err['email'] = '<div class="err">Enter valid email</div>';
    }
  } else {
    $err['email'] = '<div class="err">Enter email</div>';
  }

  if (isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])) {
    $password = md5($_POST['password']);

  } else {
    $err['password'] = '<div class="err">Enter password</div>';
  }

  if (count($err) == 0) {
    
    try{
      $connection = new mysqli('localhost','root','','medlife');
      $sql = "select * from tbl_admin where email='$email' and password='$password' and status=1";
      $result = $connection->query($sql);
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($row['email']== $email && $row['password']== $password) {
          session_start();
          $_SESSION['email'] = $row['email'];
          $_SESSION['name'] = $row['name'];
          $_SESSION['login_status'] = true;
          $_SESSION['admin_id'] = $row['admin_id'];
    
          if (isset($_POST['remember'])) {
            setcookie('emailcookie',$email,time()+86400);
          }
          header('location:admin_home.php');
        } 
        
      }
      else {
        $error = 'Incorrect email or password';
      }
    }catch(Exception $ex){
      die('Database Error:.' . $ex->getMessage());
    }
    
  }
} 

 ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Page</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/adminlogin.css">
</head>
<body>
  
<img src="logo/admin.png" class="img-a" alt="">

<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" novalidate>
  <div class="login-form">
  <h2><u>Admin Login</u></h2>
  <?php 
if (isset($_GET['msg']) && $_GET['msg'] == 1) { ?>
  <div class="err" style='background:red;color:white;'>Please login to continue</div>
<?php } ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email" style="width: 300px;padding: 10px;font-family:'Poppins'" value="<?php if(isset($_COOKIE['emailcookie'])){ echo $_COOKIE['emailcookie'];} ?> ">
    <?php echo isset($err['email'])?$err['email']:'' ?>
 
  
    <label for="password">Password</label>
    <input type="password" id="password" name="password">
    <?php echo isset($err['password'])?$err['password']:'' ?>
  
  
    <div class="checkbox-container">
    <input type="checkbox" name= "remember" id="remember-me">
    <label for="remember-me">Remember me</label>

  </div>
  <div class="err"><?php echo $error ; ?></div>
  
  
  
  
    <input type="submit" id="submit" name="login" value="Login"><br>
    <a href="#" style="text-decoration:none; color:black;margin-left:150px">Forgot Password??</a> 
  </div>
</form>
</body>
</html>
</body>
</html>