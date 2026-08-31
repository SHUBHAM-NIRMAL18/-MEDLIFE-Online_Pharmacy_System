<?php 
require_once 'config.php';
require_once 'includes/PaymentGateways.php';

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
$payment = 'esewa';
$terms = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Prescription file upload - OPTIONAL
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

    if (isset($_POST['payment']) && in_array($_POST['payment'], ['cod', 'esewa', 'khalti'])) {
        $payment = $_POST['payment'];
    } else {
        $err['payment'] = 'Please select a valid payment method';
    }

    if (isset($_POST['terms']) && !empty($_POST['terms'])) {
        $terms = $_POST['terms'];
    } else {
        $err['terms'] = 'You must accept the terms and conditions';
    }

    if (count($err) == 0) {
        $tracking_order = "medlife" . rand(1000, 9999);
        $current_pharmacy_id = isset($_SESSION['cart_pharmacy_id']) ? (int)$_SESSION['cart_pharmacy_id'] : get_current_pharmacy_id();
        $pharmacy_details = get_pharmacy_details($current_pharmacy_id);
        $delivery_fee = isset($pharmacy_details['delivery_fee']) ? (float)$pharmacy_details['delivery_fee'] : 100.00;
        
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
        $grand_total = $subtotal + $delivery_fee;

        $conn = get_db_connection();
        $initial_payment_status = 'Pending';
        $stmt_order = $conn->prepare("INSERT INTO tbl_order (tracking_order, user_id, user_name, phone, address, payment, payment_status, prescription, total, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt_order) {
            $stmt_order->bind_param("sissssssdi", $tracking_order, $id, $fullname, $phone_val, $address_val, $payment, $initial_payment_status, $uploadPath, $grand_total, $current_pharmacy_id);
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
                            $stmt_item->bind_param("iisid", $order_id, $key_clean, $prdct_name, $qty, $prdct_price);
                            $stmt_item->execute();
                            $stmt_item->close();
                        }

                        // Automatically deduct stock inventory
                        $stmt_stock = $conn->prepare("UPDATE tbl_products SET stock_quantity = GREATEST(0, stock_quantity - ?) WHERE prdct_id = ?");
                        if ($stmt_stock) {
                            $stmt_stock->bind_param("ii", $qty, $key_clean);
                            $stmt_stock->execute();
                            $stmt_stock->close();
                        }
                    }
                }

                // Route based on selected payment method
                if ($payment === 'esewa') {
                    // Redirect to eSewa payment processor
                    header("Location: esewa_process.php?order_id=" . $order_id);
                    exit();

                } elseif ($payment === 'khalti') {
                    // Initiate Khalti ePayment v2 request
                    $cust_info = [
                        'name' => $fullname,
                        'email' => $_SESSION['email'] ?? 'customer@medlife.com',
                        'phone' => $phone_val
                    ];

                    $khalti_init = PaymentGateways::initiateKhaltiPayment($order_id, $tracking_order, $grand_total, $cust_info);

                    if ($khalti_init['success'] && !empty($khalti_init['payment_url'])) {
                        // Store pidx in transaction_id for lookup verification
                        $up_k = $conn->prepare("UPDATE tbl_order SET transaction_id = ? WHERE order_id = ?");
                        if ($up_k) {
                            $up_k->bind_param("si", $khalti_init['pidx'], $order_id);
                            $up_k->execute();
                            $up_k->close();
                        }

                        header("Location: " . $khalti_init['payment_url']);
                        exit();
                    } else {
                        $err['payment'] = 'Khalti gateway error: ' . ($khalti_init['message'] ?? 'Could not initiate payment session.');
                    }

                } else {
                    // Cash on Delivery (COD)
                    unset($_SESSION['cart'], $_SESSION['cart_pharmacy_id']);
                    $_SESSION['toast'] = [
                        'type' => 'success',
                        'title' => 'Order Placed (Cash on Delivery)',
                        'message' => 'Your pharmacy order has been placed successfully with ' . $pharmacy_details['name'] . '! Tracking: ' . $tracking_order
                    ];
                    
                    header("Location: order_placed.php?order_id=" . $order_id);
                    exit();
                }
            } else {
                $err['error'] = 'Could not place order. Please try again.';
            }
            $stmt_order->close();
        }
    }
}

