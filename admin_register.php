<?php
$servername = "localhost";  // Replace with your database server name
$username = "username";    // Replace with your database username
$password = "password";    // Replace with your database password
$dbname = "medlife";       // Replace with your database name

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the form data
    $email = $_POST["email"];
    $password = md5($_POST["password"]);
    $status = $_POST["status"];
    $name = $_POST["name"];

    // Prepare the SQL statement
    $sql = "INSERT INTO tbl_admin (email, password, status, name) VALUES ('$email', '$password', '$status', '$name')";

    // Execute the SQL statement
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

// Close the database connection
$conn->close();
?>
<h2>Admin Register</h2>
<form action="" method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required>
    <br>
    <label for="status">Status:</label>
    <input type="text" id="status" name="status" required>
    <br>
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required>
    <br>
    <input type="submit" value="Submit">
</form>

