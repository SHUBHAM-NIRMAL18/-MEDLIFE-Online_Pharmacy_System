<?php  
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$product_id = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $conn = get_db_connection();
    
    // Fetch product details
    $stmt = $conn->prepare("SELECT * FROM tbl_products WHERE prdct_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $prdct_img = $row['prdct_img'];
            $product_name = $row['prdct_name'];
            $price = $row['prdct_price'];
            $prdct_company = isset($row['prdct_company']) ? $row['prdct_company'] : '';
            $manf_date = isset($row['manf_date']) ? $row['manf_date'] : '';
            $exp_date = isset($row['exp_date']) ? $row['exp_date'] : '';
            $cat_id = isset($row['cat_id']) ? $row['cat_id'] : 0;
        } else {
            header('location:index.php');
            exit();
        }
        $stmt->close();
    }
} else {
    header('location:index.php');
    exit();
}

// Fetch quantity currently in cart
$quantity = 1;
if (isset($_SESSION['cart'][$product_id]['quantity'])) {
    $quantity = (int)$_SESSION['cart'][$product_id]['quantity'];
}

$category_name = "Healthcare Product";
if ($cat_id == 1) {
    $category_name = "Medicine";
} elseif ($cat_id == 2) {
    $category_name = "Clinical Device";
} elseif ($cat_id == 3) {
    $category_name = "Health Supplement";
}

$page_title = $product_name;
$page_css = "css/products.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <div class="product-detail-flex">
        
        <!-- Left Side: Product Image -->
        <div class="product-detail-image-side">
            <img src="medimg/<?php echo htmlspecialchars($prdct_img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        
        <!-- Right Side: Details & Buy Info -->
        <div class="product-detail-info-side">
            <span class="product-detail-badge"><?php echo htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8'); ?></span>
            <h1 class="product-detail-title"><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="product-detail-company">Manufacturer: <?php echo !empty($prdct_company) ? htmlspecialchars($prdct_company, ENT_QUOTES, 'UTF-8') : 'Unknown'; ?></div>
            
            <div class="product-detail-price">Rs. <?php echo number_format($price, 2); ?></div>
            
            <!-- Technical Specs / Product Details -->
            <div class="product-detail-specs">
                <div class="spec-row">
                    <strong>Availability</strong>
                    <span style="color: var(--success); font-weight: 600;"><i class="bx bx-check-shield"></i> In Stock & Genuine</span>
                </div>
                
                <?php if (!empty($manf_date) && $manf_date !== '0000-00-00'): ?>
                    <div class="spec-row">
                        <strong>Mfg. Date</strong>
                        <span><?php echo date("F d, Y", strtotime($manf_date)); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($exp_date) && $exp_date !== '0000-00-00'): ?>
                    <div class="spec-row">
                        <strong>Exp. Date</strong>
                        <span style="color: var(--danger); font-weight: 500;"><?php echo date("F d, Y", strtotime($exp_date)); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="spec-row">
                    <strong>Shipping Details</strong>
                    <span>Dispatched in 24 hours. Delivery standard terms apply.</span>
                </div>
            </div>
            
            <!-- Quantity and Add to Cart Form -->
            <form action="addToCart.php" method="GET" class="product-purchase-form" onsubmit="return validateQuantity();">
                <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                
                <div style="display: flex; align-items: center; gap: 10px;">
                    <label for="quantity" class="qty-label">Qty:</label>
                    <input type="number" step="1" min="1" value="<?php echo $quantity; ?>" name="quantity" id="quantity" class="qty-number-input">
                </div>
                
                <button type="submit" class="btn btn-primary" name="btncart">
                    <i class="bx bx-cart-add" style="font-size: 18px;"></i> Add to Cart
                </button>
            </form>
            
        </div>
    </div>
</main>

<script>
function validateQuantity() {
    var quantityInput = document.getElementById('quantity');
    var quantity = parseInt(quantityInput.value, 10);

    if (isNaN(quantity) || quantity <= 0) {
        alert('Please enter a valid quantity of 1 or more.');
        return false;
    }
    return true;
}
</script>

<?php include('footer.php'); ?>