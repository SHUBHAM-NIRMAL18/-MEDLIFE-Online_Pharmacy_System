<?php include_once 'dashboard.php' ?>

<!DOCTYPE html>
<html>
<head>
    <title>Home</title>
    <style>
        .container{
            margin-top:100px;
            margin-left:400px;
            border-radius:5px;
        }
        .blue {
            background-color: lightblue;
            height: 120px;
            width: 450px;
            margin-right: 140px;
            margin-bottom: 20px;
            float: left;
            border-radius:5px;
            text-align:center;
            
        }
        
        .green {
            background-color: green;
            height: 120px;
            width: 450px;
            margin-right: 10px;
            margin-bottom: 10px;
            float: left;
            border-radius:5px;
            text-align:center;
        }
        
        .yellow {
            background-color: yellow;
            height: 120px;
            width: 450px;
            margin-right: 140px;
            margin-bottom: 20px;
            float: left;
            border-radius:5px;
            text-align:center;
        }
        
        .black {
            background-color: black;
            height: 120px;
            width: 450px;
            margin-right: 10px;
            margin-bottom: 10px;
            float: left;
            border-radius:5px;
            color:white;
            text-align:center;
        }
        
        .container {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
            try{
                $conn = new mysqli('localhost','root','','medlife');
                if($conn->connect_error){
                    die("Connection failed:".$conn->connect_error);
                    }
                $query = "SELECT count(*) from tbl_products";
                $result=$conn->query($query);
                while ($row=mysqli_fetch_array($result))
                {$totalMedicine=$row[0];}
            }
            catch(Exception $e){
                die('Database error :'.$e->getMessage());
            
              }
        ?>
        <div class="blue"><h2>Total Products</h2>
        <div style='font-size:60px;'><?php echo $totalMedicine; ?></div>
        </div>
        <?php
            try{
                $conn = new mysqli('localhost','root','','medlife');
                if($conn->connect_error){
                    die("Connection failed:".$conn->connect_error);
                    }
                $query = "SELECT count(*) from tbl_categories";
                $result=$conn->query($query);
                while ($row=mysqli_fetch_array($result))
                {$totalsupp=$row[0];}
            }
            catch(Exception $e){
                die('Database error :'.$e->getMessage());
            
              }
        ?>
        <div class="green"><h2>Total Categories</h2>
        <div style='font-size:60px;color:white;'><?php echo $totalsupp; ?></div>
        </div>
    </div>
    <div class="container">
    <?php
            try{
                $conn = new mysqli('localhost','root','','medlife');
                if($conn->connect_error){
                    die("Connection failed:".$conn->connect_error);
                    }
                $query = "SELECT count(*) from tbl_order";
                $result=$conn->query($query);
                while ($row=mysqli_fetch_array($result))
                {$totaldevice=$row[0];}
            }
            catch(Exception $e){
                die('Database error :'.$e->getMessage());
            
              }
        ?>
        <div class="yellow"><h2>Total Orders</h2>
        <div style='font-size:60px;'><?php echo $totaldevice; ?></div>
        </div>
        <?php
            try{
                $conn = new mysqli('localhost','root','','medlife');
                if($conn->connect_error){
                    die("Connection failed:".$conn->connect_error);
                    }
                $query = "SELECT count(*) from tbl_user ";
                $result=$conn->query($query);
                while ($row=mysqli_fetch_array($result))
                {$totalorder=$row[0];}
            }
            catch(Exception $e){
                die('Database error :'.$e->getMessage());
            
              }
        ?>
        <div class="black"><h2>Registered Customers</h2>
        <div style='font-size:60px;color:white;'><?php echo $totalorder; ?></div>
        </div>
    </div>
</body>
</html>

