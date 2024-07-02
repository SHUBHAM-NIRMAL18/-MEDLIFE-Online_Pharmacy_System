<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Send OTP</title>
  <style>
    /* body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: Arial, sans-serif;
      background-color: #f1f1f1;
    } */

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
<a href="index.php"><img src="logo/MEDLOGO.png" width="10%" height="50%" style="margin-left:700px;margin-top:180px;"alt=""></a>
<?php 
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    require 'vendor/autoload.php';
    session_start();

    $email = $message= '';

    if(isset($_POST['btnOTP'])){
    $err = [];
    if(isset($_POST['email'])&& !empty($_POST['email']) && trim($_POST['email'])){
    $email = $_POST['email'];
    }
    else{
        $err['email']= "Enter email";
    }
    if(count($err)==0){
    $con=mysqli_connect('localhost','root','','medlife');
    $res=mysqli_query($con,"select * from tbl_user where email='$email'");
    $count=mysqli_num_rows($res);
    if($count>0){
        $otp=rand(11111,99999);
        mysqli_query($con,"update tbl_user set otp='$otp' where email='$email'");
        $_SESSION['user_email'] = $email;
        
        $message =  "OTP SEND";

        $toEmail = $email;
        $emailSubject = 'Password Reset OTP';
    

      // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
            // Configure the PHPMailer instance
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = 'bddc191603848d';
            $mail->Password = 'e4ee0e941c3351';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Set the sender, recipient, subject, and body of the message
            $mail->setFrom('medlife@gmail.com');
            $mail->addAddress($toEmail);
            $mail->Subject = $emailSubject;
            $mail->isHTML(true);
            $mail->Body = "<p>Your OTP is {$otp}";

            // Send the message
            $mail->send();
            
        echo "<script>alert('OTP Sent!! Check your mail');</script>";
        echo  "<script type='text/javascript'>";
        echo "window.location.href='check_otp.php'"; 
        echo "</script>";
    }
    else{
        $err['email'] =  "Enter a valid email";
    }
    }
}

    

            
        


    

?>
<body>
    
  <div class="otp">
    <h2>Reset Password</h2><br>
    <form method='post'>
      <div class="form-group">
        <label for="email">Email:</label>
        <span style="color:green;font-size:15px"><?php if(isset($message)){echo $message ;}?></span>
        <input type="text" id="email" name="email" placeholder="Enter Email Here">
        <div style="color:red;font-size:12px"><?php echo (isset($err['email'])?$err['email']:'');?></div>
      </div>
      <button class="btn-send-otp" name="btnOTP" type="submit">Send OTP</button>
    </form>
  </div>
</body>
</html>
