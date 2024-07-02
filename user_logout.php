<?php 
session_start();
session_destroy();
//remove cookie
setcookie('email',null,time()-1);
setcookie('name',null,time()-1);
setcookie('user_id',null,time()-1);
header('location:customer_login.php');
 ?>