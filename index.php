<?php
error_reporting(0);
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$current_pharmacy_id = get_current_pharmacy_id();
$current_pharmacy = get_pharmacy_details($current_pharmacy_id);
$all_pharmacies = get_active_pharmacies();

// Helper to fetch all child/sub-subcategory IDs recursively
if (!function_exists('get_child_cat_ids')) {
    function get_child_cat_ids($conn, $parent_id, $pharmacy_id) {
        $ids = [(int)$parent_id];
        $stmt = $conn->prepare("SELECT cat_id FROM tbl_categories WHERE parent_id = ? AND pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $parent_id, $pharmacy_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $sub_ids = get_child_cat_ids($conn, $r['cat_id'], $pharmacy_id);
                    $ids = array_merge($ids, $sub_ids);
                }
            }
            $stmt->close();
        }
        return array_unique($ids);
    }
}

// Fetch Active Root Categories for this Pharmacy
$store_categories = [];
$cat_res = $conn->query("SELECT * FROM tbl_categories WHERE pharmacy_id = $current_pharmacy_id AND parent_id = 0 AND cat_status = 1 ORDER BY cat_id ASC");
if ($cat_res && $cat_res->num_rows > 0) {
    while ($cat = $cat_res->fetch_assoc()) {
        $cid = (int)$cat['cat_id'];
        $all_ids = get_child_cat_ids($conn, $cid, $current_pharmacy_id);
        $in_ids = implode(',', $all_ids);
        
        $prod_res = $conn->query("SELECT * FROM tbl_products WHERE pharmacy_id = $current_pharmacy_id AND cat_id IN ($in_ids) AND prdct_status = 1 ORDER BY prdct_id DESC LIMIT 4");
        $prods = [];
        if ($prod_res && $prod_res->num_rows > 0) {
            while ($p = $prod_res->fetch_assoc()) {
                $prods[] = $p;
            }
        }
        $cat['products'] = $prods;
        $store_categories[] = $cat;
    }
}

// Fetch All Featured Products for this Store
$all_store_products = [];
$all_prod_res = $conn->query("SELECT * FROM tbl_products WHERE pharmacy_id = $current_pharmacy_id AND prdct_status = 1 ORDER BY prdct_id DESC LIMIT 8");
if ($all_prod_res && $all_prod_res->num_rows > 0) {
    while ($p = $all_prod_res->fetch_assoc()) {
        $all_store_products[] = $p;
    }
}

$page_title = "Home - " . $current_pharmacy['name'];
$page_css = "css/index.css";
include('header.php');
?>

<style>
/* Multi-Tenant Storefront Brand Header */
.saas-store-banner {
  background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #059669 100%);
  color: #ffffff;
  padding: 20px 0;
  box-shadow: 0 4px 12px rgba(4, 120, 87, 0.15);
}
.store-banner-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
}
.store-brand-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.store-brand-icon {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.18);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  flex-shrink: 0;
}
.store-brand-name {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  display: flex;
  align-items: center;
  gap: 8px;
}
.store-badge-verified {
  background: rgba(255, 255, 255, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.4);
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}
.store-brand-meta {
  font-size: 13px;
  opacity: 0.9;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}
.store-switch-btn {
  background: #ffffff;
  color: #065f46;
  font-weight: 700;
  font-size: 13px;
  padding: 9px 18px;
  border-radius: 24px;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  transition: transform 0.15s ease, background 0.15s ease;
}
.store-switch-btn:hover {
  transform: translateY(-2px);
  background: #f0fdf4;
}

/* Multi-Store Carousel / Cards Section */
.network-discovery-section {
  padding: 36px 0 20px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.network-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 18px;
  margin-top: 18px;
}
.network-card {
  background: #ffffff;
  border-radius: 14px;
  border: 1.5px solid #e2e8f0;
  padding: 16px 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  text-decoration: none;
  color: inherit;
  transition: all 0.2s ease;
}
.network-card:hover {
  border-color: #059669;
  box-shadow: 0 8px 20px rgba(5, 150, 105, 0.08);
  transform: translateY(-2px);
}
.network-card.active {
  border-color: #059669;
  background: #f0fdf4;
}

