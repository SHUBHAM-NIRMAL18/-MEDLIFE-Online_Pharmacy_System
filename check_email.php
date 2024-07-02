<?php 
$email = $_POST['email'];
$conn = new mysqli('localhost','root','','medlife');
$sql = "select * from tbl_admin where email = '$email'";
$result = $conn->query($sql);
if($result->num_rows == 1)
    {
        echo "Email already taken";
    }
else
    {
        echo "Email Available";
    }

?>


