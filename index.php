<?php
error_reporting(0);
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get DB connection from config
$conn = get_db_connection();

// Fetch products for home sections
$medicines = $conn->query('SELECT * FROM tbl_products WHERE cat_id=1 LIMIT 5');
$supplements = $conn->query('SELECT * FROM tbl_products WHERE cat_id=3 LIMIT 5');
$devices = $conn->query('SELECT * FROM tbl_products WHERE cat_id=2 LIMIT 5');

$page_title = "Home";
$page_css = "css/index.css";
include('header.php');
?>

<!-- Hero Banner Section -->
<section class="hero-section">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <h1 class="hero-title">Convenient & Reliable Healthcare at Your Fingertips</h1>
    <form action="search_products.php" method="get" class="search-form">
      <input class="hero-search-input" type="text" placeholder="Search medicines, devices, supplements..." name="search" required>
      <button type="submit" name="btnSearchProduct" class="hero-search-btn">
        <i class="bx bx-search"></i> Search
      </button>
    </form>
  </div>
</section>

<!-- Promo & Features Section -->
<section class="promo-section">
  <div class="content-container">
    <div class="promo-grid">
      
      <div class="promo-card">
        <img src="img/image 6.jpg" alt="10% Discount">
        <div class="promo-info">
          <h4>10% Discount</h4>
          <p>On all generic prescription medicines</p>
        </div>
      </div>
      
      <div class="promo-card">
        <img src="img/image 8.jpg" alt="Cash on Delivery">
        <div class="promo-info">
          <h4>Cash On Delivery</h4>
          <p>Pay right at your doorstep safely</p>
        </div>
      </div>
      
      <div class="promo-card">
        <img src="img/image 9.jpg" alt="Easy Return">
        <div class="promo-info">
          <h4>Easy Return</h4>
          <p>Hassle-free 7 days replacement</p>
        </div>
      </div>
      
    </div>
  </div>
</section>

<!-- Medicines Category Section -->
<section class="category-section">
  <div class="content-container">
    <h2 class="section-title">Medicines</h2>
    <div class="product-grid">
      <?php if ($medicines && $medicines->num_rows > 0): ?>
        <?php while ($row = $medicines->fetch_assoc()): ?>
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
        <p>No medicines found.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Supplements Category Section -->
<section class="category-section">
  <div class="content-container">
    <h2 class="section-title">Supplements</h2>
    <div class="product-grid">
      <?php if ($supplements && $supplements->num_rows > 0): ?>
        <?php while ($row = $supplements->fetch_assoc()): ?>
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
                  <span>4.9</span>
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
        <p>No supplements found.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Devices Category Section -->
<section class="category-section">
  <div class="content-container">
    <h2 class="section-title">Devices</h2>
    <div class="product-grid">
      <?php if ($devices && $devices->num_rows > 0): ?>
        <?php while ($row = $devices->fetch_assoc()): ?>
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
  </div>
</section>

<!-- Trust & Highlights Feature Section -->
<section class="trust-section">
  <div class="content-container">
    <div class="trust-grid">
      <div class="trust-card">
        <div class="trust-icon-box">
          <i class="bx bx-rocket"></i>
        </div>
        <div class="trust-info">
          <h4>Express Delivery</h4>
          <p>Fast delivery across Patan & Kathmandu valley</p>
        </div>
      </div>

      <div class="trust-card">
        <div class="trust-icon-box">
          <i class="bx bx-shield-quarter"></i>
        </div>
        <div class="trust-info">
          <h4>100% Genuine</h4>
          <p>Directly sourced certified medicines</p>
        </div>
      </div>

      <div class="trust-card">
        <div class="trust-icon-box">
          <i class="bx bx-user-voice"></i>
        </div>
        <div class="trust-info">
          <h4>Pharmacist Help</h4>
          <p>24/7 prescription & healthcare support</p>
        </div>
      </div>

      <div class="trust-card">
        <div class="trust-icon-box">
          <i class="bx bx-wallet"></i>
        </div>
        <div class="trust-info">
          <h4>Easy Payments</h4>
          <p>Cash on delivery & instant digital QR</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Continuous Infinite Marquee Customer Reviews Section -->
<section class="marquee-review-section">
  <div class="marquee-header">
    <div class="section-subtitle">Real Customer Feedback</div>
    <h3 class="section-title" style="margin: 0;">What Our Customers Say</h3>
  </div>

  <div class="marquee-track-container">
    <div class="marquee-track">
      
      <!-- Review 1 -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box">
              <div class="review-avatar-circle">A</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Ajit K.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Excellent delivery speed and genuine medicines. I highly recommend Medlife to anyone looking for convenient online healthcare."</p>
      </div>

      <!-- Review 2 -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-blue">
              <div class="review-avatar-circle">R</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Ram S.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"The feedback form and customer care is so helpful. I had questions about my supplement order and got immediate replies."</p>
      </div>

      <!-- Review 3 -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-teal">
              <div class="review-avatar-circle">L</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Lakshman N.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Ordering medical devices online used to be hard, but Medlife made it easy. Product quality is top notch."</p>
      </div>

      <!-- Review 4 -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-indigo">
              <div class="review-avatar-circle">N</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Nabin P.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Very reliable services. The payment options were clean and Patan campus location is super convenient!"</p>
      </div>

      <!-- Review 5 -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-purple">
              <div class="review-avatar-circle">M</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Manish G.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"I save 10% on my bills every month. A must-use online healthcare system for every family."</p>
      </div>

      <!-- Duplicate Set for Seamless Continuous Loop -->
      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box">
              <div class="review-avatar-circle">A</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Ajit K.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Excellent delivery speed and genuine medicines. I highly recommend Medlife to anyone looking for convenient online healthcare."</p>
      </div>

      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-blue">
              <div class="review-avatar-circle">R</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Ram S.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"The feedback form and customer care is so helpful. I had questions about my supplement order and got immediate replies."</p>
      </div>

      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-teal">
              <div class="review-avatar-circle">L</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Lakshman N.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Ordering medical devices online used to be hard, but Medlife made it easy. Product quality is top notch."</p>
      </div>

      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-indigo">
              <div class="review-avatar-circle">N</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Nabin P.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"Very reliable services. The payment options were clean and Patan campus location is super convenient!"</p>
      </div>

      <div class="marquee-card">
        <div class="review-header-row">
          <div style="display: flex; align-items: center; gap: 12px;">
            <div class="review-avatar-box avatar-purple">
              <div class="review-avatar-circle">M</div>
              <div class="review-quote-badge"><i class="bx bxs-quote-right"></i></div>
            </div>
            <div>
              <h4 class="review-author">Manish G.</h4>
              <span class="verified-buyer"><i class="bx bx-check-circle"></i> Verified Buyer</span>
            </div>
          </div>
          <div class="review-rating">
            <i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i><i class="bx bxs-star"></i>
          </div>
        </div>
        <p class="review-text">"I save 10% on my bills every month. A must-use online healthcare system for every family."</p>
      </div>

    </div>
  </div>
</section>

<?php include('footer.php'); ?>
