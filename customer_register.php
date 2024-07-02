<?php
$name = $phone = $email = $address = $gender = $password= $message = '';

if (isset($_POST['btnRegister'])){
  $err = [];
  if (isset($_POST['name']) && !empty($_POST['name']) && trim($_POST['name'])){
    $name = $_POST['name'];
}
else{
  $err['name'] = '*Please enter your name';
}
if (isset($_POST['phone']) && !empty($_POST['phone']) && trim($_POST['phone'])){
  $phone = $_POST['phone'];
  if (!preg_match('/^[0-9]{10}+$/', $phone))
  {
   $err['phone'] = '*Enter valid phone';
 }
}
else{
  $err['phone'] = '*Please enter your phone number';
}
if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])){
  $email = $_POST['email'];
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
           {
            $err['email'] = '*Enter valid email';
          }
}
else{
  $err['email'] = '*Please enter your email';
}
if (isset($_POST['address']) && !empty($_POST['address']) && trim($_POST['address'])){
  $address = $_POST['address'];
}
else{
  $err['address'] = '*Please enter your address';
}
if(isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])){
  $password = md5($_POST['password']);
}
else{
  $err['password'] = '*Please enter your password';
}
if(isset($_POST['gender']) && !empty($_POST['gender']) && trim($_POST['gender'])){
  $gender = $_POST['gender'];
}
else{
  $err['gender'] = '*Please select your gender';
}


if(count($err) == 0){
  try{
    $conn = new mysqli('localhost','root','','medlife');
    $sql = "insert into tbl_user(name,email,phone,address,password,gender) values ('$name','$email','$phone','$address','$password','$gender')";
    $conn->query($sql);
    if($conn->affected_rows == 1 && $conn-> insert_id > 0){
      $_SESSION['user'] = $name;
      $message= '<div style="color:green; font-size:12">User Created Sucessfully</div>';
    }
    
  }
  catch(Exception $e){
    die('Database error :'.$e->getMessage());

  }
}


}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
        .container2 {
      display: flex;
      align-items: center;
      max-width: 900px;
      
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      margin-left:300px;
      margin-top:10px;
      font-family:"poppins";
    }

    .register-form {
        width:500px;
        
      flex-grow: 1;
      margin-right: 10px;
      
    }
    .register-form h2 {
      padding:10px;
      text-align:left;
      color:green;
      font-family:"poppins";
    }

    .register-form input[type='text'],input[type='email'],input[type='number'],input[type='password'],
    .register-form select {
      width: 70%;
      padding: 8px;
      margin-bottom: 10px;
      box-sizing: border-box;
    }

    .register-form .button2 {
      width: 50%;
      padding: 10px;
      background-color: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
    }
    .register-form label {
  display: inline-flex;
  align-items: center;
  margin-right: 10px;
}

.register-form input[type="radio"] {
  margin-right: 5px;
}

    .login-link {
      display: block;
      text-align: center;
      margin-top: 10px;
      margin-right:250px;
      text-decoration:none;
      font-size:12px;
      
    }

    .image-container {
      flex-grow: 1;
      text-align: center;
    }

    .image-container img {
      max-width: 100%;
      height: auto;
      margin-bottom:100px;
    }
    </style>
</head>
<body>
    <?php include('header.php'); ?>
    <div class="container2">
    <div class="register-form">
      <h2><u>Register</u></h2>
      <?php echo $message; ?>
      <form action="" method="post" novalidate>
        <label for="name">Name</label><br>
        <input type="text" placeholder="Name" name="name">
        <div style="color:red;font-size:12px"><?php echo (isset($err['name'])?$err['name']:'');?></div>



        <label for="email">Email</label><br>
        <input type="email" id="email" placeholder="Email" name='email'><span id="msg_email" style="font-size:12px; font-style:italic;"></span>
        <div style="color:red ;font-size:12px"><?php echo (isset($err['email'])?$err['email']:'');?></div>



        <label for="phone">Phone</label><br>
        <input type="number" placeholder="Phone" name='phone'>
        <div style="color:red; font-size:12px"><?php echo (isset($err['phone'])?$err['phone']:'');?></div>



        <label for="address">Address</label><br>
        <input type="text" placeholder="Address" name='address'>
        <div style="color:red; font-size:12px"><?php echo (isset($err['address'])?$err['address']:'');?></div>



        <label for="address">Password</label><br>
        <input type="password" placeholder="Password" name='password'>
        <div style="color:red; font-size:12px"><?php echo (isset($err['password'])?$err['password']:'');?></div>



        <label>Gender:</label><br/>
        
        <input type="radio" name="gender" value="male" id="male" >
        <label for="male">Male</label><br/>
        <input type="radio" name="gender" value="female" id="female" >
        <label for="female">Female</label>
        <div style="color:red; font-size:12px"><?php echo (isset($err['gender'])?$err['gender']:'');?></div>
        
        <input type="submit" name="btnRegister" value="Register" class="button2">
      </form>
      <a href="customer_login.php" class="login-link">Already have an account? Login</a>
    </div>
    <div class="image-container">
      <img src="img/image-22.png" alt="Large Image">
    </div>
  </div>
</body>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" ></script>
  <script type="text/javascript">
    $(document).ready(function(){
        $('#email').keyup(function(){
            var email = $(this).val();
            $.ajax({
                url:'check_customer_email.php',
                data: {'email': email},
                dataType:'text',
                method:'post',
                success:function(resp){
                    $('#msg_email').html(resp);
                    if(resp == 'Email Available')
                    {
                        $('#msg_email').css({color:'green'})
                    }
                    else
                    {
                        $('#msg_email').css({color:'red'})
                    }
                }
            });
        });
    });
  </script>

</html>
<?php include('footer.php')?>