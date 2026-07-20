<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['email'])) {
    header("Location: customer_login.php");
    exit();
}

$id = (int)$_SESSION['user_id'];
$connection = get_db_connection();

// Fetch customer default information
$name = $address = $phone = '';
$user_stmt = $connection->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
if ($user_stmt) {
    $user_stmt->bind_param("i", $id);
    $user_stmt->execute();
    $result = $user_stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $name = $row["name"];
        $address = $row["address"];
        $phone = $row["phone"];
    }
    $user_stmt->close();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header("Location: cart.php");
    exit();
}

$err = [];
$fullname = $name;
$phone_val = $phone;
$address_val = $address;
$payment = '';
$terms = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnOrder'])) {
    if (isset($_POST['fullname']) && !empty($_POST['fullname']) && trim($_POST['fullname'])) {
        $fullname = trim($_POST['fullname']);
    } else {
        $err['fullname'] = 'Full name is required';
    }

    if (isset($_POST['phone']) && !empty($_POST['phone']) && trim($_POST['phone'])) {
        $phone_val = trim($_POST['phone']);
    } else {
        $err['phone'] = 'Phone number is required';
    }

    if (isset($_POST['address']) && !empty($_POST['address']) && trim($_POST['address'])) {
        $address_val = trim($_POST['address']);
    } else {
        $err['address'] = 'Delivery address is required';
    }

    // Prescription file upload - OPTIONAL as requested by the user
    $uploadPath = '';
    if (isset($_FILES['prescription']['name']) && !empty($_FILES['prescription']['name'])) {
        $prescriptionName = basename($_FILES["prescription"]["name"]);
        $prescriptionTempName = $_FILES["prescription"]["tmp_name"];
        
        // Ensure prescription directory exists
        if (!is_dir("prescriptions")) {
            mkdir("prescriptions", 0777, true);
        }
        
        $uploadPath = "prescriptions/" . time() . "_" . $prescriptionName;
        move_uploaded_file($prescriptionTempName, $uploadPath);
    }

    if (isset($_POST['payment']) && !empty($_POST['payment']) && trim($_POST['payment'])) {
        $payment = $_POST['payment'];
    } else {
        $err['payment'] = 'Please select a payment mode';
    }

    if (isset($_POST['terms']) && !empty($_POST['terms'])) {
        $terms = $_POST['terms'];
    } else {
        $err['terms'] = 'You must accept the terms and conditions';
    }

    if (count($err) == 0) {
        $tracking_order = "medlife" . rand(1000, 9999);
        
        // Sum total price of cart
        $subtotal = 0;
        foreach ($cart as $key => $value) {
            $key_clean = (int)$key;
            $sql_cart = "SELECT prdct_price FROM tbl_products WHERE prdct_id = $key_clean";
            $res_cart = $connection->query($sql_cart);
            if ($res_cart && $res_cart->num_rows > 0) {
                $row_cart = $res_cart->fetch_assoc();
                $subtotal += ($row_cart['prdct_price'] * $value['quantity']);
            }
        }
        $grand_total = $subtotal + 100; // Adding flat Rs. 100 delivery fee

        $conn = get_db_connection();
        $stmt_order = $conn->prepare("INSERT INTO tbl_order (tracking_order, user_id, user_name, phone, address, payment, prescription, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_order) {
            $stmt_order->bind_param("sisssssd", $tracking_order, $id, $fullname, $phone_val, $address_val, $payment, $uploadPath, $grand_total);
            if ($stmt_order->execute()) {
                $order_id = $conn->insert_id;

                // Add order items details
                foreach ($cart as $key => $value) {
                    $key_clean = (int)$key;
                    $sql_prod = "SELECT prdct_id, prdct_name, prdct_price FROM tbl_products WHERE prdct_id = $key_clean";
                    $res_prod = $conn->query($sql_prod);
                    if ($res_prod && $res_prod->num_rows > 0) {
                        $row_prod = $res_prod->fetch_assoc();
                        $prdct_name = $row_prod['prdct_name'];
                        $prdct_price = $row_prod['prdct_price'];
                        $qty = (int)$value['quantity'];

                        $stmt_item = $conn->prepare("INSERT INTO tbl_orderitems (order_id, prdct_id, prdct_name, quantity, price) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt_item) {
                            $stmt_item->bind_param("iiisd", $order_id, $key_clean, $prdct_name, $qty, $prdct_price);
                            $stmt_item->execute();
                            $stmt_item->close();
                        }
                    }
                }

                // Clear Shopping Cart and set toast notification
                unset($_SESSION['cart']);
                $_SESSION['toast'] = [
                    'type' => 'success',
                    'title' => 'Order Placed',
                    'message' => 'Your pharmacy order has been placed successfully! Tracking: ' . $tracking_order
                ];
                
                header("Location: order_placed.php");
                exit();
            } else {
                $err['error'] = 'Could not place order. Please try again.';
            }
            $stmt_order->close();
        }
    }
}

