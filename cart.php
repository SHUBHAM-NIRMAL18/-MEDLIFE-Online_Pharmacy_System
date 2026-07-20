<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$page_title = "Shopping Cart";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    <?php if (empty($cart)): ?>
        <!-- Empty Cart State -->
        <div class="empty-cart-card">
            <div class="empty-cart-icon">
                <i class="bx bx-shopping-bag"></i>
            </div>
            <h3>Your Shopping Cart is Empty</h3>
            <p>Looks like you haven't added any products to your cart yet. Let's find some health products for you!</p>
            <a href="index.php" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <h2 class="section-title">Shopping Cart</h2>
        
        <div class="cart-flex-container">
            <!-- Left Side: Staged Cart Items Table -->
            <div class="cart-items-section">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total = 0;
                        $conn = get_db_connection();
                        
                        foreach($cart as $key => $value):
                            $key_clean = (int)$key;
                            $sql = "SELECT * FROM tbl_products WHERE prdct_id = $key_clean";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0):
                                $row = $result->fetch_assoc();
                                $subtotal = (int)$row['prdct_price'] * (int)$value['quantity'];
                                $total += $subtotal;
                        ?>
                                <tr>
                                    <td>
                                        <div class="cart-product-info">
                                            <img src="medimg/<?php echo htmlspecialchars($row['prdct_img'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <a href="single.php?id=<?php echo $row['prdct_id']; ?>" class="cart-product-name"><?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></a>
                                        </div>
                                    </td>
                                    <td>Rs. <?php echo number_format($row['prdct_price'], 2); ?></td>
                                    <td>
                                        <span class="cart-qty-badge"><?php echo htmlspecialchars($value['quantity']); ?></span>
                                    </td>
                                    <td class="cart-price-sub">Rs. <?php echo number_format($subtotal, 2); ?></td>
                                    <td>
                                        <a href="delete_cart.php?id=<?php echo $key; ?>" class="cart-remove-btn" title="Remove Item">
                                            <i class="bx bx-trash"></i> Remove
                                        </a>
                                    </td>
                                </tr>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Right Side: Sticky Checkout Summary Panel -->
            <div class="cart-summary-section">
                <h3>Cart Totals</h3>
                
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rs. <?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="color: var(--success); font-weight: 500;">Free Shipping</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span>Rs. <?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="summary-actions">
                    <a href="checkout.php" class="btn btn-primary"><i class="bx bx-credit-card"></i> Proceed to Checkout</a>
                    <a href="index.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include('footer.php'); ?>