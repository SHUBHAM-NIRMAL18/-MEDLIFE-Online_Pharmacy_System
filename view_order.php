<?php include_once('dashboard.php'); ?>
<?php 
    if(isset($_POST['btnStatus'])){
        $odr_id = $_POST['order_id'];
        $order_status = $_POST['order_status'];

        $conn = new mysqli('localhost','root','','medlife');
        $status = "update tbl_order set status='$order_status' where order_id='$odr_id'";
        $status_query= mysqli_query($conn,$status);
       
        echo "<script> alert('Status Updated Sucessfully');</script>";
        
        
        
    }


?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details</title>
    <style>
        .orderstatus {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            margin-top:100px;
            margin-left:500px;
        }

        .order-container {
            display: flex;
        }

        .customer-details {
            flex: 1;
            margin-right: 20px;
        }

        .customer-details h3 {
            margin-bottom: 10px;
        }

        .customer-details form {
            text-align: left;
        }

        .customer-details label {
            display: block;
            margin-top: 10px;
        }

        .customer-details input,
        .customer-details textarea {
            width: 100%;
            padding: 8px;
        }

        .order-details {
            flex: 1;
            margin-left:30px;
        }

        .order-details h3 {
            margin-bottom: 10px;
        }

        .product {
            display: flex;
            align-items: center;
            margin-top: 20px;
        }

        .product img {
            width: 100px;
            margin-right: 10px;
        }

        .product-info {
            text-align: left;
        }

        .product-info p {
            margin: 5px 0;
        }

        .product-info p span {
            font-weight: bold;
        }
        .order-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            text-align:center;
        }

        .order-summary-table th, .order-summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .order-summary-table th {
            background-color: #f2f2f2;
        }

        .order-summary-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .order-summary-table img {
            width: 50px;
        }

        .order-details p {
            margin: 5px 0;
        }

        .order-details p span {
            font-weight: bold;
        }

        .order-status {
            padding: 5px;
            border-radius: 4px;
        }
                .update-status {
                    display: block;
                    margin-top: 20px;
                    margin-left:95px;
                    padding: 10px 20px;
                    background-color: green;
                    color: #fff;
                    text-decoration: none;
                    font-weight: bold;
                    border-radius: 4px;
                    cursor: pointer;
                }

                .update-status:hover {
                    background-color: #0056b3;
                }
                .total-row {
            font-weight: bold;
        }

.total-row td {
    text-align: right;
}

    </style>
</head>
<body>
    <div class="orderstatus">
        <h2><u>Order Details</u></h2>
        <div class="order-container">
            <div class="customer-details">
                <h3>Customer Details</h3>
                <?php 
                if(isset($_GET['order_id'])){
                    $order_id = $_GET['order_id'];

                    $conn = new mysqli('localhost','root','','medlife');
                    $query="select * from tbl_order where order_id = '$order_id'";
                    $result=$conn->query($query);
                    $data = mysqli_fetch_assoc($result);


                }
                
                ?>
                <form>
                    <label for="name">Tracking No:</label>
                    <input type="text" id="name" name="name" value='<?php echo $data['tracking_order']; ?>'>
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" value='<?php echo $data['user_name']; ?>'>
                    
                    <label for="address">Address:</label>
                    <input type='text' id="address" name="address" value='<?php echo $data['address']; ?>' >
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" value='<?php echo $data['phone']; ?>'>
                </form>
            </div>
            
                
                <div class="order-details">
                <h3>Order Summary</h3>
                <table class="order-summary-table">
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        
                        
                    </tr>
                    <?php 
                            $conn = new mysqli('localhost','root','','medlife');
                            $order= "select * from tbl_orderitems where order_id= $order_id ";
                            $order_query= mysqli_query($conn,$order);

                            if(mysqli_num_rows($order_query) > 0 ){
                                foreach($order_query as $items){
                              
                               
                        ?>
                    <tr>
                        <td><?php echo $items['prdct_name']; ?></td>
                        <td><span class="quantity"><?php echo $items['quantity'] ; ?></span></td>
                        <td><span class="price"><?php echo $items['price'] ; ?></span></td>
                        
                    </tr>
                    <?php } } ?>
                    <tr class="total-row">
                        <td colspan="2" class="text-right">Total:</td>
                        <td><span class="total"><?php echo $data['total']; ?></span></td>
                    </tr>
                </table>
                <p>Payment Method: <span class="payment-method"><?php echo $data['payment'];?></span></p>
                <p>Order Status: </p>
                    <form action="" method="POST">
                    <input type="hidden" name="order_id" value="<?php echo $data['order_id'];?>">
                    <select class="order-status" name="order_status">
                        <option value="0" <?php echo $data['status']== 0?"selected":"";?>>Under Process</option>
                        <option value="1" <?php echo $data['status']== 1?"selected":"";?>>Completed</option>
                        <option value="2" <?php echo $data['status']== 2?"selected":"";?>>Cancled</option>
                        
                    </select>
                
                <button  name="btnStatus"class="update-status">Update Status</button>
                </form>
            </div>
               
        </div>
    </div>
</body>
</html>
