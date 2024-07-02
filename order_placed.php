<?php include_once('header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank you</title>
    <style>
        .pot {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    text-align: center;
    font-family:"poppins";
}

.thank-you {
    color: #008000;
}

.message {
    font-size: 18px;
}

.order-history {
    margin-top: 30px;
}

.ordercard {
    border: 1px solid #ddd;
    padding: 20px;
    margin-top: 20px;
    text-align: left;
    box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
}

.ordercard h4 {
    margin-bottom: 10px;
}

.ordercard p {
    margin: 5px 0;
}

.order-id {
    font-weight: bold;
}
.dashboard-button {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background-color: #007bff;
    color: #fff;
    text-decoration: none;
    font-weight: bold;
    border-radius: 4px;
}

.dashboard-button:hover {
    background-color: #0056b3;
}
    </style>
</head>
<body>
<div class="pot">
<?php
    
    $conn = new mysqli('localhost','root','','medlife');
    $sql = "SELECT * FROM tbl_order ORDER BY order_id DESC LIMIT 1";
    $result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Display the record
    $row = $result->fetch_assoc();
    ?>
        <h2 class="thank-you">Thank You!</h2>
        <p class="message">Your order has been placed successfully.</p>
        
        <h3 class="order-history">Order History</h3>
        
        <!-- Add PHP code here to fetch and display the order history from the database -->
        
        <div class="ordercard">
            <h4>Order ID: <span class="order-id"><?php echo $row['order_id'] ; ?></span></h4>
            <p>Tracking Order: <span class="tracking-order"><?php echo $row['tracking_order'] ; ?></span></p>
            <p>User Name: <span class="user-id"><?php echo $row['user_name'] ; ?></span></p>
            <p>Phone: <span class="phone"><?php echo $row['phone'] ; ?></span></p>
            <p>Address: <span class="address"><?php echo $row['address'] ; ?></span></p>
            <p>Payment: <span class="payment"><?php echo $row['payment'] ; ?></span></p>
            <p>Total: <span class="total"><?php echo $row['total'] ; ?></span></p>
            <p>Order At: <span class="created-at"><?php echo $row['created_at'] ; ?></span></p>
            
        </div>
        <?php }
    else {
        echo "No records found.";
    } ?>
        <a href="user_dashboard.php" class="dashboard-button">Go to Dashboard</a>
    </div>
    
    <?php  include_once('footer.php') ?>
</body>
</html>