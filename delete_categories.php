<?php 
$id = $_GET['cat_id'];
try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "delete from tbl_categories where cat_id=$id";
  $conn->query($sql);
  if ($conn->affected_rows == 1 ) {
    echo "Categories delete success";
  }
  header('location:viewcat.php?action=1');
}
catch(Exception $e){
   die('Database  Error : ' .$e->getMessage());
}
 ?>