/* Category Quick Filter Pills */
.category-pills-bar {
  display: flex;
  gap: 10px;
  overflow-x: auto;
  padding: 18px 0 10px;
  scrollbar-width: none;
}
.category-pill {
  padding: 8px 18px;
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 700;
  color: #334155;
  text-decoration: none;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s ease;
}
.category-pill:hover, .category-pill.active {
  background: #059669;
  color: #ffffff;
  border-color: #059669;
}
</style>

<!-- Multi-Tenant Active Storefront Brand Header -->
<div class="saas-store-banner">
  <div class="content-container store-banner-inner">
    <div class="store-brand-left">
      <div class="store-brand-icon">
        <i class='bx bx-store'></i>
      </div>
      <div>
        <div class="store-brand-name">
          <?php echo htmlspecialchars($current_pharmacy['name']); ?>
          <span class="store-badge-verified"><i class='bx bx-check-shield'></i> Verified Pharmacy</span>
        </div>
        <div class="store-brand-meta">
          <span><i class='bx bx-map-pin'></i> <?php echo htmlspecialchars($current_pharmacy['address']); ?></span>
          <span>&bull;</span>
          <span><i class='bx bx-time-five'></i> <?php echo htmlspecialchars(!empty($current_pharmacy['business_hours']) ? $current_pharmacy['business_hours'] : 'Daily: 8:00 AM - 9:00 PM'); ?></span>
          <span>&bull;</span>
          <span><i class='bx bx-bus'></i> Delivery: <strong>रु. <?php echo number_format($current_pharmacy['delivery_fee'], 2); ?></strong></span>
        </div>
      </div>
    </div>
    
    <div>
      <a href="stores.php" class="store-switch-btn">
        <i class='bx bx-transfer-alt'></i> Switch Pharmacy Store
      </a>
    </div>
  </div>
</div>

<!-- Hero Banner Section -->
<section class="hero-section">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="hero-title">Order Certified Healthcare from <?php echo htmlspecialchars($current_pharmacy['name']); ?></h1>
    <p style="color: #e2e8f0; font-size: 15px; margin-bottom: 22px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
      100% Genuine batch tracking, licensed pharmacist verification, and doorstep courier delivery.
    </p>
    <form action="search_products.php" method="get" class="search-form">
      <input type="hidden" name="pharmacy" value="<?php echo $current_pharmacy_id; ?>">
      <input class="hero-search-input" type="text" placeholder="Search medicines, supplements, or medical devices in this store..." name="search" required>
      <button type="submit" name="btnSearchProduct" class="hero-search-btn">
        <i class="bx bx-search"></i> Search Store
      </button>
    </form>
  </div>
</section>

<!-- Multi-Pharmacy Marketplace Discovery Grid -->
<section class="network-discovery-section">
  <div class="content-container">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
          <i class='bx bx-globe' style="color: #059669;"></i> Explore Partner Pharmacies on MEDLIFE Network
        </h3>
        <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Switch stores to browse specialized medicine, herbal organics, or orthopedic catalogs.</p>
      </div>
      <a href="stores.php" style="font-size: 13px; font-weight: 700; color: #059669; text-decoration: none;">
        View All Stores (<?php echo count($all_pharmacies); ?>) &rarr;
      </a>
    </div>

    <div class="network-grid">
      <?php foreach ($all_pharmacies as $ph): ?>
        <?php 
        $is_act = ($ph['pharmacy_id'] == $current_pharmacy_id);
        $p_count = (int)($ph['product_count'] ?? 0);
        ?>
        <a href="index.php?pharmacy=<?php echo $ph['pharmacy_id']; ?>" class="network-card <?php echo $is_act ? 'active' : ''; ?>">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 42px; height: 42px; border-radius: 10px; background: <?php echo $is_act ? '#059669' : '#f1f5f9'; ?>; color: <?php echo $is_act ? '#ffffff' : '#0f172a'; ?>; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
              <i class='bx bx-plus-medical'></i>
            </div>
            <div>
              <strong style="font-size: 13.5px; color: #0f172a; display: block; line-height: 1.2;"><?php echo htmlspecialchars($ph['name']); ?></strong>
              <span style="font-size: 12px; color: #64748b; margin-top: 2px; display: block;"><?php echo htmlspecialchars($ph['address']); ?></span>
            </div>
          </div>
          <div style="text-align: right; flex-shrink: 0;">
            <span style="font-size: 11px; font-weight: 700; background: <?php echo $is_act ? '#059669' : '#e2e8f0'; ?>; color: <?php echo $is_act ? '#ffffff' : '#334155'; ?>; padding: 3px 8px; border-radius: 10px;">
              <?php echo $is_act ? 'Active' : 'Switch'; ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Category Quick Filter Pills -->
