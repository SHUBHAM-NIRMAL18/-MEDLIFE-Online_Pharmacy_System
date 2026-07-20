<?php 

require_once 'config.php';
$id = $_GET['prdct_id'];
try{
  $conn = get_db_connection();
  
  $sql = "delete from tbl_products where prdct_id=$id";
  $conn->query($sql);
  if ($conn->affected_rows == 1 ) {
    echo "Supplements delete success";
  }
  header('location:view_supplements.php?action=1');
}
catch(Exception $e){
   die('Database  Error : ' .$e->getMessage());
}
 ?>