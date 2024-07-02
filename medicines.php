
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .product-card {
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 10px;
  text-align: center;
  width: 200px;
  margin-top:250px;
  margin-left:50px;
}

.product-image {
  max-width: 100%;
}

.product-title {
  margin-top: 10px;
  font-size: 18px;
}

.product-price {
  font-weight: bold;
  margin-top: 5px;
}

.add-to-cart {
  background-color: green;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 8px 12px;
  margin-top: 10px;
  cursor: pointer;
}

.add-to-cart:hover {
  background-color: darkgreen;
}

    </style>
</head>
<body>
<?php include('header.php') ?>
<div class="product-card">
  <img src="medimg/Zarrt 50.jpeg" alt="Product Image" class="product-image">
  <h3 class="product-title">Zart 50 mg</h3>
  <p class="product-price">$19.99</p>
  <button class="add-to-cart">Add to Cart</button>
</div>

</body>
</html>