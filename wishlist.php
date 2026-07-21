<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wishlist_ids = isset($_SESSION['wishlist']) ? array_keys($_SESSION['wishlist']) : [];
$products = [];

if (count($wishlist_ids) > 0) {
    $conn = get_db_connection();
    $clean_ids = array_map('intval', $wishlist_ids);
    $in_clause = implode(',', $clean_ids);
    $res = $conn->query("SELECT * FROM tbl_products WHERE prdct_id IN ($in_clause) ORDER BY prdct_id DESC");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

$page_title = "My Saved Wishlist";
$page_css = "css/products.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 40px 24px;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 class="section-title" style="margin-bottom: 4px; text-align: left;">
                <i class="bx bx-heart" style="color: #ef4444;"></i> My Wishlist
            </h1>
            <p style="color: #64748b; font-size: 14px;">Your saved health and pharmacy items for future purchasing.</p>
        </div>
        
        <?php if (!empty($products)): ?>
            <a href="toggle_wishlist.php?action=clear_all" class="btn btn-outline" onclick="return confirm('Clear all saved items from wishlist?');" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.3);">
                <i class="bx bx-trash"></i> Clear Wishlist
            </a>
        <?php endif; ?>
    </div>

    <?php if (empty($products)): ?>
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 60px 20px; text-align: center; max-width: 500px; margin: 20px auto;">
            <i class="bx bx-heart" style="font-size: 64px; color: #cbd5e1; margin-bottom: 14px; display: block;"></i>
            <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Your Wishlist is Empty</h3>
            <p style="color: #64748b; font-size: 14px; margin-bottom: 24px;">Explore our catalog and click the heart icon on any product to save items for later.</p>
            <a href="search_products.php" class="btn btn-primary" style="padding: 10px 24px;">Browse Pharmacy Catalog</a>
        </div>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $row): ?>
                <?php $p_stock = (int)($row['stock_quantity'] ?? 50); ?>
                <div class="product-card" id="wishlistCard_<?php echo $row['prdct_id']; ?>">
                    <div class="product-img-wrapper">
                        <div class="product-badge-bar">
                            <?php if ($p_stock > 0): ?>
                                <span class="product-badge"><i class="bx bx-check-circle"></i> In Stock</span>
                            <?php else: ?>
                                <span class="product-badge" style="color: #dc2626; border-color: rgba(220,38,38,0.3); background: #fef2f2;"><i class="bx bx-x-circle"></i> Out of Stock</span>
                            <?php endif; ?>
                            <button class="wishlist-btn active" onclick="removeFromWishlist(<?php echo $row['prdct_id']; ?>, this)" title="Remove from Wishlist">
                                <i class="bx bxs-heart" style="color: #ef4444;"></i>
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
                            <?php if ($p_stock > 0): ?>
                                <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                            <?php else: ?>
                                <button type="button" class="btn" disabled style="opacity: 0.6; cursor: not-allowed; background: #e2e8f0; color: #94a3b8; border: none;">Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<script>
function removeFromWishlist(id, btn) {
    fetch('toggle_wishlist.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                var card = document.getElementById('wishlistCard_' + id);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(function() {
                        card.remove();
                        if (document.querySelectorAll('.product-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                }
                var badge = document.getElementById('headerWishlistBadge');
                if (badge) badge.textContent = data.count;
            }
        });
}
</script>

<?php include('footer.php'); ?>
