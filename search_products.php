<?php 
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = get_db_connection();

// Read filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$selected_cat_id = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : 0;
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$selected_brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$stock_only = isset($_GET['stock']) && $_GET['stock'] == '1' ? 1 : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';

// Helper to fetch all child/sub-subcategory IDs recursively
function get_all_child_category_ids($conn, $parent_id) {
    $ids = [(int)$parent_id];
    $stmt = $conn->prepare("SELECT cat_id FROM tbl_categories WHERE parent_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $sub_ids = get_all_child_category_ids($conn, $r['cat_id']);
                $ids = array_merge($ids, $sub_ids);
            }
        }
        $stmt->close();
    }
    return array_unique($ids);
}

// Build SQL Query
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search)) {
    $where_clauses[] = "p.prdct_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

if ($selected_cat_id > 0) {
    $cat_ids = get_all_child_category_ids($conn, $selected_cat_id);
    $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
    $where_clauses[] = "p.cat_id IN ($placeholders)";
    foreach ($cat_ids as $cid) {
        $params[] = $cid;
        $types .= "i";
    }
}

if ($min_price > 0) {
    $where_clauses[] = "p.prdct_price >= ?";
    $params[] = $min_price;
    $types .= "d";
}

if ($max_price > 0) {
    $where_clauses[] = "p.prdct_price <= ?";
    $params[] = $max_price;
    $types .= "d";
}

if (!empty($selected_brand)) {
    $where_clauses[] = "p.prdct_company = ?";
    $params[] = $selected_brand;
    $types .= "s";
}

if ($stock_only === 1) {
    $where_clauses[] = "p.stock_quantity > 0";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

$order_sql = " ORDER BY p.prdct_id DESC";
if ($sort === 'price_asc') {
    $order_sql = " ORDER BY p.prdct_price ASC";
} elseif ($sort === 'price_desc') {
    $order_sql = " ORDER BY p.prdct_price DESC";
} elseif ($sort === 'name_asc') {
    $order_sql = " ORDER BY p.prdct_name ASC";
}

$query = "SELECT p.*, c.cat_name FROM tbl_products p LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id" . $where_sql . $order_sql;

$products = [];
if (count($params) > 0) {
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $products[] = $r;
        }
        $stmt->close();
    }
} else {
    $res = $conn->query($query);
    if ($res) {
        while ($r = $res->fetch_assoc()) $products[] = $r;
    }
}

// Fetch active category tree for sidebar filter
function fetch_sidebar_category_tree($conn, $parent_id = 0, $depth = 0) {
    $tree = [];
    $stmt = $conn->prepare("SELECT c.*, (SELECT COUNT(*) FROM tbl_products p WHERE p.cat_id = c.cat_id) AS prod_count FROM tbl_categories c WHERE c.parent_id = ? AND c.cat_status = 1 ORDER BY c.cat_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['depth'] = $depth;
                $tree[] = $row;
                $subs = fetch_sidebar_category_tree($conn, $row['cat_id'], $depth + 1);
                $tree = array_merge($tree, $subs);
            }
        }
        $stmt->close();
    }
    return $tree;
}

$category_tree = fetch_sidebar_category_tree($conn, 0, 0);

// Fetch selected category name for display badge
$selected_cat_name = "";
if ($selected_cat_id > 0) {
    $c_stmt = $conn->prepare("SELECT cat_name FROM tbl_categories WHERE cat_id = ?");
    if ($c_stmt) {
        $c_stmt->bind_param("i", $selected_cat_id);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result();
        if ($c_res && $c_res->num_rows > 0) {
            $selected_cat_name = $c_res->fetch_assoc()['cat_name'];
        }
        $c_stmt->close();
    }
}

// Fetch Brands for sidebar filter
$brands_list = [];
$b_res = $conn->query("SELECT prdct_company, COUNT(*) AS cnt FROM tbl_products WHERE prdct_company IS NOT NULL AND TRIM(prdct_company) != '' GROUP BY prdct_company ORDER BY prdct_company ASC");
if ($b_res && $b_res->num_rows > 0) {
    while ($b = $b_res->fetch_assoc()) $brands_list[] = $b;
}