$page_title = "Checkout - MedLife";
$page_css = "css/checkout.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 20px 20px;">
    
    <h2 class="section-title" style="margin-top: 10px; margin-bottom: 10px;">Checkout</h2>
    
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>" method="POST" enctype="multipart/form-data" novalidate id="checkoutMainForm">
        
        <div class="cart-flex-container">
            
            <!-- Left Side: Billing Details Form -->
            <div class="checkout-form-section">
                <h2>Billing & Delivery Details</h2>
                
                <?php if (isset($err['error'])): ?>
                    <div class="alert alert-error"><?php echo $err['error']; ?></div>
                <?php endif; ?>

                <?php if (isset($err['payment'])): ?>
                    <div class="alert alert-error"><?php echo $err['payment']; ?></div>
                <?php endif; ?>

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

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; margin-top: 20px;">
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                        <i class="bx bx-check-shield" style="color: var(--primary); font-size: 18px;"></i> Safe & Trusted Delivery
                    </div>
                    <p style="font-size: 12px; color: #64748b; margin: 0; line-height: 1.5;">
                        Your prescriptions and medical orders are verified and packed in tamper-proof sterile packaging by registered pharmacists.
                    </p>
                </div>
            </div>

            <!-- Right Side: Order Summary & Payment Selection Panel -->
            <div class="cart-summary-section" style="position: sticky; top: 90px;">
                <h3>Order Summary</h3>
                
                <div style="max-height: 220px; overflow-y: auto; margin-bottom: 16px; padding-right: 4px;">
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
                                <span class="p-price">रु. <?php echo number_format($item_sub, 2); ?></span>
                            </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>

                <div class="summary-row" style="border-top: 1px solid var(--border-color); padding-top: 14px;">
                    <span>Cart Subtotal</span>
                    <span>रु. <?php echo number_format($subtotal, 2); ?></span>
                </div>

                <div class="summary-row">
                    <span>Shipping Fee</span>
                    <span>रु. 100.00</span>
                </div>

                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span>रु. <?php echo number_format($subtotal + 100, 2); ?></span>
                </div>

                <!-- Payment Method Section Placed JUST BELOW Order Summary -->
                <div class="summary-payment-section" style="margin-top: 22px; padding-top: 18px; border-top: 1.5px solid #e2e8f0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                        <label style="font-size: 14px; font-weight: 700; color: #0f172a; margin: 0;">Payment Method</label>
                        <span style="font-size: 11px; color: var(--primary); font-weight: 700; background: rgba(5, 150, 105, 0.08); padding: 2px 8px; border-radius: 10px;">
                            <i class="bx bx-lock-alt"></i> 100% Encrypted
                        </span>
                    </div>
                    
                    <div class="payment-cards-grid">
                        
                        <!-- eSewa Wallet Card with Downloaded Official Logo -->
                        <label class="payment-method-card esewa-card <?php echo $payment === 'esewa' ? 'selected' : ''; ?>" for="pay_esewa">
                            <input type="radio" id="pay_esewa" name="payment" value="esewa" <?php echo $payment === 'esewa' ? 'checked' : ''; ?>>
                            <div class="pay-card-content">
                                <div class="pay-card-left">
                                    <div class="pay-logo-badge esewa-badge-img">
                                        <img src="img/esewa_logo.png" alt="eSewa Official Logo" class="gateway-brand-img">
                                    </div>
                                    <div class="pay-details">
                                        <div class="pay-title">eSewa Mobile Wallet</div>
                                        <div class="pay-desc">Pay via eSewa Account or QR</div>
                                    </div>
                                </div>
                                <div class="pay-tag green-tag">Instant ePay</div>
                            </div>
                        </label>

                        <!-- Khalti Wallet Card with Downloaded Official Logo -->
                        <label class="payment-method-card khalti-card <?php echo $payment === 'khalti' ? 'selected' : ''; ?>" for="pay_khalti">
                            <input type="radio" id="pay_khalti" name="payment" value="khalti" <?php echo $payment === 'khalti' ? 'checked' : ''; ?>>
                            <div class="pay-card-content">
                                <div class="pay-card-left">
                                    <div class="pay-logo-badge khalti-badge-img">
                                        <img src="img/khalti_logo.png" alt="Khalti Official Logo" class="gateway-brand-img">
                                    </div>
                                    <div class="pay-details">
                                        <div class="pay-title">Khalti Digital Wallet</div>
                                        <div class="pay-desc">Khalti Wallet, Mobile Banking & SCT</div>
                                    </div>
                                </div>
                                <div class="pay-tag purple-tag">Khalti Pay</div>
                            </div>
                        </label>

                        <!-- Cash on Delivery (COD) Card with Official Logo -->
                        <label class="payment-method-card cod-card <?php echo $payment === 'cod' ? 'selected' : ''; ?>" for="pay_cod">
                            <input type="radio" id="pay_cod" name="payment" value="cod" <?php echo $payment === 'cod' ? 'checked' : ''; ?>>
                            <div class="pay-card-content">
                                <div class="pay-card-left">
                                    <div class="pay-logo-badge cod-badge-img">
                                        <img src="img/cod_logo.png" alt="Cash on Delivery" class="gateway-brand-img">
                                    </div>
                                    <div class="pay-details">
                                        <div class="pay-title">Cash on Delivery (COD)</div>
                                        <div class="pay-desc">Pay cash when medicine arrives</div>
                                    </div>
                                </div>
                                <div class="pay-tag neutral-tag">Doorstep</div>
                            </div>
                        </label>

                    </div>

                    <?php if (isset($err['payment'])): ?>
                        <span class="error-text" style="margin-top: 8px;"><i class="bx bx-error-circle"></i> <?php echo $err['payment']; ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group" style="margin-top: 18px;">
                    <label class="checkbox-option" style="font-size: 12px; line-height: 1.4; color: var(--text-main);">
                        <input type="checkbox" name="terms" value="true" <?php echo !empty($terms) ? 'checked' : ''; ?>>
                        <span>I accept terms and confirm ordered medicines are for personal health care.</span>
                    </label>
                    <?php if (isset($err['terms'])): ?>
                        <span class="error-text"><i class="bx bx-error-circle"></i> <?php echo $err['terms']; ?></span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="btnOrder" id="btnSubmitOrder" class="btn btn-primary" style="width: 100%; height: 46px; font-weight: 700; font-size: 15px; margin-top: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);">
                    <i class="bx bx-lock-alt" style="font-size: 18px;"></i> Place Order & Pay
                </button>
                
                <div style="font-size: 11.5px; color: var(--text-light); text-align: center; margin-top: 16px; display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <i class="bx bx-shield-quarter" style="color: var(--primary); font-size: 15px;"></i> 100% Authentic Pharmacy Guarantee
                </div>
            </div>

        </div>
        
    </form>
    
