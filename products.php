<?php 
require_once 'dashboard.php';
$categories=[];
$name = $company = $price = $manufactured = $expiry = $image = $manu = $exp ='';


$con = new mysqli('localhost','root','','medlife');
$sqli = "select * from tbl_categories";
$result = $con->query($sqli);
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
    array_push($categories, $row);
  }
}
if(isset($_POST['btnAdd'])){
  $err =[];
  if(isset($_POST['name']) && !empty($_POST['name']) && trim($_POST['name'])){
    $name = $_POST['name'];
  } else{
    $err['name'] = 'Please enter the product name';
  }
  if(isset($_POST['company']) && !empty($_POST['company']) && trim($_POST['company'])){
    $company = $_POST['company'];
  } else{
    $err['company'] = 'Please enter the product company';
  }
  if(isset($_POST['price']) && !empty($_POST['price']) && trim($_POST['price'])){
    $price = $_POST['price'];
    if($price < 1 ){
      $err['price'] = 'Please enter the valid price';
    }
  } else{
    $err['price'] = 'Please enter the product price';
  }
  if(isset($_POST['manufactured']) && !empty($_POST['manufactured']) && trim($_POST['manufactured'])){
    $manufactured = $_POST['manufactured'];
    $manu = strtotime($manufactured);
    if($manu > strtotime('now')){
    $err['manufactured'] = 'Manufactured date must be before future date';
  }
  } else{
    $err['manufactured'] = 'Please enter the product manufactured date';
  }
  if(isset($_POST['expiry']) && !empty($_POST['expiry']) && trim($_POST['expiry'])){
    $expiry = $_POST['expiry'];
    $exp = strtotime($expiry);
    if($exp < $manu){
    $err['expiry'] = 'Expiry date must be after than manufactured date';
  } 
  } else{
    $err['expiry'] = 'Please enter the product expiry date';
  }
  
  
  if ($_FILES['image']['name']){
    if($_FILES["image"]["type"]=="image/jpeg" || $_FILES["image"]["type"]=="image/jpeg" || $_FILES["image"]["type"]=="image/png" ){
            $image = $_FILES['image']['name'];
            $temp_name = $_FILES['image']['tmp_name'];
            $target_path = "medimg/";
            $target_path = $target_path . basename($image);
            move_uploaded_file($temp_name, $target_path);
    }else{
      $err['image'] = "Invalid image format";
    }
  } else{
    $err['image'] = 'Please choose the product image';
  }
  if(isset($_POST['category'])&& !empty($_POST['category'])){
  $cat_id=$_POST['category'];
  }else{
    $err['category']="Please select category";

  }
    if(count($err)==0){
      try{
      $conn = new mysqli('localhost','root','','medlife');
      $sql = "insert into tbl_products(prdct_name, prdct_company, prdct_price, manf_date, exp_date, prdct_img,cat_id) values('$name', '$company', '$price','$manufactured', '$expiry', '$image','$cat_id')";
      $conn->query($sql);
      if($conn->affected_rows == 1 && $conn->insert_id>0){
        $success = 'Product inserted successfully';
      }
    }catch(Exception $e){
      'Database Connection error' . $e->getMessage();
    }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Form</title>
    <style>
        body {
      display: grid;
      justify-content: center;
      align-items: center;
      height: 100vh;
      font-family: "Poppins", sans-serif;
    }

    form .form-group{
      width: 600px;
      /* margin-top:150px; */
      margin-left:290px;
      padding: 20px;
      background-color: #f5f5f5;
      border-radius: 5px;
      font-size:13px;
    }

    input[type="text"],input[type="date"], input[type=number]{
      width: 95%;
      padding: 5px;
      margin-bottom: 10px;
      border: 1px solid #ccc;
      border-radius: 5px;
      font-family: "Poppins";
    }
    select{
        width: 100%;
      padding: 5px;
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
      margin-top: 10px;
    }

    input[type="submit"]:hover {
      background-color: darkgreen;
    }
   
    .success{
        color: green;
    }
    .error{
      color: red;
    }
    
    </style>
</head>
<body>
  
  <h2 style="margin-top:80px; margin-left:500px;"><u>Add Products</u></h2>
  
    <form action="<?php echo ($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" method="post">
      <div class="form-group">
        <?php if(isset($success)){?>
          <span class="success">
          <?php echo $success;?>
          </span>
        <?php }?><br>
        <label for="">Product Name</label>
        <input type="text" name="name" id="name" value="<?php echo $name;?>">
        <span class="error">
        <?php if (isset($err['name'])){?>
          <?php echo $err['name']; ?>
        <?php } ?>
      </span><br>
        <label for="">Product Company</label>
        <input type="text" name="company" id="company" value="<?php echo $company;?>">
        <span class="error">
        <?php if (isset($err['company'])){?>
          <?php echo $err['company']; ?>
        <?php } ?>
      </span><br>
        <label for="">Product Price</label>
        <input type="number" name="price" id="price" value="<?php echo $price;?>">
        <span class="error">
        <?php if (isset($err['price'])){?>
          <?php echo $err['price']; ?>
        <?php } ?>
      </span><br>
        <label for="">Manufactured Date</label>
        <input type="date" name="manufactured" id="manufactured" value="<?php echo $manufactured;?>">
        <span class="error">
        <?php if (isset($err['manufactured'])){?>
          <?php echo $err['manufactured']; ?>
        <?php } ?>
        <?php if(isset($error)){ ?>
          <?php echo $error;?>
        <?php }?>
      </span><br>
        <label for="">Expiry Date</label>
        <input type="date" name="expiry" id="expiry" value="<?php echo $expiry;?>">
        <span class="error">
        <?php if (isset($err['expiry'])){?>
          <?php echo $err['expiry']; ?>
        <?php } ?>
      </span><br>
      <label for="">Product Image</label><br>
        <input type="file" name="image" id="image"><br>
        <span class="error">
        <?php if (isset($err['image'])){?>
          <?php echo $err['image']; ?>
        <?php } ?>
      </span><br>
      <label for="">Category type</label>
      <select name="category" id="category">
      <option value="">Select category type</option>
      <?php foreach($categories as $c) {?>
        <option value="<?php echo $c['cat_id']?>"><?php echo $c['cat_name']?></option>
        <?php }?>
      </select><span class="error">
        <?php if (isset($err['category'])){?>
          <?php echo $err['category']; ?>
        <?php } ?>
      </span><br>
      <input type="submit" name="btnAdd" value="Add Products">
      </div>
    </form>
</body>
</html>