<?php if (!empty($store_categories)): ?>
  <div class="content-container">
    <div class="category-pills-bar">
      <a href="search_products.php?pharmacy=<?php echo $current_pharmacy_id; ?>" class="category-pill active">
        <i class='bx bx-grid-alt'></i> All Products (<?php echo count($all_store_products); ?>)
      </a>
      <?php foreach ($store_categories as $sc): ?>
        <a href="search_products.php?pharmacy=<?php echo $current_pharmacy_id; ?>&cat=<?php echo $sc['cat_id']; ?>" class="category-pill">
          <i class='bx bx-folder'></i> <?php echo htmlspecialchars($sc['cat_name']); ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<!-- Dynamic Category Sections for the Selected Pharmacy -->
<?php if (!empty($store_categories)): ?>
  <?php foreach ($store_categories as $cat_item): ?>
    <?php if (!empty($cat_item['products'])): ?>
      <section class="category-section" style="padding: 24px 0;">
        <div class="content-container">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
            <h2 class="section-title" style="margin: 0;">
              <?php echo htmlspecialchars($cat_item['cat_name']); ?>
            </h2>
            <a href="search_products.php?pharmacy=<?php echo $current_pharmacy_id; ?>&cat=<?php echo $cat_item['cat_id']; ?>" style="font-size: 13px; font-weight: 700; color: #059669; text-decoration: none;">
              Browse Category &rarr;
            </a>
          </div>

          <div class="product-grid">
            <?php foreach ($cat_item['products'] as $row): ?>
              <?php 
              $stock_qty = isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 50;
              $img_src = get_product_image_url($row['prdct_img']);
              ?>
              <div class="product-card">
                <div class="product-img-wrapper">
                  <div class="product-badge-bar">
                    <?php if ($stock_qty > 0): ?>
                      <span class="product-badge"><i class="bx bx-check-shield"></i> In Stock</span>
                    <?php else: ?>
                      <span class="product-badge" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.3); background: #fef2f2;"><i class="bx bx-x-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                    <button class="wishlist-btn" onclick="toggleWishlist(this, event)" title="Save to Wishlist">
                      <i class="bx bx-heart"></i>
                    </button>
                  </div>
                  <img src="<?php echo htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                </div>
                <div class="product-info">
                  <div class="product-meta-row">
                    <span class="product-company"><?php echo !empty($row['prdct_company']) ? htmlspecialchars($row['prdct_company'], ENT_QUOTES, 'UTF-8') : htmlspecialchars($current_pharmacy['name']); ?></span>
                    <div class="product-rating">
                      <i class="bx bxs-star"></i>
                      <i class="bx bxs-star"></i>
                      <i class="bx bxs-star"></i>
                      <i class="bx bxs-star"></i>
                      <i class="bx bxs-star-half"></i>
                      <span>4.9</span>
                    </div>
                  </div>
                  <h3 class="product-name"><?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                  <div class="product-price-row">
                    <span class="product-price">रु. <?php echo number_format($row['prdct_price'], 2); ?></span>
                    <span class="guarantee-tag"><i class="bx bx-badge-check"></i> Genuine</span>
                  </div>
                  <div class="product-actions">
                    <a href="single.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-outline"><i class="bx bx-info-circle"></i> Details</a>
                    <?php if ($stock_qty > 0): ?>
                      <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                    <?php else: ?>
                      <button type="button" class="btn btn-disabled" disabled><i class="bx bx-x-circle"></i> Out of Stock</button>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    <?php endif; ?>
  <?php endforeach; ?>
