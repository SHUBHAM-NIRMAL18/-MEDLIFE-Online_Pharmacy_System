

<?php 

    include('dashboard.php');
  $categories =[];
  try{
    $conn = new mysqli('localhost','root','','medlife');
    $sql = "SELECT * FROM tbl_products";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        array_push($categories, $row);
      }
    }
  }
  catch(Exception $e){
    die('Database error:'.$e->getMessage());

  }







?>





<!DOCTYPE html>
<html>
<head>
  <title>View Medicines</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .container{
        margin-top:100px;
    }
   table {

  margin-left:285px;
  border-collapse: collapse;
  border: 2px solid #555;
  border-radius: 10px;
  width:80%;
  font-family:"Poppins";
  font-size:14px;
  

  /* Add any other styles you want for the table */
}

table th,
table td {
  padding: 10px;
  border: 1px solid #555;
}
table th , table td {
  background-color: #f2f2f2;
}

a {
  display: block;
  text-align: center;
  margin: 10px;
  font-family:"Poppins";
  
}
a.button {
      display: inline-block;
      padding: 0px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    
    }
    a.buttom {
      display: inline-block;
      padding: 0px 20px;
      background-color: red;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    
    }
    .button1{
        margin-left:285px;
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
    
    
  </style>
</head>
<body>
  <div class="container">
  
  <a class = 'button1' href='products.php'>Add Products</a>
  <h2 style=" margin-left:800px;"><u>View All Products</u></h2>
  <?php if(isset($_GET['msg']) && $_GET['msg']== 1){ ?>
    <span style="margin-top:100px; margin-left:800px; color:red;">Invalid Request</span>

  <?php } ?>
    <table border=1>
      <tr>
        <th>SN</th>
        <th>Products Name</th>
        <th>Products Company</th>
        <th>Products Price</th>
        <th>Manf Date</th>
        <th>Exp Date</th>
        <th>Products Image</th>
        <th>Action</th>
      </tr>
      <?php for($i=0;$i< count($categories);$i++) { ?>
        <tr>
          <td><?php echo $i+1 ?></td>
          <td><?php echo $categories[$i]['prdct_name'] ?></td>
          <td><?php echo $categories[$i]['prdct_company'] ?></td>
          <td><?php echo $categories[$i]['prdct_price'] ?></td>
          <td><?php echo $categories[$i]['manf_date'] ?></td>
          <td><?php echo $categories[$i]['exp_date'] ?></td>
          <td><img src="medimg/<?php echo $categories[$i]['prdct_img']; ?>" alt="Product Image" class="product-image" style="width:100px ; height:50px"></td>
          <td>
            <a class='button' href="edit_products.php?prdct_id=<?php echo $categories[$i]['prdct_id'] ?>">Update</a>
            <a class='buttom' href="delete_products.php?prdct_id=<?php echo $categories[$i]['prdct_id'] ?>" onclick="return confirm('Are you sure?')">Delete </a>
          </td>
        </tr>
        <?php } ?>
    </table>
      </div>

</body>
</html>
