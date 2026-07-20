<?php
require_once 'config.php';
$conn = get_db_connection();
$sql = 'SELECT * FROM tbl_products WHERE cat_id=2';
$devices = $conn->query($sql);

$page_title = "Devices";
$page_css = "css/products.css";
include('header.php');
?>

<main class="content-container" style="padding: 40px 24px; min-height: 60vh;">
    <h2 class="section-title">Devices</h2>
    
    <div class="product-grid">
        <?php if ($devices && $devices->num_rows > 0): ?>
            <?php while($row = $devices->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <div class="product-badge-bar">
                            <span class="product-badge"><i class="bx bx-check-shield"></i> In Stock</span>
                            <button class="wishlist-btn" onclick="toggleWishlist(this, event)" title="Save to Wishlist">
                                <i class="bx bx-heart"></i>
                            </button>
                        </div>
                        <img src="medimg/<?php echo htmlspecialchars($row['prdct_img'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="product-info">
                        <div class="product-meta-row">
                            <span class="product-company"><?php echo !empty($row['prdct_company']) ? htmlspecialchars($row['prdct_company'], ENT_QUOTES, 'UTF-8') : 'Medlife Care'; ?></span>
                            <div class="product-rating">
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star"></i>
                                <i class="bx bxs-star-half"></i>
                                <span>4.7</span>
                            </div>
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="product-price-row">
                            <span class="product-price">Rs. <?php echo number_format($row['prdct_price'], 2); ?></span>
                            <span class="guarantee-tag"><i class="bx bx-badge-check"></i> Genuine</span>
                        </div>
                        <div class="product-actions">
                            <a href="single.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-outline"><i class="bx bx-info-circle"></i> Details</a>
                            <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No devices found.</p>
        <?php endif; ?>
    </div>
</main>

<?php include('footer.php') ?>
</body>
</html>