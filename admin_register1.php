<?php
     include_once ('dashboard.php');

    $name = $email = $password = $conpassword = $status = $message = '';
    if(isset($_POST['btnadRegister']))
    {
        $err = [];
        if (isset($_POST['name']) && !empty($_POST['name']) && trim($_POST['name'])){
            $name = $_POST['name'];
        }
        else
        {
            $err['name'] = '*Please enter your name';
        }
        
        if (isset($_POST['email']) && !empty($_POST['email']) && trim($_POST['email'])) {
            $email = $_POST['email'];

          if (!filter_var($email, FILTER_VALIDATE_EMAIL))
           {
            $err['email'] = '*Enter valid email';
          }
        } 
        else 
        {
            $err['email'] = '*Enter email';
        }
        if(isset($_POST['password']) && !empty($_POST['password']) && trim($_POST['password'])){
            $password = md5($_POST['password']);
          }
          else{
            $err['password'] = '*Please enter your password';
          }
          if(isset($_POST['conpassword']) && !empty($_POST['conpassword']) && trim($_POST['conpassword'])){
            $conpassword = md5($_POST['conpassword']);
          }
          else{
            $err['conpassword'] = '*Please enter your confirm password';
          }

          if($password != $conpassword)
          {
            $err['conpassword'] = '*Password doesnot match';
          }
          if(isset($_POST['status']) && !empty($_POST['status']) && trim($_POST['status'])){
            $status = ($_POST['status']);
          }
          else{
            $err['status'] = '*Please select your status';
          }


          if(count($err) == 0){
            try{
              $conn = new mysqli('localhost','root','','medlife');
              $sql = "insert into tbl_admin(name,email,password,status) values ('$name','$email','$password','$status')";
              $conn->query($sql);
              if($conn->affected_rows == 1 && $conn-> insert_id > 0)
              {
                $message= '<div style="color:green; font-size:12">User Created Sucessfully</div>';
              }
              
            }
            catch(Exception $e)
            {
              die('Database error :'.$e->getMessage());
          
            }
          }

    }



?>

<!DOCTYPE html>
<html>
<head>
  <title>Admin Registration Form</title>
  <style>
    .form-container {
      width: 400px;
      margin: 0 auto;
      padding: 20px;
      background-color: #f2f2f2;
      border: 1px solid #ccc;
      border-radius: 4px;
      /* margin-top:250px; */
    }

    .form-container input[type="text"],
    .form-container input[type="email"],
    .form-container input[type="password"] {
      width: 100%;
      padding: 12px 20px;
      margin: 8px 0;
      display: inline-block;
      border: 1px solid #ccc;
      box-sizing: border-box;
      border-radius: 4px;
    }

    .form-container input[type="radio"] {
      margin: 4px 8px 4px 0;
    }

    .form-container button {
      background-color: #4CAF50;
      color: white;
      padding: 14px 20px;
      margin: 8px 0;
      border: none;
      cursor: pointer;
      width: 100%;
      border-radius: 4px;
    }

    .form-container button:hover {
      background-color: #45a049;
    }
  </style>
</head>
<body>
<h2 style="text-align:center; margin-top:120px">Admin Register Form</h2>
    
  <div class="form-container">
    
    <form action='' method='post'>
    <?php echo $message; ?>
      <label for="name">Name:</label>
      <input type="text" id="name" name="name" >
      <div style="color:red;font-size:12px"><?php echo (isset($err['name'])?$err['name']:'');?></div>

      <label for="email">Email:</label>
      <input type="text" id="email" name="email" ><span id="msg_emails" style="font-size:12px; font-style:italic;"></span>
      <div style="color:red;font-size:12px"><?php echo (isset($err['email'])?$err['email']:'');?></div>

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" >
      <div style="color:red;font-size:12px"><?php echo (isset($err['password'])?$err['password']:'');?></div>

      <label for="conpassword">Confirm Password:</label>
      <input type="password" id="conpassword" name="conpassword" >
      <div style="color:red;font-size:12px"><?php echo (isset($err['conpassword'])?$err['conpassword']:'');?></div>

      <label>Status:</label>
      <input type="radio" id="active" name="status" value="1" >
      <label for="active">Active</label>

      <input type="radio" id="inactive" name="status" value="0" >
      <label for="inactive">Inactive</label>
      <div style="color:red;font-size:12px"><?php echo (isset($err['status'])?$err['status']:'');?></div>

      <button type="submit" name="btnadRegister">Register</button>
    </form>
  </div>

  <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" ></script>
  <script type="text/javascript">
    $(document).ready(function(){
        $('#email').keyup(function(){
            var email = $(this).val();
            $.ajax({
                url:'check_email.php',
                data: {'email': email},
                dataType:'text',
                method:'post',
                success:function(resp){
                    $('#msg_emails').html(resp);
                    if(resp.trim === 'Email Available')
                    {
                        $('#msg_emails').css({color:'green'})
                    }
                    else
                    {
                        $('#msg_emails').css({color:'red'})
                    }
                }
            });
        });
    });
  </script>
</body>
</html>
