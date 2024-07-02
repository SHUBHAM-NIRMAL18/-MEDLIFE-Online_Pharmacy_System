<?php 
if (isset($_COOKIE['name'])) {
  session_start();
  $_SESSION['name'] = $_COOKIE['name'];
  
}

$email = '';
$error = '';
if (isset($_POST['btnlogin'])) {
  $err = [];
  if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
      $email = $_POST['email'];
    if (!preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $_POST['email'])) {
      $err['email'] = '<div class="err">*Enter valid email</div>';
    }else {
      $email = $_POST['email'];
    }
  } else {
    $err['email'] = '<div class="err">*Enter email</div>';
  }

  if (isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])) {
    $password = md5($_POST['password']);

  } else {
    $err['password'] = '<div class="err">*Enter password</div>';
  }

  if (count($err) == 0) {
    
    try{
      $connection = new mysqli('localhost','root','','medlife');
      $sql = "select * from tbl_user where email='$email' and password='$password'";
      $result = $connection->query($sql);
      if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if ($row['email']== $email && $row['password']== $password) {
          session_start();
          $_SESSION['email'] = $row['email'];
          $_SESSION['name'] = $row['name'];
          $_SESSION['login_status'] = true;
          $_SESSION['user_id'] = $row['user_id'];
    
          if (isset($_POST['remember'])) {
            setcookie('emailcookie',$email,time()+86400);
          }
          header('location:user_dashboard.php');
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        .container1 {
      display: flex;
      align-items: center;
      max-width: 800px;
      padding: 20px;
      border: 1px solid black;
      border-radius: 5px;
      margin-left:350px;
      margin-top:60px;
    }

    .login-form1 {
      flex-grow: 1;
      margin-right: 20px;
      margin-bottom:100px;
      font-family:"poppins";
    }
    .login-form1 h2 {
      padding:50px;
      text-align:center;
      color:green;
      font-family:"poppins";
    }

    .login-form1 input {
      width: 100%;
      padding: 8px;
      margin-bottom: 10px;
      box-sizing: border-box;
    }

    .login-button1 {
      width: 100%;
      padding: 10px;
      background-color: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      margin-top:20px;
    }

    .signup-link1 {
      display: block;
      text-align: center;
      margin-top: 10px;
      color: black;
      text-decoration:none;
    }
    .signup-link2 {
      display: block;
      text-align: right;
      margin-top: 10px;
      color: black;
      text-decoration:none;
      font-size:12px;
      color:red;
    }

    .image-container1 {
      flex-grow: 1;
      text-align: center;
    }

    .image-container1 img {
      max-width: 100%;
      height: auto;
      margin-left:25px;
    }
    .login-form1 .r{
                display:flex;
                margin-left:15px;
            }
    .login-form1 .checkbox-container {
            display: flex;
            align-items: left;
            margin-bottom: 10px;
            }

    .login-form1 .checkbox-container input[type="checkbox"] {
        margin: 4px 0 0;
        line-height: normal;
        width: 20px;
        height: 20px;
    }
    .err{
        color:red;
        font-size:12px;
    }
    </style>
</head>
<?php include('header.php') ?>
<body>
<div class="container1">
    
    <div class="login-form1">
      <h2><u>Login Here</u></h2>
      <?php 
    if (isset($_GET['msg']) && $_GET['msg'] == 1) { ?>
      <div class="err">Please login to continue</div>
    <?php } ?>
    <div class="err"><?php echo $error ; ?></div>
      <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post" novalidate>
        <label for="email">Email</label>
        <input type="email" placeholder="Email" name="email" value="<?php if(isset($_COOKIE['emailcookie'])){ echo $_COOKIE['emailcookie'];} ?> ">
        <?php echo isset($err['email'])?$err['email']:'' ?><br>

        <label for="password">Password</label>
        <input type="password" placeholder="Password"  name="password">
        <?php echo isset($err['password'])?$err['password']:'' ?><br>
        
    <div class="checkbox-container">
      <input type="checkbox" id="remember-me"  name="remember">
      <label for="remember-me" class="r" >Remember me</label>
      
    </div>
        <button class="login-button1" name="btnlogin">Login</button>
      </form>
      <a href="customer_register.php" class="signup-link1">Don't have an account? Sign up</a>
      <a href="forget_password.php" class="signup-link2">Forgot Password?</a>
    </div>
    <div class="image-container1">
      <img src="img/image-22.png" alt="Large Image">
    </div>
    </div>
    
</body>
<?php include ('footer.php') ?>
</html>