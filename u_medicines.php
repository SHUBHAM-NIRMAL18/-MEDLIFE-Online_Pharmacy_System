<?php

    $conn = new mysqli('localhost','root','','medlife');
    if($conn->connect_error){
        die("Connection failed:".$conn->connect_error);
        }
    $sql = 'select * from tbl_products ';
    $medicines = $conn->query($sql);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicines</title>
    <style>
        .product-card {
        width:200px;
        height:auto;
      background-color: #f8f8f8;
      padding: 20px;
      margin-top:50px;
      
      text-align: left;
      margin-left:50px;
      display:inline-block;
    }

     h2 {
      font-size: 24px;
      font-weight:normal;
      margin-top: 10px;
      font-family:"poppins";
      color:green;
      margin-left:20px;
    }

    .product-card img {
      width: 150px;
      height: auto;
      display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .product-card p {
      font-size: 18px;
      text-align:center;
      margin-bottom: 5px;
      font-family:"poppins";
    }

    .product-card .price {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
      font-family:"poppins";
      
    }

    .product-card .btn-buy-now {
      background-color: green;
      color: white;
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
      font-family:"poppins";
    }

    .product-card .btn-add-to-cart {
      background-color: yellow;
      color: black;
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-family:"poppins";
    }
    </style>
</head>
<body>
<?php include('header.php') ?>
<h2><u>Medicines</u></h2>
            <?php while($row = mysqli_fetch_assoc($medicines)){
            ?>
            <div class="product-card">
                <img src="<?php echo "medimg/".$row['prdct_img'];?>" style="width:100px ; height:80px" alt="Product Image">
                <p><?php echo $row['prdct_name'];?></p>
                <p class="price"><?php echo "Rs".$row['prdct_price'];?></p>
                <button class="btn-buy-now"><a href='single.php?id=<?php echo $row['prdct_id']; ?>' style="color:white ; text-decoration:none">Details</a></button>
                <button class="btn-add-to-cart"><a href='addToCart.php?id=<?php echo $row['prdct_id']; ?>' style="color:black ; text-decoration:none">Add to Cart</a></button>
            </div>
            <?php } ?>

    <?php include('footer.php') ?>
</body>
</html>


