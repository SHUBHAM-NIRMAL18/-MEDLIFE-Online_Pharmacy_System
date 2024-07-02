<?php error_reporting(0); ?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password</title>
  <style>
    /* body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: Arial, sans-serif;
      background-color: #f1f1f1;
    } */

    .reset {
      width: 300px;
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      margin-left:600px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .reset h2 {
      margin-top: 0;
      text-align: center;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .form-group input[type="password"] {
      width: 90%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
    }

    .btn-reset {
      width: 100%;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn-reset:hover {
      background-color: #45a049;
    }
  </style>
</head>
        <?php
            session_start();
            $password = $conpassword = '';
            if(isset($_POST['btnReset']))
            
            {
                $err=[];
                if(isset($_POST['password']) && !empty($_POST['password'])){
                    if(!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.{8,})/",$_POST['password']))
                    {
                        $err['password'] = "Password must have at least one lowercase,uppercase,number";
                    }
                    else
                    {
                        $password = md5($_POST['password']);
                    }
                }
                else
                {
                    $err['password'] = 'Please enter your password';
                }
                if(isset($_POST['conpassword']) && !empty($_POST['conpassword']) && trim($_POST['conpassword']))
                {
                    $conpassword = md5($_POST['conpassword']);
                  }
                  else
                  {
                    $err['conpassword'] = 'Please enter your confirm password';
                  }
        
                  if($password != $conpassword)
                  {
                    $err['conpassword'] = 'Password doesnot match';
                  }
                  if(isset($_SESSION['IS_LOGIN'])){
                    $email = $_SESSION['IS_LOGIN'];
                }

                if(count($err)== 0){
                    $conn = new mysqli('localhost','root','','medlife');
                    $sql = "update tbl_user set password='$password' where email = '$email'";
                    if ($conn->query($sql) === TRUE) 
                    {
                        unset($_SESSION['IS_LOGIN']);
                        echo "<script>alert('Password Changed');</script>";
                        echo  "<script type='text/javascript'>";
                        echo "window.location.href='customer_login.php'"; 
                        echo "</script>";
                      } else 
                      {
                        $err['error']="Something went wrong.Try again";
                      }
                }
            }



        ?>

<body>
<a href="index.php"><img src="logo/MEDLOGO.png" width="10%" height="50%" style="margin-left:700px;margin-top:180px;"alt=""></a>
  <div class="reset">
    <h2>Reset Password</h2>
    <form method="POST">
      <div class="form-group">
        <span name="error" style="color:red;font-size:14px"><?php echo (isset($err['error'])?$err['error']:'');?></span>
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" >
        <div style="color:red;font-size:14px"><?php echo (isset($err['password'])?$err['password']:'');?></div>
      </div>
      <div class="form-group">
        <label for="conpassword">Confirm Password:</label>
        <input type="password" id="conpassword" name="conpassword">
        <div style="color:red;font-size:14px"><?php echo (isset($err['conpassword'])?$err['conpassword']:'');?></div>
      </div>
      <button class="btn-reset" name="btnReset" type="submit">Reset Password</button>
    </form>
  </div>
</body>
</html>