$page_title = "Checkout";
$page_css = "css/checkout.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <h2 class="section-title">Checkout</h2>
    
    <div class="cart-flex-container">
        
        <!-- Left Side: Billing Details Form -->
        <div class="checkout-form-section">
            <h2>Billing & Delivery Details</h2>
            
            <?php if (isset($err['error'])): ?>
                <div class="alert alert-error"><?php echo $err['error']; ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data" novalidate>
                
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Enter Full Name" required value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (isset($err['fullname'])): ?>
                        <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['fullname']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="98XXXXXXXX" required value="<?php echo htmlspecialchars($phone_val, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (isset($err['phone'])): ?>
                        <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['phone']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="address">Delivery Address</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Street Name, Area, City" required value="<?php echo htmlspecialchars($address_val, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if (isset($err['address'])): ?>
                        <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['address']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="prescription">Medical Prescription <span style="color: var(--text-light); font-weight: normal; font-size: 12px;">(Optional)</span></label>
                    <input type="file" id="prescription" name="prescription" class="form-control" accept=".pdf, .jpg, .jpeg, .png, .doc, .docx" style="padding: 8px 12px;">
                    <span style="font-size: 11.5px; color: var(--text-light); margin-top: 4px; display: block;">Accepts PDF, Word docs, and Images. If your medicines require a prescription, please upload it.</span>
                </div>

                <div class="form-group">
                    <label>Select Payment Method</label>
                    <div class="radio-group">
                        <label class="radio-option">
                            <input type="radio" name="payment" value="cod" <?php echo $payment === 'cod' ? 'checked' : ''; ?>>
                            <span>Cash on Delivery (COD)</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="payment" value="online" <?php echo $payment === 'online' ? 'checked' : ''; ?>>
                            <span>Online Payment / Wallet</span>
                        </label>
                    </div>
                    <?php if (isset($err['payment'])): ?>
                        <span class="error-text" style="margin-top: 8px;"><i class="bx bx-error-circle"></i> <?php echo $err['payment']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="checkbox-option">
                        <input type="checkbox" name="terms" value="true" <?php echo !empty($terms) ? 'checked' : ''; ?>>
                        <span>I accept the terms and conditions, and verify that the items ordered are for personal healthcare.</span>
                    </label>
                    <?php if (isset($err['terms'])): ?>
                        <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['terms']; ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="btnOrder" class="btn btn-primary" style="width: 100%; height: 44px; font-weight: 600; font-size: 15px; margin-top: 15px;">
                    <i class="bx bx-check-circle" style="font-size: 18px;"></i> Complete Order
                </button>
                
            </form>
        </div>

        <!-- Right Side: Order Summary Panel -->
        <div class="cart-summary-section" style="position: sticky; top: 90px;">
            <h3>Order Summary</h3>
            
            <div style="max-height: 240px; overflow-y: auto; margin-bottom: 20px; padding-right: 4px;">
                <?php 
                $subtotal = 0;
                foreach ($cart as $key => $value):
                    $key_clean = (int)$key;
                    $sql_prod = "SELECT prdct_name, prdct_price FROM tbl_products WHERE prdct_id = $key_clean";
                    $res_prod = $connection->query($sql_prod);
                    if ($res_prod && $res_prod->num_rows > 0):
                        $row_prod = $res_prod->fetch_assoc();
                        $item_sub = $row_prod['prdct_price'] * $value['quantity'];
                        $subtotal += $item_sub;
                ?>
                        <div class="checkout-summary-item">
                            <span class="p-name" title="<?php echo htmlspecialchars($row_prod['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($row_prod['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="p-qty">x<?php echo htmlspecialchars($value['quantity']); ?></span>
                            <span class="p-price">Rs. <?php echo number_format($item_sub, 2); ?></span>
                        </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>

            <div class="summary-row" style="border-top: 1px solid var(--border-color); padding-top: 16px;">
                <span>Cart Subtotal</span>
                <span>Rs. <?php echo number_format($subtotal, 2); ?></span>
            </div>

            <div class="summary-row">
                <span>Shipping Fee</span>
                <span>Rs. 100.00</span>
            </div>

            <div class="summary-row total">
                <span>Grand Total</span>
                <span>Rs. <?php echo number_format($subtotal + 100, 2); ?></span>
            </div>
            
            <div style="font-size: 11.5px; color: var(--text-light); text-align: center; margin-top: 20px;">
                <i class="bx bx-lock-alt"></i> Secure checkout powered by Medlife
            </div>
        </div>

    </div>
    
</main>

<?php include('footer.php'); ?>
