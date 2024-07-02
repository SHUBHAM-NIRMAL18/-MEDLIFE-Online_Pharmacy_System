

<?php 
ob_start();
require_once 'dashboard.php';

if(is_numeric($_GET['prdct_id'])){
  $id = $_GET['prdct_id'];
  }else{
    header("Location:view_products.php?msg=1");
  }


if (isset($_POST['btnUpdate'])) {
    $categories = [];
   
  
  
    $prdct_name = $_POST['prdct_name'];
    $prdct_price = $_POST['prdct_price'];
    $prdct_company = $_POST['prdct_company'];
    $manf_date = $_POST['manf_date'];
    $exp_date = $_POST['exp_date'];
    $cat_id = $_POST['cat_id'];
    $old_image = $_POST['old_image'];
    if ($_FILES['new_image']['name']){
      $image = $_FILES['new_image']['name'];
      $temp_name = $_FILES['new_image']['tmp_name'];
      $target_path = "medimg/";
      $target_path = $target_path . basename($image);
      move_uploaded_file($temp_name, $target_path);
    } else{
      $image = $old_image;
    }
    
 

 

 

 
    try{
      $conn = new mysqli('localhost','root','','medlife');
      

     
        $sql = "update tbl_products set prdct_name='$prdct_name',prdct_price='$prdct_price',prdct_company='$prdct_company',manf_date='$manf_date',exp_date='$exp_date',cat_id='$cat_id',prdct_img='$image'  where prdct_id=$id";
      


      $conn->query($sql);
      if ($conn->affected_rows == 1) {
       $sucess= "Product updated success";
      }
    }
    catch(Exception $e){
       die('Database  Error : ' .$e->getMessage());
    }
 
}?>
<?php

try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "SELECT tbl_products.*, tbl_categories.cat_name FROM tbl_products, tbl_categories WHERE tbl_products.cat_id = tbl_categories.cat_id and prdct_id=$id";
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
<?php 
$con = new mysqli('localhost','root','','medlife');
$sqli = "select * from tbl_categories";
$result = $con->query($sqli);
if ($result->num_rows == 1) {
  $row = $result->fetch_assoc();
    
  
}?>

 
  <!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Update Products</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100px;
      font-family: "Poppins", sans-serif;
      margin-top:300px;
    }

    form {
      width: 400px;
      padding: 10px;
      background-color: #f5f5f5;
      border-radius: 5px;
      margin-top:80px;
      margin-left:80px;
      font-size:12px;
    }

    input[type="text"], input[type="date"]{
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
      padding-bottom:500px;
    }
  </style>
</head>
<body>
<?php if (!empty($categories)){ ?>
<a class='prdt'href="view_products.php">View Products</a>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>?prdct_id=<?php echo $id ?>" method="post" enctype="multipart/form-data">
<?php if(isset($sucess)){?>
          <span class="sucess" style="color:green;">
          <?php echo $sucess;?>
          </span>
        <?php }?><br>
  <div class="control">
    Product Name<input type="text" name="prdct_name"  value="<?php echo $categories['prdct_name']?>" />
  </div>
  <div class="control">
    <label for="prdct_price">Product Price</label>
    <input type="text" name="prdct_price"  value="<?php echo $categories['prdct_price']?>" />
  </div>
  <div class="control">
    <label for="prdct_company">Product Company</label>
    <input type="text" name="prdct_company"  value="<?php echo $categories['prdct_company']?>" />
  </div>
  <div class="control">
    <label for="manf_date">Manufacturing Date</label>
    <input type="date" name="manf_date"  value="<?php echo $categories['manf_date']?>" />
  </div>
  <div class="control">
    <label for="exp_date">Expiration Date</label>
    <input type="date" name="exp_date"  value="<?php echo $categories['exp_date']?>" />
  </div>
  <div class="control">
    <label for="cat_id">Categories</label>
    <input type="hidden" name="cat_id" value="<?php  echo $categories['cat_name']?>"  />
    <select name="cat_id">
      <option value="<?php  echo $categories['cat_id']?>" selected ><?php  echo $categories['cat_name']?></option>
      <?php while ($row = $result->fetch_assoc()) { ?>
        <option value="<?php echo $row['cat_id']?>"><?php echo $row['cat_name']?></option>
        <?php }?>
    </select>
    
    
    </div>
  <div class="control">
    <label for="prdct_img">Update Product Image</label><br>
    
    <input type="hidden" name="old_image" value="<?php echo $categories['prdct_img']?>"><br>
    <img src="medimg/<?php echo $categories['prdct_img'];?>" alt="" style="width:80px;height:60px"><input type="file" name="new_image" id="image">
  </div><br/>
 
  <div class="control">
    <input type="submit" name="btnUpdate" value="Update" />
  </div>
</form>
<?php } else{ echo "<span style='font-size:20px; color:red;'>No Record Find</span>"; }?>
</body>
</html>