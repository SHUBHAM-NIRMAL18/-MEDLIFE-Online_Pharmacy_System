
<?php session_start(); ?>
<?php include_once ('header.php'); ?>




<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medlife</title>
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
    /* .banner {
      background-image: url("img/home.jpg");
      background-size: cover;
      background-position: center;
      height: 200px;
      position: relative;
      opacity: 80%;
    
      
    } */

    .quote {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-align: center;
      color: black;
      font-size: 25px;
      font-weight: bold;
      font-family:"Poppins";
    }

    .search-container {
      text-align: center;
      margin-top: 20px;
    }

    .search-input {
      padding: 5px 16px;
      font-size: 16px;
      border: 1px solid black;
      border-radius: 4px;
      width:30%;
      margin-top:30px;
      font-family:"poppins";
    }
    .searchbutton{
      display: inline-block;
      padding: 5px 16px;
      background-color: green;
      color: white;
      text-decoration: none;
      font-size: 16px;
      font-family:"poppins";
      border-radius: 4px;
      border: 1px solid black;

    }
    .image-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
    }

    .image {
      position: relative;
    }

    .image img {
      width: 150px;
      height: auto;
      background-color: transparent;
      border-radius:80%
    }

    .quote1 {
        
      position: absolute;
      top: 50%;
      left: 100%;
      transform: translateY(-50%);
      /* background-color: rgba(0, 0, 0, 0.7); */
      color: black;
      padding: 10px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      width: 100px;
      white-space: nowrap;
      font-family:"Poppins";
      /* border-radius:40% */
    }
    .image:nth-child(3) {
      margin-right: 100px;
    }
    .product-card {
        width:200px;
        height:auto;
      background-color: #f8f8f8;
      padding: 20px;
      margin-top:50px;
      
      text-align: left;
      margin-left:50px;
      display:inline-block;
    }

     h2 {
      font-size: 24px;
      font-weight:normal;
      margin-top: 10px;
      font-family:"poppins";
      color:green;
      margin-left:20px;
    }

    .product-card img {
      width: 150px;
      height: auto;
      display: block;
        margin-left: auto;
        margin-right: auto;
    }

    .product-card p {
      font-size: 18px;
      text-align:center;
      margin-bottom: 5px;
      font-family:"poppins";
    }

    .product-card .price {
      font-size: 18px;
      font-weight: bold;
      margin-bottom: 10px;
      font-family:"poppins";
      
    }

    .product-card .btn-buy-now {
      background-color: green;
      color: white;
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
      font-family:"poppins";
    }

    .product-card .btn-add-to-cart {
      background-color: yellow;
      color: black;
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-family:"poppins";
    }

    .centered-content {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 70vh;
    }

    .feedback-form {
      width: 350px;
      background-color: #f8f8f0;
      padding: 15px 40px 15px 40px;
    }

    .feedback-form h2 {
      font-size: 20px;
      margin-bottom: 10px;
      align-items:center;
    }

    .feedback-form label {
      display: block;
      margin-bottom: 10px;
      font-family:"poppins";
    }

    .feedback-form input[type="text"],
    .feedback-form textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      resize: vertical;
    }

    .feedback-form textarea {
      height: 100px;
    }

    .feedback-form button {
      background-color: green;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .slider {
      width: 800px;
      margin: 0 auto;
      overflow: hidden;
      position: relative;
    }

    .slider-container {
      display: flex;
      transition: transform 0.3s ease-in-out;
    }

    .review-card {
      width: 900px;
       height:250px; 
      background-color: #b4f0de;
      padding: 20px;
      margin-right: 20px;
      padding:60px;
      border-radius:4%;
    }

    .review-card img {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin-bottom: 10px;
    }

    .slider-controls {
      display: flex;
      justify-content: space-between;
      align-items:center;
      margin-top: 10px;
    }

    .slider-controls button {
      background-color: #b4f0de;
      color: white;
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    .footer {
      background-color: black;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      margin-top:200px;
    }

    .footer .left-section {
      flex: 1;
    }

    .footer .middle-section {
      flex: 1;
      text-align: center;
    }

    .footer .right-section {
      flex: 1;
      text-align: right;
    }

    .footer iframe {
      width: 80%;
      height: 200px;
    }

    .footer form {
        color:white;
        font-family:"Poppins";
      max-width: 300px;
      margin-bottom: 20px;
    }

    .footer input[type="text"],
    .footer textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 10px;
    }

    .footer input[type="submit"] {
      background-color: green;
      color: white;
      border: none;
      padding: 10px 20px;
      cursor: pointer;
    }
    .right-section h3{
        font-family:"poppins";
        color:white;
        text-align:center;

    }
    .middle-section h3, .middle-section p{
        font-family:"poppins";
        color:white;

    }
    .left-section h3{
        font-family:"poppins";
        color:white;

    }
    
  
    
  </style>
</head>
<body>
    


  


  <div class="banner">
    
    <div class="search-container">
      <form action="search_products.php" method="get">
      <input class="search-input" type="text" placeholder="Find Products Here" name="search">
      <input type="submit" value='Search' name="btnSearchProduct" class="searchbutton">
      </form>
    </div>
  </div>
  <?php
  if(isset($_GET['btnSearchProduct'])){
    $search = $_GET['search'];
    $conn = new mysqli('localhost','root','','medlife');
    if($conn->connect_error){
        die("Connection failed:".$conn->connect_error);
        }
    $sql = "select * from tbl_products where prdct_name like '%$search%' ";
    $products = $conn->query($sql);
      }
      
?>

            <h2><u>Search Products</u></h2>
            <?php while($row = mysqli_fetch_assoc($products)){
            ?>
            <div class="product-card">
                <img src="<?php echo "medimg/".$row['prdct_img'];?>" style="width:100px ; height:80px" alt="Product Image">
                <p><?php echo $row['prdct_name'];?></p>
                <p class="price"><?php echo "Rs".$row['prdct_price'];?></p>
                <button class="btn-buy-now"><a href='single.php?id=<?php echo $row['prdct_id']; ?>' style="color:white ; text-decoration:none">Details</a></button>
                <button class="btn-add-to-cart"><a href='addToCart.php?id=<?php echo $row['prdct_id']; ?>' style="color:black ; text-decoration:none">Add to Cart</a></button>
            </div>
            <?php } ?>


            
      


        <!-- <div class="centered-content">
            <div class="feedback-form">
            <h2>Feedback Form</h2>
            <form>
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter your name">

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" placeholder="Enter your email">

                <label for="message">Message:</label>
                <textarea id="message" name="message" placeholder="Enter your feedback"></textarea>

                <button type="submit">Submit</button>
            </form>
            </div>
        </div> -->
        <?php
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                require 'vendor/autoload.php';
                $errors = [];
                $errorMessage = '';
                $successMessage = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $name = sanitizeInput($_POST['name']);
                    $email = sanitizeInput($_POST['email']);
                    $message = sanitizeInput($_POST['message']);
                if (empty($name)) {
                    $errors[] = 'Name is empty';
                }
                if (empty($email)) {
                    $errors[] = 'Email is empty';
                }  else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Email is invalid';
                }
                if (empty($message)) {
                    $errors[] = 'Message is empty';
                }

                if (!empty($errors)) {
                    $allErrors = join('<br/>', $errors);
                    $errorMessage = "<p style='color: red;'>{$allErrors}</p>";
                } else {
                    $toEmail = $email;
                    $emailSubject = 'New email from your contaсt form';

                    // Create a new PHPMailer instance
                        $mail = new PHPMailer(true);
                        try {
                            // Configure the PHPMailer instance
                            $mail->isSMTP();
                            $mail->Host = 'sandbox.smtp.mailtrap.io';
                            $mail->SMTPAuth = true;
                            $mail->Username = 'bddc191603848d';
                            $mail->Password = 'e4ee0e941c3351';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            // Set the sender, recipient, subject, and body of the message
                            $mail->setFrom($email);
                            $mail->addAddress($toEmail);
                            $mail->Subject = $emailSubject;
                            $mail->isHTML(true);
                            $mail->Body = "<p>Name: {$name}</p><p>Email: {$email}</p><p>Message: {$message}</p>";

                            // Send the message
                            $mail->send();

                            $successMessage = "<p style='color: green;'>Thank you for contacting us :)</p>";
                        } catch (Exception $e) {
                    $errorMessage = "<p style='color: red;'>Oops, something went wrong. Please try again later</p>";
                    }
                }
                }
            function sanitizeInput($input) {
            $input = trim($input);
            $input = stripslashes($input);
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            return $input;
            }
        ?>
        <footer class="footer">
            <div class="left-section">
            <h3 >Feedback Form</h3>
            <form action=""method="post" id="contact-form">
            <?php echo((!empty($errorMessage)) ? $errorMessage : '') ?>
            <?php echo((!empty($successMessage)) ? $successMessage : '') ?>
                <input type="text" name="name" placeholder="Your Name">
                <input type="text" name="email" placeholder="Your Email">
                <textarea name="message" placeholder="Your Message"></textarea>
                <input type="submit" value="Submit">
            </form>
            </div>
            <div class="middle-section">
            <h3>Information</h3>
            <p style="color:white;text-align:center;font-family='poppins',margin-right:8px">Our team of licensed pharmacists and <br> healthcare professionals
                are dedicated to providing <br> you with the highest quality of
                service and care. <br> Whether you have questions about your
                medication <br>or need help finding the right product for <br>your
                healthcare needs, we're here to help</p>
            </div>
            <div class="right-section">
            <h3>Location</h3>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4024.5793175587314!2d85.32190731783429!3d27.676611715094968!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39eb19c9a5cf34fb%3A0x9184a86a77dac5e5!2sPatan%20Multiple%20Campus!5e0!3m2!1sen!2snp!4v1687273958510!5m2!1sen!2snp"
                frameborder="0" style="border:0" allowfullscreen></iframe>
            </div>
        </footer>
        
  </div>
  <script>
    var sliderContainer = document.querySelector('.slider-container');
    var prevButton = document.getElementById('prev-btn');
    var nextButton = document.getElementById('next-btn');
    var reviewCards = document.querySelectorAll('.review-card');
    var cardWidth = reviewCards[0].offsetWidth;
    var currentIndex = 0;

    prevButton.addEventListener('click', function() {
      if (currentIndex > 0) {
        currentIndex--;
        updateSliderPosition();
      }
    });

    nextButton.addEventListener('click', function() {
      if (currentIndex < reviewCards.length - 3) {
        currentIndex++;
        updateSliderPosition();
      }
    });

    function updateSliderPosition() {
      var newPosition = -currentIndex * cardWidth;
      sliderContainer.style.transform = 'translateX(' + newPosition + 'px)';
    }
  </script>

      
</body>
</html>
