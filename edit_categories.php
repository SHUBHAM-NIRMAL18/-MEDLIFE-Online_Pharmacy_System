
<?php 
ob_start();
include_once ('dashboard.php') ?>
<?php 
if(is_numeric($_GET['cat_id'])){
$id = $_GET['cat_id'];
}else{
  header("Location:viewcat.php?msg=1");
}
$message='';

if (isset($_POST['btnUpdate'])) {
    $categories = [];
  
  
    $cat_name = $_POST['cat_name'];
 

  
    $cat_status = $_POST['cat_status'];
 

 

 

 
    try{
      $conn = new mysqli('localhost','root','','medlife');
      

     
        $sql = "update tbl_categories set cat_name='$cat_name',cat_status='$cat_status' where cat_id=$id";
      


      $conn->query($sql);
      if ($conn->affected_rows == 1) {
        $message= "Category updated success";
      }
    }
    catch(Exception $e){
       die('Database  Error : ' .$e->getMessage());
    }
 
}?>
<?php

try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "select * from tbl_categories where cat_id=$id";
  $res = $conn->query($sql);
  if ($res->num_rows == 1) {
    $categories = $res->fetch_assoc();
   
    
  } else {
    $categories = [];
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
  <title> Form</title>
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
    .prdt{
      padding-bottom:700px;
      
      text-decoration: none;
    }
  </style>
</head>
<body>
<h2 style="margin-top:90px; margin-left:700px; "><u>Edit Category</u></h2>
<div class="contain">
<a class='prdt'href="viewcat.php">View Categories</a>
<?php if (!empty($categories)){ ?>

<div class="form">
<form action="<?php echo $_SERVER['PHP_SELF'] ?>?cat_id=<?php echo $id ?>" method="post">
  <span style="color:green"><?php echo $message ?></span>
  <div class="control">
    <label for="cat_name">Categories Name</label>
    <input type="text" name="cat_name"  value="<?php echo $categories['cat_name']?>" />
    
  </div>
  <div class="control">
    <label for="status">Categories Status</label>
        <?php if($categories['cat_status']==1) { ?>
          <select  name="cat_status" >
            <option value="1" selected>Active</option>
            <option value="2">Inactive</option>
          </select><?php } else { ?>
          <select  name="cat_status" >
            <option value="1" >Active</option>
            <option value="2" selected>Inactive</option>
          </select>
          <?php } ?>
    
    
  </div>
 
  <div class="control">
    <input type="submit" name="btnUpdate" value="Update" />
  </div>
</form>

          </div>
          <?php } else{ echo "<span style='font-size:20px; color:red;'>No Record Find</span>"; }?>
</div>
</body>
</html>