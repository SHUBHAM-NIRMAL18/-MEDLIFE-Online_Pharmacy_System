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
            $stock_qty = isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 50;
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

// Helper to trace category parent chain for breadcrumb navigation
function get_category_breadcrumb_chain($conn, $cat_id) {
    $chain = [];
    $curr_id = (int)$cat_id;
    while ($curr_id > 0) {
        $stmt = $conn->prepare("SELECT cat_id, cat_name, parent_id FROM tbl_categories WHERE cat_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $curr_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();
                array_unshift($chain, $row);
                $curr_id = (int)$row['parent_id'];
            } else {
                break;
            }
            $stmt->close();
        } else {
            break;
        }
    }
    return $chain;
}

$breadcrumb_chain = get_category_breadcrumb_chain($conn, $cat_id);

$category_name = !empty($breadcrumb_chain) ? end($breadcrumb_chain)['cat_name'] : "Healthcare Product";

$page_title = $product_name;
$page_css = "css/products.css";
include('header.php');
?>

<main class="content-container" style="min-height: 65vh; padding: 30px 24px;">
    
    <!-- Category Hierarchy Breadcrumb Navigation -->
    <nav class="product-breadcrumb-bar" aria-label="breadcrumb">
        <a href="index.php" class="crumb-link"><i class="bx bx-home-alt"></i> Home</a>
        <span class="crumb-sep"><i class="bx bx-chevron-right"></i></span>
        <a href="search_products.php" class="crumb-link">Shop Catalog</a>
        
        <?php foreach ($breadcrumb_chain as $b_item): ?>
            <span class="crumb-sep"><i class="bx bx-chevron-right"></i></span>
            <a href="search_products.php?cat=<?php echo $b_item['cat_id']; ?>" class="crumb-link">
                <?php echo htmlspecialchars($b_item['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        <?php endforeach; ?>
        
        <span class="crumb-sep"><i class="bx bx-chevron-right"></i></span>
        <span class="crumb-current"><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></span>
    </nav>
    
    <div class="product-detail-flex" style="margin-top: 10px;">
        
        <!-- Left Side: Product Image with Magnifier -->
        <div class="product-detail-image-side">
            <div class="product-badge-bar" style="top: 16px; left: 16px; right: 16px;">
                <?php if ($stock_qty > 0): ?>
                    <span class="product-badge"><i class="bx bx-check-circle"></i> In Stock</span>
                <?php else: ?>
                    <span class="product-badge" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.3); background: #fef2f2;"><i class="bx bx-x-circle"></i> Out of Stock</span>
                <?php endif; ?>
                <button class="wishlist-btn" onclick="toggleWishlist(this, event)" title="Save to Wishlist">
                    <i class="bx bx-heart"></i>
                </button>
            </div>

            <div class="img-zoom-container">
                <img id="productMainImage" src="medimg/<?php echo htmlspecialchars($prdct_img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?>">
                <div id="magnifierGlass" class="img-magnifier-glass"></div>
            </div>

            <div class="zoom-hint">
                <i class="bx bx-zoom-in"></i> Hover image to magnify details
            </div>
        </div>
        
        <!-- Right Side: Details & Buy Info -->
        <div class="product-detail-info-side">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                <div class="product-category-pills">
                    <?php foreach ($breadcrumb_chain as $b_idx => $b_item): ?>
                        <a href="search_products.php?cat=<?php echo $b_item['cat_id']; ?>" class="cat-pill">
                            <?php echo htmlspecialchars($b_item['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                        <?php if ($b_idx < count($breadcrumb_chain) - 1): ?>
                            <span class="cat-pill-sep">›</span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <div class="product-rating" style="font-size: 14px;">
                    <i class="bx bxs-star"></i>
                    <i class="bx bxs-star"></i>
                    <i class="bx bxs-star"></i>
                    <i class="bx bxs-star"></i>
                    <i class="bx bxs-star-half"></i>
                    <span style="font-size: 12px; font-weight: 600;">4.8 (120+ Verified Reviews)</span>
                </div>
            </div>

            <h1 class="product-detail-title"><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="product-detail-company">Manufacturer: <?php echo !empty($prdct_company) ? htmlspecialchars($prdct_company, ENT_QUOTES, 'UTF-8') : 'Medlife Care'; ?></div>
            
            <div class="product-detail-price">रु. <?php echo number_format($price, 2); ?></div>
            
            <!-- Technical Specs / Product Availability (General status only, no unit count shown) -->
            <div class="product-detail-specs">
                <div class="spec-row">
                    <strong>Category Path</strong>
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <?php foreach ($breadcrumb_chain as $b_idx => $b_item): ?>
                            <a href="search_products.php?cat=<?php echo $b_item['cat_id']; ?>" style="color: #059669; font-weight: 600; text-decoration: none;">
                                <?php echo htmlspecialchars($b_item['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                            <?php if ($b_idx < count($breadcrumb_chain) - 1): ?>
                                <span style="color: #94a3b8; font-size: 11px;">›</span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="spec-row">
                    <strong>Availability</strong>
                    <?php if ($stock_qty > 0): ?>
                        <span style="color: #059669; font-weight: 600;"><i class="bx bx-check-circle"></i> In Stock & Ready to Dispatch</span>
                    <?php else: ?>
                        <span style="color: #dc2626; font-weight: 600;"><i class="bx bx-x-circle"></i> Currently Out of Stock</span>
                    <?php endif; ?>
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
                    <strong>Guarantee</strong>
                    <span style="color: #059669; font-weight: 600;"><i class="bx bx-badge-check"></i> 100% Genuine Pharmacy Grade</span>
                </div>
            </div>

            <!-- Quantity & Add to Cart Form -->
            <form action="addToCart.php" method="GET" class="product-purchase-form">
                <input type="hidden" name="id" value="<?php echo $product_id; ?>">
                
                <?php if ($stock_qty > 0): ?>
                    <div class="qty-control-wrapper">
                        <label for="productQtyInput" class="qty-label">Quantity:</label>
                        <div class="qty-selector">
                            <button type="button" class="qty-btn minus" onclick="decrementQty()"><i class="bx bx-minus"></i></button>
                            <input type="number" id="productQtyInput" name="quantity" value="1" min="1" max="<?php echo $stock_qty; ?>" readonly class="qty-number-input">
                            <button type="button" class="qty-btn plus" onclick="incrementQty(<?php echo $stock_qty; ?>)"><i class="bx bx-plus"></i></button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-add-cart">
                        <i class="bx bx-cart-add" style="font-size: 19px;"></i> Add to Cart
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-disabled" disabled>
                        <i class="bx bx-x-circle" style="font-size: 19px;"></i> Out of Stock
                    </button>
                <?php endif; ?>
            </form>
            
        </div>
    </div>

    <!-- Detailed Description & Clinical Guidance Card -->
    <div class="product-description-card">
        <h3><i class="bx bx-file-blank"></i> Product Description & Care Guidelines</h3>
        <p>
            <strong><?php echo htmlspecialchars($product_name, ENT_QUOTES, 'UTF-8'); ?></strong> is a certified healthcare product manufactured under strict Quality Control standards by <strong><?php echo htmlspecialchars(!empty($prdct_company) ? $prdct_company : 'Medlife Care', ENT_QUOTES, 'UTF-8'); ?></strong>. Designed for optimal efficacy, patient safety, and medical reliability. Please verify manufacturing and expiration details upon delivery.
        </p>
        
        <div class="product-highlights-grid">
            <div class="highlight-item">
                <i class="bx bx-badge-check"></i>
                <div>
                    <strong>100% Genuine Guarantee</strong>
                    <span>Sourced directly from authorized pharmaceutical distributors</span>
                </div>
            </div>
            <div class="highlight-item">
                <i class="bx bx-truck"></i>
                <div>
                    <strong>Express Delivery</strong>
                    <span>Dispatched in 24 hours in secure packaging</span>
                </div>
            </div>
            <div class="highlight-item">
                <i class="bx bx-shield-quarter"></i>
                <div>
                    <strong>Storage & Safety</strong>
                    <span>Store in a cool, dry place away from direct heat and sunlight</span>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
function incrementQty(maxStock) {
    var input = document.getElementById('productQtyInput');
    var val = parseInt(input.value, 10) || 1;
    if (val < maxStock) {
        input.value = val + 1;
    }
}
function decrementQty() {
    var input = document.getElementById('productQtyInput');
    var val = parseInt(input.value, 10) || 1;
    if (val > 1) {
        input.value = val - 1;
    }
}

// Product Image Magnifier Logic
function initImageMagnifier(imgID, glassID, zoomLevel) {
    var img = document.getElementById(imgID);
    var glass = document.getElementById(glassID);
    if (!img || !glass) return;

    var zoom = zoomLevel || 2.5;

    function moveMagnifier(e) {
        e.preventDefault();
        var pos = getCursorPos(e);
        var x = pos.x;
        var y = pos.y;

        var bw = 3;
        var w = glass.offsetWidth / 2;
        var h = glass.offsetHeight / 2;

        if (x > img.width - (w / zoom)) { x = img.width - (w / zoom); }
        if (x < w / zoom) { x = w / zoom; }
        if (y > img.height - (h / zoom)) { y = img.height - (h / zoom); }
        if (y < h / zoom) { y = h / zoom; }

        glass.style.left = (x - w) + "px";
        glass.style.top = (y - h) + "px";
        glass.style.backgroundPosition = "-" + ((x * zoom) - w + bw) + "px -" + ((y * zoom) - h + bw) + "px";
    }

    function getCursorPos(e) {
        var a = img.getBoundingClientRect();
        var x = (e.pageX || (e.touches && e.touches[0] ? e.touches[0].pageX : 0)) - a.left - window.pageXOffset;
        var y = (e.pageY || (e.touches && e.touches[0] ? e.touches[0].pageY : 0)) - a.top - window.pageYOffset;
        return {x: x, y: y};
    }

    img.addEventListener("mouseenter", function() {
        glass.style.backgroundImage = "url('" + img.src + "')";
        glass.style.backgroundSize = (img.width * zoom) + "px " + (img.height * zoom) + "px";
        glass.style.display = "block";
    });

    img.addEventListener("mouseleave", function() {
        glass.style.display = "none";
    });

    img.addEventListener("mousemove", moveMagnifier);
    glass.addEventListener("mousemove", moveMagnifier);
}

document.addEventListener("DOMContentLoaded", function() {
    initImageMagnifier("productMainImage", "magnifierGlass", 2.5);
});
</script>

<?php include('footer.php'); ?>