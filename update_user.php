
<?php include ('dashboard.php') ?>
<?php 
$id = $_GET['user_id'];

if (isset($_POST['btnUpdate'])) {
    $row = [];
  
  
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
 

  
   
 

 

 

 
    try{
      $conn = new mysqli('localhost','root','','medlife');
      

     
        $sql = "update tbl_user SET name = '$name', email = '$email', phone = '$phone', address = '$address', gender = '$gender' where user_id=$id";
      


      $conn->query($sql);
      if ($conn->affected_rows == 1) {
        echo "User updated success";
      }
    }
    catch(Exception $e){
       die('Database  Error : ' .$e->getMessage());
    }
 
}?>
<?php

try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "select * from tbl_user where user_id=$id";
  $res = $conn->query($sql);
  if ($res->num_rows == 1) {
    $row = $res->fetch_assoc();
   
    
  } else {
    die("data not found");
  }
}
catch(Exception $e){
   die('Database  Error : ' .$e->getMessage());
}
?>
 
  <!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update Users</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: "Poppins", sans-serif;
          }

          form {
            width: 400px;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 5px;
            margin-right:200px;
            margin-top:100px;

          }

          input[type="text"],input[type="email"] {
            width: 95%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: "Poppins";
          }
          select{
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: "Poppins";
          }

          input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
          }

          input[type="submit"]:hover {
            background-color: darkgreen;
          }
          .button{
            margin-bottom:500px;
            margin-right:200px;
          }
          /* Styling for the anchor tag button */
          a.button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
          
          }

          a.button:hover {
            background-color: #45a049;
          }

  </style>
</head>
<body>

<a href="view_user.php" class='button'>View Users</a>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>?user_id=<?php echo $id ?>" method="post">
  <div class="control">
    <label for="name">Name</label>
    <input type="text" name="name"  value="<?php echo $row['name']?>" />
    
  </div>
  <div class="control">
    <label for="email">Email</label>
    <input type="email" name="email"  value="<?php echo $row['email']?>" />
    
  </div>
  <div class="control">
    <label for="phone">Phone</label>
    <input type="text" name="phone"  value="<?php echo $row['phone']?>" />
    
  </div>
  <div class="control">
    <label for="address">Address</label>
    <input type="text" name="address"  value="<?php echo $row['address']?>" />
    
  </div>
  <div class="control">
    <label for="gender">Gender</label>
    <input type="text" name="gender"  value="<?php echo $row['gender']?>" />
    
  </div>
  
 
  <div class="control">
    <input type="submit" name="btnUpdate" value="Update" />
  </div>
</form>
</body>
</html>