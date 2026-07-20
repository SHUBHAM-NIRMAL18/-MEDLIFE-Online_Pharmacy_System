<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$products = null;

if (!empty($search)) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM tbl_products WHERE prdct_name LIKE ?");
    if ($stmt) {
        $searchTerm = "%" . $search . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $products = $stmt->get_result();
    }
}

$page_title = !empty($search) ? "Search: " . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') : "Search Products";
$page_css = "css/products.css";
include('header.php'); 
?>

<main class="content-container" style="padding: 40px 24px; min-height: 60vh;">
    
    <!-- Search Bar -->
    <div style="max-width: 600px; margin: 0 auto 30px; text-align: center;">
        <form action="search_products.php" method="get" style="display: flex; gap: 10px;">
            <input type="text" class="form-control" placeholder="Search medicines, supplements, devices..." name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" style="padding: 12px 18px; border-radius: 30px; font-size: 15px;">
            <button type="submit" name="btnSearchProduct" class="btn btn-primary" style="border-radius: 30px; padding: 0 24px;">
                <i class="bx bx-search" style="font-size: 18px;"></i> Search
            </button>
        </form>
    </div>

    <h2 class="section-title">
        <?php echo !empty($search) ? 'Results for "' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '"' : 'Search Products'; ?>
    </h2>
    
    <div class="product-grid">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while($row = $products->fetch_assoc()): ?>
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
                                <span>4.8</span>
                            </div>
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="product-price-row">
                            <span class="product-price">रु. <?php echo number_format($row['prdct_price'], 2); ?></span>
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
            <div style="text-align: center; grid-column: 1 / -1; padding: 50px 0; color: var(--text-light);">
                <i class="bx bx-search-alt" style="font-size: 54px; margin-bottom: 12px; display: block; color: var(--border-color);"></i>
                <p style="font-size: 16px; color: var(--text-muted);">
                    <?php echo !empty($search) ? 'No products found matching "' . htmlspecialchars($search, ENT_QUOTES, 'UTF-8') . '".' : 'Enter a search term above to find pharmacy products.'; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

</main>

<?php include('footer.php'); ?>
