<?php include('header.php'); ?>
<?php 
session_start();

if (!isset($_SESSION['login_status'])) {
  header('location:customer_login.php?msg=1');
}

?>
    


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        .sidebar {
          float: left;
        background-color: #f4f4f4;
        width: 200px;
        padding: 50px;
        height:500px;
        }

        .sidebar h4 {
        color: green;
        font-size: 20px;
        margin-bottom: 20px;
        font-family:"poppins";
        }

        .sidebar ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
        font-family:"poppins";
        
        }

        .sidebar li {
        margin-bottom: 30px;
        }

        .sidebar a {
        color: #666;
        text-decoration: none;
        font-size: 18px;
        }

        .sidebar a:hover {
        color: #333;
        font-weight: bold;
        }
        .main-content {
        margin-left: 300px;
        padding: 20px;
        font-family:"poppins";
      }

      .order-item {
          margin-bottom: 10px;
      }

      .order-date {
          font-weight: bold;
      }

      .order-details {
          margin-left: 10px;
      }
      table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 8px;
      text-align: left;
      border: 1px solid #ddd;
    }
    th {
      background-color: #f2f2f2;
    }

    </style>
</head>
<body>
<div class="sidebar">
    <h4><u>Welcome <?php echo $_SESSION['name'] ?></u></h4>
      <ul>
          <li><a href="#">Your Info</a></li><hr>
          <li><a href="#">Change Password</a></li><hr>
          <li><a href="user_logout.php">Logout</a></li><hr>
      </ul>
</div>

<div class="main-content">
        <h2>Order History</h2>
        <!-- <div class="order-list">
            <div class="order-item">
                <span class="order-date">2023-07-01</span>
                <span class="order-details">Product A x 2 - $20</span>
            </div>
            <div class="order-item">
                <span class="order-date">2023-06-28</span>
                <span class="order-details">Product B x 1 - $10</span>
            </div>
            Add more order items here 
        </div>-->
        <table> 
          
    <thead>
      <tr>
        <th>Order ID</th>
        <th>Tracking No</th>
        <th>Date</th>
        <th>Total</th>
        <th>Order Status</th>
      </tr>
    </thead>
    <tbody>
    <?php
    if(isset($_SESSION['user_id']))
    {
      $uid=$_SESSION['user_id'];
    }
    $conn = new mysqli('localhost','root','','medlife');
    $order = "SELECT * FROM tbl_order where user_id = $uid";
    $order_run = $conn->query($order);
    if(mysqli_num_rows($order_run) > 0)
    {
      foreach($order_run as $items){

    


    ?>
      <tr>
        <td><?php echo $items['order_id']; ?></td>
        <td ><?php echo $items['tracking_order']; ?></td>
        <td><?php echo $items['created_at']; ?></td>
        <td ><?php echo $items['total']; ?></td>
        <td>
          <?php if($items['status'] == 0 ){
            echo "<span style='background:yellow;'>Under Process</span>";
          }else if($items['status'] == 1){
            echo "<span style='background:Green;'>Completed</span>";
          }
          else if($items['status'] == 2){
            echo "<span style='background:red;'>Cancelled</span>";
          }?>
        </td>
      </tr>
      
      <?php } 
    
    }else{
      echo "<span style='background:red;'>No Order Yet</span>";
    }
    ?>
    </tbody>
  </table>
    </div>

</body>

</html>