$page_title = "Pharmacy Product Catalog";
$page_css = "css/products.css";
include('header.php');
?>

<main class="content-container" style="padding: 30px 24px; min-height: 70vh;">
    
    <!-- Page Breadcrumb & Header -->
    <div style="margin-bottom: 20px;">
        <h1 class="section-title" style="margin-bottom: 6px; text-align: left;">
            <i class="bx bx-store-alt" style="color: var(--primary);"></i> Pharmacy Product Catalog
        </h1>
        <p style="color: #64748b; font-size: 14px;">Browse certified medicines, clinical equipment, and healthcare supplements with precision filters.</p>
    </div>

    <!-- Active Filter Badges Bar -->
    <?php 
    $has_active_filters = !empty($search) || $selected_cat_id > 0 || $min_price > 0 || $max_price > 0 || !empty($selected_brand) || $stock_only === 1; 
    ?>
    
    <?php if ($has_active_filters): ?>
        <div class="active-filter-tags">
            <span style="font-size: 13px; font-weight: 600; color: #475569; margin-right: 4px;">Active Filters:</span>
            
            <?php if (!empty($search)): ?>
                <span class="filter-badge">
                    Search: "<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                    <a href="<?php echo build_filter_url(['search' => '']); ?>"><i class="bx bx-x"></i></a>
                </span>
            <?php endif; ?>

            <?php if (!empty($selected_cat_name)): ?>
                <span class="filter-badge">
                    Category: <?php echo htmlspecialchars($selected_cat_name, ENT_QUOTES, 'UTF-8'); ?>
                    <a href="<?php echo build_filter_url(['cat' => '']); ?>"><i class="bx bx-x"></i></a>
                </span>
            <?php endif; ?>

            <?php if ($min_price > 0 || $max_price > 0): ?>
                <span class="filter-badge">
                    Price: रु. <?php echo $min_price; ?> - <?php echo $max_price > 0 ? 'रु. ' . $max_price : 'Any'; ?>
                    <a href="<?php echo build_filter_url(['min_price' => '', 'max_price' => '']); ?>"><i class="bx bx-x"></i></a>
                </span>
            <?php endif; ?>

            <?php if (!empty($selected_brand)): ?>
                <span class="filter-badge">
                    Brand: <?php echo htmlspecialchars($selected_brand, ENT_QUOTES, 'UTF-8'); ?>
                    <a href="<?php echo build_filter_url(['brand' => '']); ?>"><i class="bx bx-x"></i></a>
                </span>
            <?php endif; ?>

            <?php if ($stock_only === 1): ?>
                <span class="filter-badge">
                    In Stock Only
                    <a href="<?php echo build_filter_url(['stock' => '']); ?>"><i class="bx bx-x"></i></a>
                </span>
            <?php endif; ?>

            <a href="search_products.php" style="font-size: 12px; font-weight: 600; color: #dc2626; text-decoration: none; margin-left: 8px;">
                <i class="bx bx-refresh"></i> Clear All
            </a>
        </div>
    <?php endif; ?>

    <!-- Main Shop Layout (2 Columns: Left Filter Sidebar + Right Product Grid) -->
    <div class="shop-layout-grid">
        
        <!-- Left Multi-Filter Sidebar -->
        <aside class="filter-sidebar-card">
            
            <!-- Search Filter Box -->
            <div>
                <div class="filter-group-header">
                    <h4><i class="bx bx-search"></i> Search Keyword</h4>
                </div>
                <form action="search_products.php" method="GET" style="margin-top: 10px;">
                    <div style="position: relative;">
                        <input type="text" 
                               name="search" 
                               class="price-input-box" 
                               placeholder="e.g. Paracetamol..." 
                               value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                               style="width: 100%; padding-right: 36px;">
                        <button type="submit" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); border: none; background: transparent; color: #059669; font-size: 18px; cursor: pointer;">
                            <i class="bx bx-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Categories & Subcategories Tree Filter -->
            <div>
                <div class="filter-group-header">
                    <h4><i class="bx bx-category-alt"></i> Categories & Subcategories</h4>
                </div>
                <ul class="filter-tree-list">
                    <li class="filter-tree-item <?php echo $selected_cat_id == 0 ? 'active' : ''; ?>">
                        <a href="<?php echo build_filter_url(['cat' => '']); ?>">
                            <span>All Categories</span>
                        </a>
                    </li>
                    <?php foreach ($category_tree as $ct): ?>
                        <?php 
                        $depth = (int)$ct['depth'];
                        $is_active = ($selected_cat_id == $ct['cat_id']);
                        $padding_left = 10 + ($depth * 14);
                        $prefix = $depth > 0 ? "└─ " : "";
                        ?>
                        <li class="filter-tree-item <?php echo $is_active ? 'active' : ''; ?>">
                            <a href="<?php echo build_filter_url(['cat' => $ct['cat_id']]); ?>" style="padding-left: <?php echo $padding_left; ?>px;">
                                <span style="<?php echo $depth === 0 ? 'font-weight: 700; color: #0f172a;' : 'font-size: 12.5px;'; ?>">
                                    <?php echo $prefix . htmlspecialchars($ct['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if ($ct['prod_count'] > 0): ?>
                                    <span class="filter-count-badge"><?php echo $ct['prod_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Price Range Filter & Presets -->
            <div>
                <div class="filter-group-header">
                    <h4><i class="bx bx-wallet"></i> Price Range (रु.)</h4>
                </div>
                <form action="search_products.php" method="GET">
                    <?php echo keep_hidden_inputs(['min_price', 'max_price']); ?>
                    <div class="price-input-row">
                        <input type="number" name="min_price" class="price-input-box" placeholder="Min रु." value="<?php echo $min_price > 0 ? $min_price : ''; ?>" min="0">
                        <span style="color: #94a3b8; font-weight: 600; font-size: 14px;">-</span>
                        <input type="number" name="max_price" class="price-input-box" placeholder="Max रु." value="<?php echo $max_price > 0 ? $max_price : ''; ?>" min="0">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px; height: 38px; font-size: 13px; font-weight: 600; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; box-shadow: 0 3px 10px rgba(5, 150, 105, 0.2);">
                        <i class="bx bx-filter"></i> Apply Price
                    </button>
                </form>

                <div class="price-preset-group">
                    <a href="<?php echo build_filter_url(['min_price' => '', 'max_price' => '200']); ?>" class="price-preset-btn <?php echo ($max_price == 200 && $min_price == 0) ? 'active' : ''; ?>">Under रु. 200</a>
                    <a href="<?php echo build_filter_url(['min_price' => '200', 'max_price' => '500']); ?>" class="price-preset-btn <?php echo ($min_price == 200 && $max_price == 500) ? 'active' : ''; ?>">रु. 200 - 500</a>
                    <a href="<?php echo build_filter_url(['min_price' => '500', 'max_price' => '1000']); ?>" class="price-preset-btn <?php echo ($min_price == 500 && $max_price == 1000) ? 'active' : ''; ?>">रु. 500 - 1000</a>
                    <a href="<?php echo build_filter_url(['min_price' => '1000', 'max_price' => '']); ?>" class="price-preset-btn <?php echo ($min_price == 1000 && $max_price == 0) ? 'active' : ''; ?>">Above रु. 1000</a>
                </div>
            </div>

            <!-- Manufacturer / Brand Filter -->
            <?php if (!empty($brands_list)): ?>
                <div>
                    <div class="filter-group-header">
                        <h4><i class="bx bx-certification"></i> Manufacturer Brand</h4>
                    </div>
                    <ul class="filter-tree-list" style="max-height: 180px;">
                        <li class="filter-tree-item <?php echo empty($selected_brand) ? 'active' : ''; ?>">
                            <a href="<?php echo build_filter_url(['brand' => '']); ?>">All Brands</a>
                        </li>
                        <?php foreach ($brands_list as $b): ?>
                            <?php $is_b_active = ($selected_brand === $b['prdct_company']); ?>
                            <li class="filter-tree-item <?php echo $is_b_active ? 'active' : ''; ?>">
                                <a href="<?php echo build_filter_url(['brand' => $b['prdct_company']]); ?>">
                                    <span><?php echo htmlspecialchars($b['prdct_company'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="filter-count-badge"><?php echo $b['cnt']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Availability & Reset -->
            <div>
                <div class="filter-group-header">
                    <h4><i class="bx bx-check-shield"></i> Stock Availability</h4>
                </div>
                <div style="margin-top: 10px;">
                    <a href="<?php echo build_filter_url(['stock' => ($stock_only ? '' : '1')]); ?>" style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #0f172a; text-decoration: none; font-weight: 500;">
                        <input type="checkbox" <?php echo $stock_only === 1 ? 'checked' : ''; ?> style="width: 16px; height: 16px; accent-color: #059669;">
                        <span>In Stock Items Only</span>
                    </a>
                </div>
            </div>

            <!-- Clear All Reset -->
            <a href="search_products.php" class="btn btn-outline" style="width: 100%; border-color: #cbd5e1; color: #475569; justify-content: center; height: 38px;">
                <i class="bx bx-refresh"></i> Reset All Filters
            </a>

        </aside>

        <!-- Right Main Catalog Content Area -->
        <section>
            
            <!-- Catalog Top Toolbar Bar -->
            <div class="catalog-toolbar">
                <div class="catalog-counter-text">
                    Showing <strong><?php echo count($products); ?></strong> Products
                </div>

                <!-- Sorting Dropdown -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="sortSelect" style="font-size: 13px; font-weight: 600; color: #64748b;">Sort By:</label>
                    <select id="sortSelect" onchange="location = this.value;" class="price-input-box" style="width: 180px;">
                        <option value="<?php echo build_filter_url(['sort' => 'newest']); ?>" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
                        <option value="<?php echo build_filter_url(['sort' => 'price_asc']); ?>" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="<?php echo build_filter_url(['sort' => 'price_desc']); ?>" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="<?php echo build_filter_url(['sort' => 'name_asc']); ?>" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                    </select>
                </div>
            </div>

            <!-- Product Cards Grid -->
            <div class="product-grid" style="margin-top: 0;">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $row): ?>
                        <?php $p_stock = (int)($row['stock_quantity'] ?? 50); ?>
                        <div class="product-card">
                            <div class="product-img-wrapper">
                                <div class="product-badge-bar">
                                    <?php if ($p_stock > 0): ?>
                                        <span class="product-badge"><i class="bx bx-check-circle"></i> In Stock</span>
                                    <?php else: ?>
                                        <span class="product-badge" style="color: #dc2626; border-color: rgba(220,38,38,0.3); background: #fef2f2;"><i class="bx bx-x-circle"></i> Out of Stock</span>
                                    <?php endif; ?>
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
                                    <?php if ($p_stock > 0): ?>
                                        <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-disabled" disabled><i class="bx bx-x-circle"></i> Out of Stock</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 60px 20px; text-align: center;">
                        <i class="bx bx-search-alt" style="font-size: 54px; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                        <h4 style="font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">No Products Found</h4>
                        <p style="font-size: 14px; color: #64748b; max-width: 420px; margin: 0 auto 20px;">No products match your selected filters. Try adjusting your category, price range, or brand criteria.</p>
                        <a href="search_products.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px;">
                            <i class="bx bx-refresh"></i> Clear All Filters
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        </section>

    </div>

</main>

<?php 
// Helper functions for building filter URLs
function build_filter_url($override_params = []) {
    $params = $_GET;
    foreach ($override_params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return 'search_products.php?' . http_build_query($params);
}

function keep_hidden_inputs($exclude_keys = []) {
    $html = "";
    foreach ($_GET as $k => $v) {
        if (!in_array($k, $exclude_keys) && !empty($v)) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '">';
        }
    }
    return $html;
}

include('footer.php'); 
?>
