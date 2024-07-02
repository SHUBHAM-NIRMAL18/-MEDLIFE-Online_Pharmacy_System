<?php
include ('dashboard.php');

// Create a connection
$conn = new mysqli('localhost', 'root', '', 'medlife');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Pagination settings
$resultsPerPage = 5; // Number of results to display per page
$currentpage = isset($_GET['page']) ? $_GET['page'] : 1; // Get the current page number
$startFrom = ($currentpage - 1) * $resultsPerPage; // Calculate the starting point for the results

// SQL query to fetch paginated data from the tbl_user table
$sql = "SELECT * FROM tbl_user LIMIT $startFrom, $resultsPerPage";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr>";
    echo "<th>S.N</th>";
    echo "<th>User ID</th>";
    echo "<th>Name</th>";
    echo "<th>Email</th>";
    echo "<th>Phone</th>";
    echo "<th>Address</th>";
    echo "<th>Gender</th>";
    echo "<th>Action</th>";
    echo "</tr>";

    // Output data of each row
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . 1 . "</td>";
        echo "<td>" . $row["user_id"] . "</td>";
        echo "<td>" . $row["name"] . "</td>";
        echo "<td>" . $row["email"] . "</td>";
        echo "<td>" . $row["phone"] . "</td>";
        echo "<td>" . $row["address"] . "</td>";
        echo "<td>" . $row["gender"] . "</td>";
        echo "<td>";
        echo "<a class='buttom' href='update_user.php?user_id=" . $row["user_id"] . "'>Update</a>  ";
        echo "<a class='button' href='delete_user.php?user_id=" . $row["user_id"] . "'>Delete</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Pagination links
    $sql = "SELECT COUNT(*) AS total FROM tbl_user";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $totalPages = ceil($row['total'] / $resultsPerPage);

    echo "<div class='pagination'>";
    if ($currentpage > 1) {
        echo "<a href='?page=" . ($currentpage - 1) . "'>&laquo; Previous</a>";
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentpage) {
            echo "<a class='active' href='?page=" . $i . "'>" . $i . "</a>";
        } else {
            echo "<a href='?page=" . $i . "'>" . $i . "</a>";
        }
    }

    if ($currentpage < $totalPages) {
        echo "<a href='?page=" . ($currentpage + 1) . "'>Next &raquo;</a>";
    }

    echo "</div>";
} else {
    echo "No users found in the database.";
}

// Close the connection
//
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users</title>
    <style>
       a.buttom {
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
    a.button {
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

        table {
        width: 80%;
        border-collapse: collapse;
        margin-bottom: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        margin-top: 220px;
        margin-left:300px;

}

    table th,
    table td {
      padding: 8px;
      border: 1px solid #ddd;
      text-align: left;
    }

/* Styling for the table header */
table th {
  background-color: #f2f2f2;
}

/* Styling for the pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-top: 20px;
}

.pagination a {
  color: #000;
  padding: 8px 12px;
  border: 1px solid #ddd;
  margin: 0 4px;
  text-decoration: none;
  transition: background-color 0.3s;
}

.pagination a:hover {
  background-color: #f2f2f2;
}

.pagination .active {
  background-color: #ddd;
}

.pagination .disabled {
  opacity: 0.6;
  pointer-events: none;
}
    </style>
</head>
<body>
    
</body>
</html>