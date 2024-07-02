
<?php error_reporting(0); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Enter OTP</title>
  <style>
   

    .otp {
        margin-left:600px;
        /* margin-top:180px; */
    
      width: 300px;
      background-color: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      font-family:"poppins";
    }

    .otp h2 {
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

    .form-group input {
      width: 90%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 3px;
    }

    .btn-send-otp {
      width: 100%;
      padding: 10px;
      background-color: #4CAF50;
      color: #fff;
      border: none;
      border-radius: 3px;
      cursor: pointer;
      font-weight: bold;
      font-family:"poppins";
    }

    .btn-send-otp:hover {
      background-color: #45a049;
    }
  </style>
</head>

<?php
session_start();

if(isset($_POST['btnOTP'])){
$err= [];
if(isset($_POST['otp']) && !empty($_POST['otp'])){
    $otp = $_POST['otp'];
}
else{
    $err['otp']="Enter OTP";
}

if(isset($_SESSION['user_email'])){
    $email= $_SESSION['user_email'];
}
if(count($err)== 0){



$con=mysqli_connect('localhost','root','','medlife');
$res=mysqli_query($con,"select * from tbl_user where email='$email' and otp='$otp'");
$count=mysqli_num_rows($res);
if($count>0){
	mysqli_query($con,"update tbl_user set otp='' where email='$email'");
	    $_SESSION['IS_LOGIN']=$email;
	    echo "<script>alert('Correct OTP');</script>";
        echo  "<script type='text/javascript'>";
        echo "window.location.href='reset_password.php'"; 
        echo "</script>";
}
else{
	$err['otp'] = "Incorrect OTP ";
}
}

}
?>
<body>
<a href="index.php"><img src="logo/MEDLOGO.png" width="10%" height="50%" style="margin-left:700px;margin-top:180px;"alt=""></a> 
  <div class="otp">
    <h2>Enter OTP</h2><br>
    <form method='post'>
      <div class="form-group">
        <label for="otp">OTP Here:</label>
        <span style="color:green;font-size:15px"><?php if(isset($message)){echo $message ;}?></span>
        <input type="text" id="otp" name="otp" placeholder="Enter OTP Here">
        <div style="color:red;font-size:15px"><?php echo (isset($err['otp'])?$err['otp']:'');?></div>
      </div>
      <button class="btn-send-otp" name="btnOTP" type="submit">Submit</button>
    </form>
  </div>
</body>
</html>