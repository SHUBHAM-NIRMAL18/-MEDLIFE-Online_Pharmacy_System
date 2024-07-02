
<?php include('header.php'); ?>

<?php 
  if(!isset($_SESSION['email'])){
    header("Location: customer_login.php");
  }

  

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <style>
    .containerc {
  width: 400px;
  /* margin: 0 auto; */
  padding: 20px;
  margin-left:550px;
  
}

h1 {
  text-align: center;
  color:green;
  font-family:"poppins";
}

.form-groupc {
  margin-bottom: 20px;
  font-family:"poppins";
  
}

label {
  display: block;
  font-weight: bold;
  margin-bottom: 5px;
}

input[type="text"],
input[type="tel"],
textarea,
select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}

.radio-group label {
  display: block;
  margin-bottom: 10px;
}

.radio-group input[type="radio"] {
  margin-right: 5px;
}

.checkbox-group input[type="checkbox"] {
  margin-right: 5px;
}

input[type="submit"] {
  width: 100%;
  padding: 10px;
  background-color: #4caf50;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-family:"poppins";
}

input[type="submit"]:hover {
  background-color: #45a049;
}


.card-content {
  text-align: left;
  background-color: #f2f2f2;
  border-radius: 5px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  padding: 20px;
  font-family:"poppins";
  
}

.product {
  display: flex;
  justify-content: space-between;
  margin-bottom: 10px;
}

.product-name {
  flex-grow: 1;
}

.product-quantity,
.product-price {
  margin-left: 10px;
}

.grand-total {
  font-weight: bold;
}


  </style>
</head>


