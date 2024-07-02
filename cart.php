<?php 
    
    session_start();
    if(isset($_SESSION['cart'])){
    $cart =  $_SESSION['cart'];
    }
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart</title>
    <style>

        .cont{
            
      display: flex;
      justify-content: center;
      align-items: center;
      height: 10vh;
      
    
        }
        table {
      border-collapse: collapse;
      width: 50%;
      justify-content: center;
      margin-left:410px;
      
      font-family:"poppins";
    }
    h2{
        font-family:"poppins";
        /* margin-left:310px; */
    }

    th, td {
      padding: 8px;
      text-align: center;
      border: 1px solid #ddd;
    }

    .counter {
      display: flex;
      align-items: center;
    }

    .counter button {
      padding: 5px 10px;
      background-color: #f1f1f1;
      border: none;
      cursor: pointer;
    }
    /* .remove-button {
      display: flex;
      justify-content: center;
    } */

    .remove-button button {
      background-color: red;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      margin-left:10px;
      font-family:"poppins";
    }
    .checkout-button {
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      font-family:"poppins";
    }
    </style>
</head>
<body>
    <?php include('header.php'); ?>

    <h2 style="color:green; text-align:center"><u>Cart</u></h2>
    <div class="cont"></div>
    <!-- <form action="checkout.php" method="GET"> -->
    <table id="cartTable">
      
    <thead>
      <tr>
           <th>Image</th>
           <th>Product</th>
           <th>Price</th>
           <th>Quantity</th>
           <th> Sub Total</th>
           <th>Action</th>
      </tr>
    </thead>
    <tbody>
    <?php
       $total = 0;

       foreach($cart as $key => $value){
        // echo $key ." : ". $value['quantity'] . "<br>";
        $conn = new mysqli('localhost','root','','medlife');
        $sql = "SELECT * FROM tbl_products where prdct_id = $key";
        $result = mysqli_query($conn, $sql);
        $num_of_rows = mysqli_num_rows($result);
        
        
        $row = mysqli_fetch_assoc($result)
                ?>
      <tr>
        
        <td><img src='<?php echo "medimg/". $row['prdct_img'] ?>' alt='' style="width:100px ; height:80px"></td>
        <td><a href="single.php?id=<?php echo $row['prdct_id']?>"><?php echo $row['prdct_name'] ?></a></td>
        <td ><?php echo $row['prdct_price'] ?></td>
        <td> <?php echo $value['quantity'] ?></td>
        
        <td><?php echo (int)$row['prdct_price'] * (int)$value['quantity'] ?></td>
        <td class="remove-button"><button ><a style="color:white ; text-decoration:none" href='delete_cart.php?id=<?php echo $key; ?> '>Remove </button></td>
      </tr>
      <?php $total = $total +  ($row['prdct_price'] * $value['quantity']);
    } ?>
      
      
    </tbody>
    
  </table>
  
  <table>
    <tr>
      <th>Total</th>
      <td><?php echo $total; ?>.00/-</td>
    </tr>
    <tr>
    <th>
      <button class="checkout-button"><a href="index.php" style="color:white ; text-decoration:none">Continue Shopping</a></button>
      </th>
      <th >
        <button class="checkout-button" value="Checkout"><a href="checkout.php" style="color:white ; text-decoration:none">Checkout</a></button>
      </th>
      
    </tr>
    
  </table>
  </form>
  </div>
  
  
</body>
<?php include('footer.php') ?>
</html>