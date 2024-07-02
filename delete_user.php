<?php 
$id = $_GET['user_id'];
try{
  $conn = new mysqli('localhost','root','','medlife');
  
  $sql = "delete from tbl_user where user_id=$id";
  $conn->query($sql);
  if ($conn->affected_rows == 1 ) {
    echo "Categories delete success";
  }
  header('location:view_user.php?action=1');
}
catch(Exception $e){
   die('Database  Error : ' .$e->getMessage());
}
 ?>