</main>

<!-- Rotating Capsule Order Processing Modal Overlay -->
<div class="order-processing-modal" id="orderProcessingModal">
    <div class="order-processing-card">
        <div class="capsule-loader-wrapper">
            <div class="capsule-pulse-ring"></div>
            <div class="capsule-pill-spinner">
                <div class="capsule-half-left"></div>
                <div class="capsule-half-right"></div>
            </div>
        </div>

        <h3 class="order-processing-title" id="processingTitleText">Processing Your Order...</h3>
        <p class="order-processing-desc" id="processingStatusText">Reserving pharmacy stock & verifying delivery details.</p>

        <div class="order-progress-track">
            <div class="order-progress-bar" id="orderProgressBar"></div>
        </div>

        <div class="order-step-status">
            <i class="bx bx-sync bx-spin"></i> Please do not close or refresh this page...
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Highlight selected payment card
    var paymentRadios = document.querySelectorAll('input[name="payment"]');
    paymentRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-method-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            var parentCard = this.closest('.payment-method-card');
            if (parentCard) parentCard.classList.add('selected');
        });
    });

    var checkoutForm = document.getElementById('checkoutMainForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            // Check HTML5 Form Validation
            if (!checkoutForm.checkValidity()) return;

            e.preventDefault();

            var selectedPayment = document.querySelector('input[name="payment"]:checked');
            var payVal = selectedPayment ? selectedPayment.value : 'esewa';

            var modal = document.getElementById('orderProcessingModal');
            var progressBar = document.getElementById('orderProgressBar');
            var titleText = document.getElementById('processingTitleText');
            var statusText = document.getElementById('processingStatusText');

            if (modal) modal.classList.add('active');

            if (payVal === 'esewa') {
                if (titleText) titleText.textContent = "Connecting to eSewa...";
                if (statusText) statusText.textContent = "Generating secure signature & initiating eSewa wallet...";
            } else if (payVal === 'khalti') {
                if (titleText) titleText.textContent = "Connecting to Khalti...";
                if (statusText) statusText.textContent = "Creating payment session & contacting Khalti Gateway...";
            } else {
                if (titleText) titleText.textContent = "Processing COD Order...";
                if (statusText) statusText.textContent = "Reserving pharmacy stock & verifying address...";
            }

            var progress = 0;
            var interval = setInterval(function() {
                progress += 5;
                if (progressBar) progressBar.style.width = progress + '%';

                if (progress >= 100) {
                    clearInterval(interval);
                    setTimeout(function() {
                        if (!checkoutForm.querySelector('input[name="btnOrder"]')) {
                            var hiddenSubmit = document.createElement('input');
                            hiddenSubmit.type = 'hidden';
                            hiddenSubmit.name = 'btnOrder';
                            hiddenSubmit.value = '1';
                            checkoutForm.appendChild(hiddenSubmit);
                        }
                        checkoutForm.submit();
                    }, 100);
                }
            }, 40); // ~0.8 second smooth transition
        });
    }
});
</script>

<?php include('footer.php'); ?>
