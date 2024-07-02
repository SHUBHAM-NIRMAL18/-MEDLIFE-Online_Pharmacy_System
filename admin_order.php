<?php include_once('dashboard.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order</title>
    <style>
        table {
      width: 50%;
      border-collapse: collapse;
      margin-left:500px;
      /* margin-top:130px; */
    }
    th, td {
      padding: 8px;
      text-align: center;
      border: 1px solid #ddd;
    }
    th {
      background-color: #f2f2f2;
    }
    a.viewdetails{
        
      display: inline-block;
      padding: 5px 10px;
      background-color: #4CAF50;
      color: white;
      font-size:12px;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    }
    </style>
</head>
<body>
<table> 
          <h2 style="margin-top:110px;margin-left:500px;">View Order</h2><br>
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Tracking No</th>
              <th>Customer Name</th>
              <th>Date</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
          <?php
          
          $conn = new mysqli('localhost','root','','medlife');
          $orders = "SELECT * FROM tbl_order ORDER BY order_id DESC ";
          $order_run = $conn->query($orders);
          if(mysqli_num_rows($order_run) > 0)
          {
            foreach($order_run as $items){
      
          
      
      
          ?>
            <tr>
              <td><?php echo $items['order_id']; ?></td>
              <td><?php echo $items['tracking_order']; ?></td>
              <td><?php echo $items['user_name']?></td>
              <td><?php echo $items['created_at']; ?></td>
              <td><?php echo $items['total']; ?></td>
              <td>
                <a class="viewdetails" href="view_order.php?order_id=<?php echo $items['order_id'];?>">View Details</a>
              </td>
            </tr>
            
            <?php } }?>
          </tbody>
        </table>
</body>
</html>