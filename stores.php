<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();
$current_pharmacy_id = get_current_pharmacy_id();
$pharmacies = get_active_pharmacies();

$page_title = "Pharmacy Stores Network - MEDLIFE SaaS";
$page_css = "css/product.css";
include('header.php');
?>

<style>
.stores-wrapper {
  max-width: 1280px;
  margin: 0 auto;
  padding: 40px 24px;
  min-height: 70vh;
}
.stores-hero {
  text-align: center;
  margin-bottom: 40px;
}
.stores-hero h1 {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
}
.stores-hero p {
  font-size: 15px;
  color: #64748b;
  margin-top: 8px;
  max-width: 650px;
  margin-left: auto;
  margin-right: auto;
}
.stores-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 24px;
}
.store-card {
  background: #ffffff;
  border-radius: 18px;
  border: 1.5px solid #e2e8f0;
  padding: 24px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  display: flex;
  flex-direction: column;
  position: relative;
}
.store-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08);
  border-color: #059669;
}
.store-card.active-store {
  border-color: #059669;
  background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 100%);
}
.active-badge-pill {
  position: absolute;
  top: 16px;
  right: 16px;
  background: #059669;
  color: #ffffff;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.store-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: linear-gradient(135deg, #059669 0%, #10b981 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  margin-bottom: 16px;
  box-shadow: 0 6px 14px rgba(5, 150, 105, 0.25);
}
.store-title {
  font-size: 18px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}
.store-address {
  font-size: 13px;
  color: #64748b;
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 14px;
}
.store-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 14px;
  margin-bottom: 18px;
  font-size: 12.5px;
}
.store-meta-item strong {
  display: block;
  font-size: 11px;
  color: #94a3b8;
  text-transform: uppercase;
  font-weight: 700;
  margin-bottom: 2px;
}
.store-meta-item span {
  font-weight: 700;
  color: #334155;
}
.store-footer-actions {
  margin-top: auto;
  display: flex;
  gap: 10px;
}
</style>

<div class="stores-wrapper">

    <!-- Hero Header -->
    <div class="stores-hero">
        <h1><i class='bx bx-store-alt' style="color: #059669;"></i> Explore Multi-Pharmacy Network</h1>
        <p>Choose from our licensed partner pharmacies across Nepal. Each store offers authentic stock, fast delivery, and certified pharmacist verification.</p>
    </div>

    <!-- Stores Grid -->
    <div class="stores-grid">
        <?php foreach ($pharmacies as $ph): ?>
            <?php 
            $is_current = ($ph['pharmacy_id'] == $current_pharmacy_id);
            $p_cnt = (int)($ph['product_count'] ?? 0);
            $c_cnt = (int)($ph['category_count'] ?? 0);
            ?>
            <div class="store-card <?php echo $is_current ? 'active-store' : ''; ?>">
                
                <?php if ($is_current): ?>
                    <span class="active-badge-pill"><i class='bx bx-check'></i> Currently Active</span>
                <?php endif; ?>

                <div class="store-icon-wrap">
                    <i class='bx bx-plus-medical'></i>
                </div>

                <h2 class="store-title"><?php echo htmlspecialchars($ph['name']); ?></h2>
                
                <div class="store-address">
                    <i class='bx bx-map-pin' style="color: #059669; font-size: 16px;"></i>
                    <span><?php echo htmlspecialchars($ph['address']); ?></span>
                </div>

                <!-- Meta Details -->
                <div class="store-meta-grid">
                    <div class="store-meta-item">
                        <strong>Delivery Fee</strong>
                        <span>रु. <?php echo number_format($ph['delivery_fee'], 2); ?></span>
                    </div>
                    <div class="store-meta-item">
                        <strong>Catalog Size</strong>
                        <span><?php echo $p_cnt; ?> Products</span>
                    </div>
                    <div class="store-meta-item" style="grid-column: 1 / -1;">
                        <strong>Business Hours</strong>
                        <span><i class='bx bx-time-five'></i> <?php echo htmlspecialchars(!empty($ph['business_hours']) ? $ph['business_hours'] : 'Daily: 8:00 AM - 9:00 PM'); ?></span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="store-footer-actions">
                    <a href="index.php?pharmacy=<?php echo $ph['pharmacy_id']; ?>" class="btn btn-primary" style="flex: 1; justify-content: center; height: 42px; font-weight: 700; font-size: 13.5px;">
                        <?php echo $is_current ? "<i class='bx bx-store'></i> View Storefront" : "<i class='bx bx-right-arrow-alt'></i> Enter Store"; ?>
                    </a>
                    <a href="search_products.php?pharmacy=<?php echo $ph['pharmacy_id']; ?>" class="btn btn-outline" style="padding: 0 14px; height: 42px; font-size: 13px; display: inline-flex; align-items: center;" title="Browse Catalog">
                        <i class='bx bx-category'></i> Catalog
                    </a>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<?php include('footer.php'); ?>
