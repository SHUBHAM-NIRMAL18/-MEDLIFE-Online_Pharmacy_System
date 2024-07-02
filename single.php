

<?php  



if(isset($_GET['id'])){
    $product_id = $_GET['id'];
    $conn= new mysqli('localhost','root','','medlife');
   $sql = "SELECT * FROM tbl_products  WHERE prdct_id=$product_id";
   $result = mysqli_query($conn, $sql);
//    header('location:products.php');

$row = mysqli_fetch_assoc($result);
$prdct_img = $row['prdct_img'];
$product_name  = $row['prdct_name'];
 
$price  = $row['prdct_price'];

}
if (isset($_SESSION['cart'][$product_id]['quantity'])) {
  $quantity = $_SESSION['cart'][$product_id]['quantity'];
} else {
  $quantity = 1;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>

      .details{
        font-size:17px;
        font-family:'poppins';
        color:green;
        text-align:center;
      }
    .product-container {
      display: flex;
      align-items: center;
      width: 750px;
      height:400px;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 5px;
      margin-left:400px;
      font-family:"poppins";
    }

    .product-image {
      margin-right: 20px;
    }

    .product-details {
      flex-grow: 2;
      width:15px;
    }

    .product-details h2 {
      margin: 0;
      font-size: 25px;
    }

    .product-details p {
      margin: 5px 0;
      font-size: 14px;
      width:100px;
      
    }

    .counter {
      display: flex;
      align-items: center;
      margin-top: 10px;
      margin-left:150px;

    }
    .counter label {
      margin-right: 5px;
      
      
    }
    

    /* .counter button {
      padding: 5px 10px;
      background-color: black;
      color: white;
      border: none;
      cursor: pointer;
      margin-left: 30px;
      margin-right:30px;
      border:2px solid green;
    } */


    .add-to-cart-button1 {
      padding: 7px 14px;
      background-color: green;
      color: white;
      border: none;
      cursor: pointer;
      border-radius: 5px;
      font-size:11px;
      font-family:"poppins";
      margin-top:500px;
      
    }
    #quantity{
      font-size:20px;
      justify-content:center;
      width: 50%;
      text-align:center;
    }
  </style>
</head>
<body>


    
    <?php include('header.php') ?>
    <div class="main">
      <h2 class='details'>Details</h2>
    <div class="product-container">
    <div class="product-image">
      <img src="<?php echo "medimg/".$prdct_img; ?>" alt="Product Image" width="200" height="200">
    </div>
    <div class="product-details">
      <h2><?php echo $product_name; ?></h2>
      <p><?php echo "Rs.".$price; ?></p><br>
      <p>Description: Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
    </div>
    <form action="addToCart.php" method='GET'onsubmit="return validateQuantity();">
        <div class="counter">
          <label for="quantity">Quantity:</label>
          
          <input type="hidden" name='id' value='<?php echo  $product_id ?>'>
            <input type="number" step='1'  value='<?php echo $quantity; ?>' name="quantity" id="quantity">
            <p style="color: red;"><?php echo $error_message; ?></p>
          <button  type ="submit" class="add-to-cart-button1" name="btncart">AddtoCart</button>
        </div>
  </form>
  </div>
  <script>
        function validateQuantity() {
            var quantityInput = document.getElementById('quantity');
            var quantity = quantityInput.value;

            if (quantity < 0) {
                alert('Please enter a valid quantity.');
                return false;
            }

            return true;
        }
    </script>

  
</body>
</html>