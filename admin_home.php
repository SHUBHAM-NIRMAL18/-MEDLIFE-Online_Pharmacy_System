<?php 
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();

// Fetch summary metrics
$totalProducts = 0;
$totalCategories = 0;
$totalOrders = 0;
$pendingOrders = 0;
$totalCustomers = 0;
$totalRevenue = 0.00;

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_products");
if ($res) { $totalProducts = (int)$res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_categories");
if ($res) { $totalCategories = (int)$res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_order");
if ($res) { $totalOrders = (int)$res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_order WHERE status = 0");
if ($res) { $pendingOrders = (int)$res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT COUNT(*) AS cnt FROM tbl_user");
if ($res) { $totalCustomers = (int)$res->fetch_assoc()['cnt']; }

$res = $conn->query("SELECT SUM(total) AS rev FROM tbl_order WHERE status != 2");
if ($res) { 
    $row = $res->fetch_assoc();
    $totalRevenue = !empty($row['rev']) ? (float)$row['rev'] : 0.00; 
}

// Fetch Top Brands / Manufacturers
$topBrands = [];
$maxBrandCount = 1;
$brandRes = $conn->query("SELECT prdct_company, COUNT(*) AS cnt 
                          FROM tbl_products 
                          WHERE prdct_company IS NOT NULL AND TRIM(prdct_company) != '' 
                          GROUP BY prdct_company 
                          ORDER BY cnt DESC 
                          LIMIT 5");
if ($brandRes && $brandRes->num_rows > 0) {
    while ($b = $brandRes->fetch_assoc()) {
        $topBrands[] = $b;
    }
    if (!empty($topBrands)) {
        $maxBrandCount = max(1, (int)$topBrands[0]['cnt']);
    }
}

// Fetch Recent Products
$recentProducts = [];
$prodRes = $conn->query("SELECT p.*, c.cat_name 
                         FROM tbl_products p 
                         LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id 
                         ORDER BY p.prdct_id DESC 
                         LIMIT 4");
if ($prodRes && $prodRes->num_rows > 0) {
    while ($p = $prodRes->fetch_assoc()) {
        $recentProducts[] = $p;
    }
}

// Fetch Recent Orders
$recentOrders = [];
$orderRes = $conn->query("SELECT * FROM tbl_order ORDER BY order_id DESC LIMIT 5");
if ($orderRes && $orderRes->num_rows > 0) {
    while ($o = $orderRes->fetch_assoc()) {
        $recentOrders[] = $o;
    }
}
?>

<div class="admin-page-wrapper">

    <!-- Page Header -->
    <div class="admin-page-header">
        <div>
            <h1><i class="bx bx-grid-alt" style="color: var(--admin-accent, #059669);"></i> Pharmacy Analytics Overview</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?></strong>. Here is your real-time store performance and inventory analysis.</p>
        </div>
    </div>

    <!-- 4 Key Stat Summary Cards (Aligned Grid) -->
    <div class="stats-grid">

        <!-- Revenue Card -->
        <div class="stat-card">
            <div class="stat-card-icon revenue">
                <i class="bx bx-wallet"></i>
            </div>
            <div class="stat-card-info">
                <h3>Rs. <?php echo number_format($totalRevenue, 2); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>

        <!-- Total Orders Card -->
        <div class="stat-card">
            <div class="stat-card-icon orders">
                <i class="bx bx-receipt"></i>
            </div>
            <div class="stat-card-info">
                <h3><?php echo $totalOrders; ?></h3>
                <p><?php echo $pendingOrders; ?> Pending Orders</p>
            </div>
        </div>

        <!-- Total Products Card -->
        <div class="stat-card">
            <div class="stat-card-icon products">
                <i class="bx bx-package"></i>
            </div>
            <div class="stat-card-info">
                <h3><?php echo $totalProducts; ?></h3>
                <p><?php echo $totalCategories; ?> Active Categories</p>
            </div>
        </div>

        <!-- Registered Customers Card -->
        <div class="stat-card">
            <div class="stat-card-icon customers">
                <i class="bx bx-group"></i>
            </div>
            <div class="stat-card-info">
                <h3><?php echo $totalCustomers; ?></h3>
                <p>Registered Customers</p>
            </div>
        </div>

    </div>

    <!-- Main Dashboard 2-Column Grid (Aligned Left & Right Boxes) -->
    <div class="dashboard-layout-grid">

        <!-- Left Column: Recent Orders & Recent Products -->
        <div class="dashboard-left-col">

            <!-- Recent Orders Card -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="bx bx-shopping-bag" style="color: #059669; margin-right: 6px;"></i> Recent Orders</h3>
                    <a href="admin_order.php" class="admin-btn view">
                        View All Orders <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Tracking No</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th style="text-align: center;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentOrders)): ?>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td style="font-weight: 700; color: #0f172a;">#<?php echo $order['order_id']; ?></td>
                                        <td style="font-family: monospace; font-size: 13px; font-weight: 600; color: #475569;">
                                            <?php echo htmlspecialchars($order['tracking_order'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a;">
                                                <?php echo htmlspecialchars($order['user_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </td>
                                        <td style="font-size: 12.5px; color: #64748b;">
                                            <?php echo !empty($order['created_at']) ? date("M d, Y", strtotime($order['created_at'])) : date("M d, Y"); ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 700; color: #059669;">
                                                Rs. <?php echo number_format($order['total'], 2); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php
                                            if ($order['status'] == 0) {
                                                echo '<span class="admin-badge process"><i class="bx bx-loader-alt"></i> Processing</span>';
                                            } elseif ($order['status'] == 1) {
                                                echo '<span class="admin-badge completed"><i class="bx bx-check"></i> Completed</span>';
                                            } elseif ($order['status'] == 2) {
                                                echo '<span class="admin-badge cancelled"><i class="bx bx-x"></i> Cancelled</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">
                                        No recent orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recently Added Catalog Products -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><i class="bx bx-capsule" style="color: #059669; margin-right: 6px;"></i> Recently Added Products</h3>
                    <a href="view_products.php" class="admin-btn view">
                        Manage Catalog <i class="bx bx-right-arrow-alt"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Thumb</th>
                                <th>Product Details</th>
                                <th>Manufacturer</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentProducts)): ?>
                                <?php foreach ($recentProducts as $p): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $img_src = !empty($p['prdct_img']) && file_exists('medimg/' . $p['prdct_img']) 
                                                ? 'medimg/' . htmlspecialchars($p['prdct_img'], ENT_QUOTES, 'UTF-8') 
                                                : 'medimg/default.png';
                                            ?>
                                            <img src="<?php echo $img_src; ?>" 
                                                 alt="<?php echo htmlspecialchars($p['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                 style="width: 36px; height: 36px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0;">
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: #0f172a;">
                                                <?php echo htmlspecialchars($p['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <?php if (!empty($p['cat_name'])): ?>
                                                <small style="color: #64748b; font-size: 11.5px;"><?php echo htmlspecialchars($p['cat_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #475569; font-weight: 500;">
                                            <?php echo htmlspecialchars($p['prdct_company'], ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <strong style="color: #0f172a;">Rs. <?php echo number_format($p['prdct_price'], 2); ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 24px; color: #64748b;">
                                        No products in catalog.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Right Column: Top Brands Analysis & Quick Management Actions -->
        <div class="dashboard-right-col">

            <!-- Top Manufacturers / Brands Analysis Widget -->
            <div class="admin-card" style="padding: 20px 22px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="bx bx-award" style="color: #059669; font-size: 20px;"></i> Top Brands & Suppliers
                    </h3>
                    <span style="font-size: 11px; font-weight: 600; color: #64748b; background: #f8fafc; padding: 2px 8px; border-radius: 10px; border: 1px solid #e2e8f0;">Catalog Share</span>
                </div>

                <?php if (!empty($topBrands)): ?>
                    <div class="top-brands-list">
                        <?php foreach ($topBrands as $brand): ?>
                            <?php 
                            $cnt = (int)$brand['cnt'];
                            $pct = round(($cnt / $maxBrandCount) * 100);
                            $bName = htmlspecialchars($brand['prdct_company'], ENT_QUOTES, 'UTF-8');
                            $firstLetter = strtoupper(substr($bName, 0, 1));
                            ?>
                            <div class="brand-item">
                                <div class="brand-avatar-circle">
                                    <?php echo $firstLetter; ?>
                                </div>
                                <div class="brand-info">
                                    <div class="brand-name-row">
                                        <span class="brand-name"><?php echo $bName; ?></span>
                                        <span class="brand-count-pill"><?php echo $cnt; ?> <?php echo $cnt === 1 ? 'item' : 'items'; ?></span>
                                    </div>
                                    <div class="brand-progress-bg">
                                        <div class="brand-progress-fill" style="width: <?php echo $pct; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px;">
                        No manufacturer data available yet.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Management Action Shortcuts -->
            <div class="admin-card" style="padding: 20px 22px;">
                <div style="margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                        <i class="bx bx-rocket" style="color: #059669; font-size: 20px;"></i> Quick Operations
                    </h3>
                </div>

                <div class="quick-action-cards-grid">
                    <a href="products.php" class="quick-action-card">
                        <i class="bx bx-plus-circle"></i>
                        <div class="quick-action-text-wrapper">
                            <div class="quick-action-title">Add Product</div>
                            <div class="quick-action-subtitle">New Medicine</div>
                        </div>
                    </a>

                    <a href="categories.php" class="quick-action-card">
                        <i class="bx bx-category-alt"></i>
                        <div class="quick-action-text-wrapper">
                            <div class="quick-action-title">Add Category</div>
                            <div class="quick-action-subtitle">New Category</div>
                        </div>
                    </a>

                    <a href="admin_register1.php" class="quick-action-card">
                        <i class="bx bx-user-plus"></i>
                        <div class="quick-action-text-wrapper">
                            <div class="quick-action-title">Add Staff</div>
                            <div class="quick-action-subtitle">Admin/Manager</div>
                        </div>
                    </a>

                    <a href="admin_order.php" class="quick-action-card">
                        <i class="bx bx-list-check"></i>
                        <div class="quick-action-text-wrapper">
                            <div class="quick-action-title">View Orders</div>
                            <div class="quick-action-subtitle">Check Orders</div>
                        </div>
                    </a>
                </div>
            </div>

        </div>

    </div>

</div>

</main>
</body>
</html>
