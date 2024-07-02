<?php 
$id = $_GET['prdct_id'];
try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "delete from tbl_products where prdct_id=$id";
  $conn->query($sql);
  if ($conn->affected_rows == 1 ) {
    echo "Product delete success";
  }
  header('location:view_products.php?action=1');
}
catch(Exception $e){
   die('Database  Error : ' .$e->getMessage());
}
 ?>