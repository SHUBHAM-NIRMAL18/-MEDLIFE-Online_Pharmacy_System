<?php include ('dashboard.php') ?>

<?php 
  $categories =[];
  try{
    $conn = new mysqli('localhost','root','','medlife');
    $sql = "SELECT * FROM tbl_categories";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        array_push($categories, $row);
      }
    }
  }
  catch(Exception $e){
    die('Database error:'.$e->getMessage());

  }







?>





<!DOCTYPE html>
<html>
<head>
  <title>View Categories</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .container{
        margin-top:100px;
    }
   table {

  margin: 0 auto;
  border-collapse: collapse;
  border: 2px solid #555;
  border-radius: 10px;
  width:50%;
  font-family:"Poppins";
  font-size:14px;
  margin-left:500px;
  

  /* Add any other styles you want for the table */
}

table th,
table td {
  padding: 10px;
  border: 1px solid #555;
}
table th , table td {
  background-color: #f2f2f2;
}

a {
  display: block;
  text-align: center;
  margin: 10px;
  font-family:"Poppins";
  
}
a.button {
      display: inline-block;
      padding: 0px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    
    }
    a.buttom {
      display: inline-block;
      padding: 0px 20px;
      background-color: red;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    
    }

    .button1{
      margin-left:505px;
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s;
    
    
    }
    
  </style>
</head>
<body>
  <div class="container">
  <h2 style="margin-top:80px; margin-left:800px;"><u>View All Categories</u></h2>
  <?php if(isset($_GET['msg']) && $_GET['msg']== 1){ ?>
    <span style="margin-top:100px; margin-left:800px; color:red;">Invalid Request</span>

  <?php } ?>
  
  <a class = 'button1' href='categories.php'>Add Categories</a>
    <table border=1>
      <tr>
        <th>SN</th>
        <th>Categories Name</th>
        <th>Categories Status</th>
        <th>Action</th>
      </tr>
      <?php for($i=0;$i< count($categories);$i++) { ?>
        <tr>
          <td><?php echo $i+1 ?></td>
          <td><?php echo $categories[$i]['cat_name'] ?></td>
          <td><?php if($categories[$i]['cat_status'] == 1) 
          { 
            echo 'Active';
          } 
          else
          { 
            echo 'Deactive';
          } ?></td>
          <td>
            <a class='button' href="edit_categories.php?cat_id=<?php echo $categories[$i]['cat_id'] ?>">Update</a>
            <a class='buttom' href="delete_categories.php?cat_id=<?php echo $categories[$i]['cat_id'] ?>" onclick="return confirm('Are you sure?')">Delete </a>
          </td>
        </tr>
        <?php } ?>
    </table>
      </div>

</body>
</html>
