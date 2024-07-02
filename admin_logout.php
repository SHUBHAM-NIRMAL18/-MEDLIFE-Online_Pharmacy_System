<?php 
session_start();
session_destroy();
//remove cookie
setcookie('email',null,time()-1);
setcookie('name',null,time()-1);
setcookie('admin_id',null,time()-1);
header('location:admin_login.php');
 ?>