<?php else: ?>
  <!-- Fallback All Featured Products Section -->
  <section class="category-section" style="padding: 24px 0;">
    <div class="content-container">
      <h2 class="section-title">Featured Products in <?php echo htmlspecialchars($current_pharmacy['name']); ?></h2>
      <div class="product-grid">
        <?php if (!empty($all_store_products)): ?>
          <?php foreach ($all_store_products as $row): ?>
            <?php 
            $stock_qty = isset($row['stock_quantity']) ? (int)$row['stock_quantity'] : 50;
            $img_src = get_product_image_url($row['prdct_img']);
            ?>
            <div class="product-card">
              <div class="product-img-wrapper">
                <div class="product-badge-bar">
                  <?php if ($stock_qty > 0): ?>
                    <span class="product-badge"><i class="bx bx-check-shield"></i> In Stock</span>
                  <?php else: ?>
                    <span class="product-badge" style="color: #dc2626; border-color: rgba(220, 38, 38, 0.3); background: #fef2f2;"><i class="bx bx-x-circle"></i> Out of Stock</span>
                  <?php endif; ?>
                  <button class="wishlist-btn" onclick="toggleWishlist(this, event)" title="Save to Wishlist">
                    <i class="bx bx-heart"></i>
                  </button>
                </div>
                <img src="<?php echo htmlspecialchars($img_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
              </div>
              <div class="product-info">
                <div class="product-meta-row">
                  <span class="product-company"><?php echo !empty($row['prdct_company']) ? htmlspecialchars($row['prdct_company'], ENT_QUOTES, 'UTF-8') : 'Medlife Store'; ?></span>
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
                  <?php if ($stock_qty > 0): ?>
                    <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                  <?php else: ?>
                    <button type="button" class="btn btn-disabled" disabled><i class="bx bx-x-circle"></i> Out of Stock</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="text-align: center; padding: 40px 20px; background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; grid-column: 1 / -1;">
            <i class='bx bx-package' style="font-size: 40px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
            <h4>No products listed in this store yet</h4>
            <p style="font-size: 13px; color: #64748b;">Please select another pharmacy store from the top bar.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- SaaS Trust & Quality Features Banner -->
<section class="promo-section" style="padding: 40px 0; background: #ffffff; border-top: 1px solid #e2e8f0;">
  <div class="content-container">
    <div class="promo-grid">
      
      <div class="promo-card" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
          <i class='bx bx-check-shield'></i>
        </div>
        <div class="promo-info" style="margin-left: 14px;">
          <h4>100% Genuine & Batch Verified</h4>
          <p>Strict FEFO batch tracking with verifiable expiration dates</p>
        </div>
      </div>
      
      <div class="promo-card" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(99, 102, 241, 0.12); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
          <i class='bx bx-plus-medical'></i>
        </div>
        <div class="promo-info" style="margin-left: 14px;">
          <h4>Pharmacist Prescription Review</h4>
          <p>Every RX slip verified by certified pharmacists</p>
        </div>
      </div>
      
      <div class="promo-card" style="border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.12); color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0;">
          <i class='bx bx-cycling'></i>
        </div>
        <div class="promo-info" style="margin-left: 14px;">
          <h4>Express Doorstep Delivery</h4>
          <p>Real-time courier GPS dispatch & COD cash tracking</p>
        </div>
      </div>
      
    </div>
  </div>
</section>

<?php include('footer.php'); ?>
