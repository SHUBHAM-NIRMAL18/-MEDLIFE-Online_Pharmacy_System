<?php error_reporting(0); ?>
<?php session_start(); ?>

<!DOCTYPE html>
<html>
<head>
<link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Sans Serif Collection:wght@400&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Sansation:wght@700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Sansation Light:wght@300&display=swap"
    />
    <link 
    rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <style>
    *{
        margin:0px;
        padding:0px;
    }
    
    .container {
      background-color: green;
      padding: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .icons {
      display: flex;
      justify-content: flex-start;
      align-items: right;
      color:yellow;
    }

    .contact {
      text-align: right;
      color: yellow;
      font-family:"Poppins";
      font-size:12px;
    }

    .icon {
      width: 25px;
      height: 25px;
      margin-right: 10px;
    }
    .header {
      
      padding: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      margin-right: 20px;
    }

    .logo img {
      width: 190px;
      height: auto;
    }

    .nav {
      display: flex;
      align-items: center;
      font-family:"Poppins";
      font-size:14px;
      
    }

    .nav-item {
      margin-right: 10px;
      text-decoration:none;
    }

    .button {
      display: inline-block;
      padding: 5px 10px;
      background-color: green;
      color: white;
      text-decoration: none;
      border-radius: 0px;
    }

    .cart-icon {
      width: 25px;
      height: 25px;
      margin-left: 10px;
    }

    .cart-container {
      background-color: green;
      padding: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
</style>
<body>
    <div class="main">
  <div class="container">
    <div class="icons">
        <p >
        <?php 
      if(isset($_SESSION['email']))
      { 
        echo "Welcome"." ". $_SESSION['name'];
        }
        else
        {
          echo "Welcome Guest";
          } ?>
        </p>
    </div>
    <div class="contact">
    <a href="admin_login.php" style="color:yellow ; text-decoration:none; ">Admin Login |</a>
    <a href="user_dashboard.php" style="color:yellow ; text-decoration:none">My Account</a>
    </div>
    
  </div>


  <div class="header">
    <div class="logo">
      <a href="index.php"><img src="logo/MEDLOGO.png" alt="Logo"></img></a>
    </div>
    <div class="nav">
      <div class="nav-item"><a href="index.php" style="color:black ; text-decoration:none">HOME</a></div>
      <div class="nav-item"><a href="u_medicines.php" style="color:black ; text-decoration:none">MEDICINES</a></div>
      <div class="nav-item"><a href="u_supplements.php"style="color:black ; text-decoration:none">SUPPLEMENTS</a></div>
      <div class="nav-item"><a href="u_devices.php"style="color:black ; text-decoration:none">DEVICES</a></div>
      <div class="nav-item"><?php 
      if(!isset($_SESSION['email']))
      { 
        echo "<a href='customer_login.php' class='button'>LOGIN</a>";
        }
        else
        {
          echo "<a href='user_logout.php' class='button'>LOGOUT</a>";
          } ?>
          </div>
      <div class="nav-item"><a href="customer_register.php" class="button">SIGNUP</a></div>
      <div class="nav-item"><a href="cart.php" class="button"><i class="bx bx-cart-add"></i></a></div>
    </div>
  </div><hr>
  