<body>


  <div class="containerc">
    <h1>Checkout</h1><br>
 
      <div class="card-content ">
        <h3>Order Summary</h3>
          <?php

       
            if(isset($_SESSION['cart'])){
              $cart= $_SESSION['cart'];
            }
            $total= 0;
            foreach($cart as $key => $value){
              
              $conn = new mysqli('localhost','root','','medlife');
              $sql_cart = "SELECT * FROM tbl_products where prdct_id = $key";
              $result_cart = mysqli_query($conn, $sql_cart);
              $row_cart = mysqli_fetch_assoc($result_cart);
              
            
          ?>
          <div class="product">
            <span class="product-name"><?php echo $row_cart['prdct_name']; ?></span>
            <span class="product-quantity"><?php echo $value['quantity']; ?>* </span>
            <span class="product-price">Rs.<?php echo $row_cart['prdct_price']; ?></span>
          </div>
              <?php $total = $total +  ($row_cart['prdct_price'] * $value['quantity']); }
              if(empty($_SESSION['cart'])){
                echo "<div style='background:red;color:white'>No Products in Cart</div>";
              } ?>
              <hr>
              
          <div class="product">
            <span class="product-name">Delivery Charge</span>
            <span class="product-price">Rs.100</span>
          </div>

          <div class="product grand-total">
            <span class="product-name">Grand Total</span>
            <span class="product-price">Rs.<?php if(empty($_SESSION['cart'])){
                echo $total;
              }else{echo $total+100;} ?></span>
          </div>
        
      </div><br>

  <?php 
    $id = $_SESSION['user_id'];
  $connection = new mysqli('localhost','root','','medlife');
      $sql = "select * from tbl_user where user_id=$id";
      $result = $connection->query($sql);
      if ($result->num_rows > 0) {
        // Fetch the data for each row
        while ($row = $result->fetch_assoc()) {
            $name = $row["name"];
            $address = $row["address"];
            $phone = $row["phone"];
        }
      }

      if(isset($_POST['btnOrder'])){
        $err = [];
          if (isset($_POST['fullname']) && !empty($_POST['fullname']) && trim($_POST['fullname'])){
            $fullname = $_POST['fullname'];
        }
        else{
          $err['fullname'] = '*Please enter your fullname';
        }
        if (isset($_POST['phone']) && !empty($_POST['phone']) && trim($_POST['phone'])){
          $phone = $_POST['phone'];
      }
      else{
        $err['phone'] = '*Please enter your phone';
      }
      if (isset($_POST['address']) && !empty($_POST['address']) && trim($_POST['address'])){
        $address = $_POST['address'];
        }
        else{
          $err['address'] = '*Please enter your address';
        }
        
        if (isset($_FILES['prescription']['name']) && !empty($_FILES['prescription']['name']) && trim($_FILES['prescription']['name'])){
          $prescriptionName = $_FILES["prescription"]["name"];
          $prescriptionTempName = $_FILES["prescription"]["tmp_name"];

          // Move the uploaded file to a desired location
          $uploadPath = "prescriptions/" . $prescriptionName;
          move_uploaded_file($prescriptionTempName, $uploadPath);
          }
          else{
            $err['prescription'] = '*Please upload your prescription';
          }
        if (isset($_POST['payment']) && !empty($_POST['payment']) && trim($_POST['payment'])){
          $payment = $_POST['payment'];
      }
      else{
        $err['payment'] = '*Please enter your payment';
      }
      if (isset($_POST['terms']) && !empty($_POST['terms']) && trim($_POST['terms'])){
        $terms = $_POST['terms'];
    }
    else{
      $err['terms'] = '*Please enter your terms';
    }
    if(count($err) == 0){
      
        $uid=$_SESSION['user_id'];
      

        $tracking_order = "medlife". rand(111,999);
        $conn = new mysqli('localhost','root','','medlife');
        $query = "insert into tbl_order(tracking_order, user_id,user_name, phone, address, payment, prescription, total) values('$tracking_order','$uid','$fullname','$phone','$address','$payment','$uploadPath','$total')";
        $query_run = mysqli_query($conn,$query);


        if($query_run)
        {

          $order_id = mysqli_insert_id($conn);

        
          if(isset($_SESSION['cart']))
          {
            $cart= $_SESSION['cart'];
            foreach($cart as $key => $value)
            {
              
              $conn = new mysqli('localhost','root','','medlife');
              $sql_cart = "SELECT * FROM tbl_products where prdct_id = $key";
              $result_cart = mysqli_query($conn, $sql_cart);
              $row_cart = mysqli_fetch_assoc($result_cart);
              $products=$row_cart['prdct_name'];
              $prdct_id = $row_cart['prdct_id'];
              $price=$row_cart['prdct_price'];
              $qnty=$value['quantity'];


              $sql_order="insert into tbl_orderitems(order_id, prdct_id,prdct_name, quantity, price) values ('$order_id','$prdct_id','$products','$qnty','$price') ";
              $query_items=mysqli_query($conn,$sql_order);

              if($conn->affected_rows == 1 && $conn-> insert_id > 0)
              {
                echo "<script>alert('Order placed');</script>";
                unset($_SESSION['cart']);
                echo "<script>window.location.href = 'order_placed.php';</script>";
              }
              
                
            }
            
          }
        }
    }
  }
      
  ?>
  
    
    <form action="<?php echo $_SERVER['PHP_SELF']?>" method="POST" enctype="multipart/form-data">
      <div class="form-groupc">
        <label for="fullname">Full Name:</label>
        <input type="text" id="fullname" name="fullname" value="<?php echo $name;?>" >
        <div style="color:red;font-size:12px"><?php echo (isset($err['fullname'])?$err['fullname']:'');?></div>
        
      </div>
      <div class="form-groupc">
        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone" value="<?php echo $phone;?>" >
        <div style="color:red;font-size:12px"><?php echo (isset($err['phone'])?$err['phone']:'');?></div>
      </div>
      <div class="form-groupc">
        <label for="address">Full Address:</label>
        <input type='text'id="address" name="address" value="<?php echo $address;?>">
        <div style="color:red;font-size:12px"><?php echo (isset($err['address'])?$err['address']:'');?></div>
      </div>
      <div class="form-groupc">
        <label for="prescription">Prescription:</label>
        <input type="file"  name="prescription" accept=".pdf, .doc, .docx">
        <div style="color:red;font-size:12px"><?php echo (isset($err['prescription'])?$err['prescription']:'');?></div>
      </div>
      <div class="form-groupc">
        <label>Payment Mode:</label>
        <div class="radio-group">
          <label>
            <input type="radio" name="payment" value="cod">
            Cash on Delivery
          </label>
          <label>
            <input type="radio" name="payment" value="online" >
            Online Payment
          </label>
          <div style="color:red;font-size:12px"><?php echo (isset($err['payment'])?$err['payment']:'');?></div>
        </div>
      </div>
      <div class="form-groupc">
        <label>
          <input type="checkbox" name="terms" value="true" >
          I accept the terms and conditions
        </label>
        <div style="color:red;font-size:12px"><?php echo (isset($err['terms'])?$err['terms']:'');?></div>
      </div>
      <input type="submit" value="Place Order (Rs.<?php echo $total+100;?>)" name="btnOrder">
      
    </form>


    
</div>
  



</body>
</html>

