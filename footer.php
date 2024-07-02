<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
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
<?php error_reporting(0); ?> 
<?php
                use PHPMailer\PHPMailer\PHPMailer;
                use PHPMailer\PHPMailer\Exception;
                require 'vendor/autoload.php';
                $errors = [];
                $errorMessage = '';
                $successMessage = '';
                $message= $name = $email = [];
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
                    $toEmail = 'medlife@gmail.com';
                    $emailSubject = 'New email from your Feedback Form';

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
</body>
</html>

