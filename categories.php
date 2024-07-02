<?php include ('dashboard.php') ?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Categories</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        .contain {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: "Poppins", sans-serif;
    }

    .form form {
      width: 400px;
      padding: 20px;
      background-color: #f5f5f5;
      border-radius: 5px;
      margin:0px 0px 300px 0px;
      
    }

    input[type="text"] {
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
    .cat{
      padding-bottom:700px;
      
      text-decoration: none;
      
    }
    .err{
      color:red;
      font-size:11px;
    }
    .success-message{
      font-size:12px;
      color:green;
    }
   



    </style>
</head>
<body >
      <?php
      $message = '';
      if(isset($_POST['btnAdd'])){
        $err =[];
      if(isset($_POST['categories']) && !empty($_POST['categories']) && trim($_POST['categories'])){
        $categories = $_POST['categories'];
      } else{
        $err['categories'] = 'Please enter the category name';
      }
      if(isset($_POST['status']) && !empty($_POST['status']) && trim($_POST['status'])){
        $status = $_POST['status'];
      } else{
        $err['status'] = 'Please select the status';
      }

      if(count($err)==0){
      $conn = new mysqli('localhost', 'root','','medlife');

      
      if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
      }

     
      

      
      $sql="INSERT INTO tbl_categories (cat_name, cat_status) VALUES ( '$categories', '$status')";
      $conn->query($sql);
      if($conn->affected_rows == 1 && $conn-> insert_id > 0)
      {
        $message="Categories created sucessfully";
      } 
      else 
      {
          echo "Error inserting data.";
      }

    } 
  }
      ?>
    <h2 style="margin-top:90px; margin-left:700px;"><u>Add Categories</u></h2>
  <div class="contain">
    
    <a href='viewcat.php'  class="cat">View Categories</a>
    <div class="form">
    <form action="" method="post">
    <div class='success-message'>
      <?php echo $message; ?>
    </div>
        <label for="categories">Categories:</label>
        <input type="text"  name="categories" >
        <span class="err">
        <?php if (isset($err['categories'])){?>
          <?php echo $err['categories']; ?>
        <?php } ?><br><br>
        </span>
        <label for="status_select">Status:</label>
        <select  name="status" >
            <option value="" disable selected >Select</option>
            <option value="1">Active</option>
            <option value="2">Inactive</option>
            
        </select>
        <span class="err">
        <?php if (isset($err['status'])){?>
          <?php echo $err['status']; ?>
        <?php } ?><br><br>
        </span>
        <input type="submit" value="Submit" name="btnAdd">
    </form>
    </div>
    </div>
</body>
</html>
