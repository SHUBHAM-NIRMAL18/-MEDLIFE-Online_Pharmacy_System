<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$page_title = "Shopping Cart";
$page_css = "css/cart.css";
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
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <h2 class="section-title" style="margin: 0;">Shopping Cart</h2>
            <a href="clear_cart.php" id="btnClearCart" class="btn btn-outline" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.3); padding: 8px 16px; font-size: 13px; border-radius: 8px;">
                <i class="bx bx-trash"></i> Clear All Cart
            </a>
        </div>
        
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
                                    <td>रु. <?php echo number_format($row['prdct_price'], 2); ?></td>
                                    <td>
                                        <span class="cart-qty-badge"><?php echo htmlspecialchars($value['quantity']); ?></span>
                                    </td>
                                    <td class="cart-price-sub">रु. <?php echo number_format($subtotal, 2); ?></td>
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
                    <span>रु. <?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="color: var(--success); font-weight: 500;">Free Shipping</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span>रु. <?php echo number_format($total, 2); ?></span>
                </div>
                
                <div class="summary-actions">
                    <a href="checkout.php" class="btn btn-primary"><i class="bx bx-credit-card"></i> Proceed to Checkout</a>
                    <a href="index.php" class="btn btn-outline">Continue Shopping</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<!-- Custom Confirmation Modal Overlay -->
<div class="modal-overlay" id="confirmDeleteModal">
    <div class="confirm-modal-card">
        <div class="confirm-modal-icon">
            <i class="bx bx-trash"></i>
        </div>
        <h4>Remove Product?</h4>
        <p>Are you sure you want to remove "<span id="confirmProductName" style="font-weight: 600; color: var(--text-main);">Product</span>" from your shopping cart?</p>
        <div class="confirm-modal-actions">
            <button class="btn btn-outline" id="btnCancelDelete">Cancel</button>
            <a href="#" class="btn btn-primary" style="background-color: var(--danger); border-color: var(--danger);" id="btnConfirmDelete">Remove</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var removeButtons = document.querySelectorAll('.cart-remove-btn');
    var modal = document.getElementById('confirmDeleteModal');
    var cancelBtn = document.getElementById('btnCancelDelete');
    var confirmLink = document.getElementById('btnConfirmDelete');
    var productNameSpan = document.getElementById('confirmProductName');

    removeButtons.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var productName = btn.closest('tr').querySelector('.cart-product-name').textContent;
            var targetHref = btn.getAttribute('href');

            // Populate modal values
            productNameSpan.textContent = productName;
            confirmLink.setAttribute('href', targetHref);

            // Show modal
            modal.classList.add('show');
        });
    });

    // Clear All Cart confirmation
    var clearCartBtn = document.getElementById('btnClearCart');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', function(e) {
            e.preventDefault();
            var href = this.getAttribute('href');
            productNameSpan.textContent = "ALL items";
            confirmLink.setAttribute('href', href);
            modal.classList.add('show');
        });
    }

    // Close modal on Cancel
    cancelBtn.addEventListener('click', function() {
        modal.classList.remove('show');
    });

    // Close modal when clicking outside card
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
        }
    });
});
</script>

<?php include('footer.php'); ?>