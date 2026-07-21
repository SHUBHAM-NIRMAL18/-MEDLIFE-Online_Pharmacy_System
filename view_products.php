<?php 
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();

// Category, Stock Filter & Pagination Settings
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$stock_filter = isset($_GET['stock']) ? trim($_GET['stock']) : '';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 8; // Items per page
$offset = ($page - 1) * $limit;

// Fetch all categories for filter dropdown
$categories = [];
$cat_res = $conn->query("SELECT * FROM tbl_categories ORDER BY cat_name ASC");
if ($cat_res && $cat_res->num_rows > 0) {
    while ($r = $cat_res->fetch_assoc()) {
        $categories[] = $r;
    }
}

// Build dynamic WHERE clause
$where = [];
$count_params = [];
$count_types = "";

if ($cat_filter > 0) {
    $where[] = "p.cat_id = ?";
    $count_params[] = $cat_filter;
    $count_types .= "i";
}

if ($stock_filter === 'low') {
    $where[] = "p.stock_quantity > 0 AND p.stock_quantity <= 10";
} elseif ($stock_filter === 'out') {
    $where[] = "p.stock_quantity <= 0";
}

$where_sql = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Count total matching records
$total_records = 0;
$count_query = "SELECT COUNT(*) AS total FROM tbl_products p" . $where_sql;
if (count($count_params) > 0) {
    $count_stmt = $conn->prepare($count_query);
    if ($count_stmt) {
        $count_stmt->bind_param($count_types, ...$count_params);
        $count_stmt->execute();
        $res = $count_stmt->get_result();
        if ($res) {
            $total_records = (int)$res->fetch_assoc()['total'];
        }
        $count_stmt->close();
    }
} else {
    $count_res = $conn->query($count_query);
    if ($count_res) {
        $total_records = (int)$count_res->fetch_assoc()['total'];
    }
}

$total_pages = max(1, (int)ceil($total_records / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Fetch paginated products
$products = [];
$data_query = "SELECT p.*, c.cat_name FROM tbl_products p LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id" . $where_sql . " ORDER BY p.prdct_id DESC LIMIT ? OFFSET ?";
$data_params = $count_params;
$data_params[] = $limit;
$data_params[] = $offset;
$data_types = $count_types . "ii";

$stmt = $conn->prepare($data_query);
if ($stmt) {
    $stmt->bind_param($data_types, ...$data_params);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $products[] = $row;
        }
    }
    $stmt->close();
}

$start_item = $total_records > 0 ? $offset + 1 : 0;
$end_item = min($offset + $limit, $total_records);
?>

<!-- Include Dedicated Product CSS -->
<link rel="stylesheet" href="css/product.css">

<div class="admin-page-wrapper">

    <!-- Breadcrumb Navigation -->
    <nav class="admin-breadcrumb" aria-label="breadcrumb">
        <a href="admin_home.php" class="breadcrumb-item">
            <i class="bx bx-home-alt"></i> Dashboard
        </a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item">Catalog</span>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item active">Manage Products</span>
    </nav>

    <!-- Page Header -->
    <div class="product-page-header">
        <div>
            <h1><i class="bx bxs-component" style="color: var(--admin-accent, #059669);"></i> Manage Pharmacy Products</h1>
            <p>View, filter, edit, or delete medicines, healthcare products, and medical devices.</p>
        </div>
        <a href="products.php" class="btn-action-primary">
            <i class="bx bx-plus-circle"></i> Add New Product
        </a>
    </div>

    <!-- Notification Banners -->
    <?php if (isset($_GET['action']) && $_GET['action'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>Product deleted successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>Product details updated successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 1): ?>
        <div class="alert-banner error">
            <i class="bx bx-error-circle"></i>
            <span>Invalid Product Request. Please select a valid item.</span>
        </div>
    <?php endif; ?>

    <!-- Product Data Table Card -->
    <div class="product-table-card">
        <div class="table-header-toolbar">
            <h3>
                <i class="bx bx-package"></i> 
                <?php echo ($cat_filter > 0 || !empty($stock_filter)) ? 'Filtered Products' : 'All Products'; ?> 
                (<?php echo $total_records; ?>)
            </h3>

            <!-- Category & Stock Filter Controls -->
            <form action="view_products.php" method="GET" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div class="input-icon-wrapper" style="width: auto;">
                    <i class="bx bx-filter-alt" style="left: 12px; font-size: 16px;"></i>
                    <select name="cat" class="form-select" onchange="this.form.submit()" style="height: 38px; padding-left: 36px; padding-right: 32px; font-size: 13.5px; width: auto;">
                        <option value="0">All Categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['cat_id']; ?>" <?php echo $cat_filter == $c['cat_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-icon-wrapper" style="width: auto;">
                    <i class="bx bx-layer" style="left: 12px; font-size: 16px;"></i>
                    <select name="stock" class="form-select" onchange="this.form.submit()" style="height: 38px; padding-left: 36px; padding-right: 32px; font-size: 13.5px; width: auto;">
                        <option value="">All Stock Levels</option>
                        <option value="low" <?php echo $stock_filter === 'low' ? 'selected' : ''; ?>>⚠️ Low Stock (<= 10)</option>
                        <option value="out" <?php echo $stock_filter === 'out' ? 'selected' : ''; ?>>❌ Out of Stock (0)</option>
                    </select>
                </div>

                <?php if ($cat_filter > 0 || !empty($stock_filter)): ?>
                    <a href="view_products.php" class="btn-secondary" style="height: 38px; padding: 0 14px; font-size: 13px;">
                        <i class="bx bx-reset"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($products)): ?>
            <div class="table-responsive">
                <table class="product-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">SN</th>
                            <th style="width: 70px;">Image</th>
                            <th>Product Info</th>
                            <th>Manufacturer</th>
                            <th>Price</th>
                            <th>Stock Status</th>
                            <th>Mfg Date</th>
                            <th>Exp Date</th>
                            <th style="width: 130px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $index => $p): ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;"><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <?php 
                                    $img_src = !empty($p['prdct_img']) && file_exists('medimg/' . $p['prdct_img']) 
                                        ? 'medimg/' . htmlspecialchars($p['prdct_img'], ENT_QUOTES, 'UTF-8') 
                                        : 'medimg/default.png';
                                    ?>
                                    <img src="<?php echo $img_src; ?>" 
                                         alt="<?php echo htmlspecialchars($p['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         class="product-thumb-cell">
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14.5px;">
                                        <?php echo htmlspecialchars($p['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($p['cat_name'])): ?>
                                        <span class="category-tag">
                                            <i class="bx bx-tag"></i> <?php echo htmlspecialchars($p['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #475569; font-weight: 500;">
                                    <?php echo htmlspecialchars($p['prdct_company'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td>
                                    <span class="price-badge">
                                        रु. <?php echo number_format($p['prdct_price'], 2); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $sq = (int)($p['stock_quantity'] ?? 50);
                                    if ($sq <= 0) {
                                        echo '<span class="status-badge inactive" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"><span class="status-dot" style="background: #dc2626;"></span> Out of Stock (0)</span>';
                                    } elseif ($sq <= 10) {
                                        echo '<span class="status-badge inactive" style="background: #fffbe0; color: #d97706; border: 1px solid #fde68a;"><span class="status-dot" style="background: #d97706;"></span> Low Stock (' . $sq . ')</span>';
                                    } else {
                                        echo '<span class="status-badge active" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"><span class="status-dot" style="background: #10b981;"></span> In Stock (' . $sq . ')</span>';
                                    }
                                    ?>
                                </td>
                                <td style="font-size: 13px; color: #64748b;">
                                    <?php echo !empty($p['manf_date']) ? date("M d, Y", strtotime($p['manf_date'])) : 'N/A'; ?>
                                </td>
                                <td style="font-size: 13px; color: #64748b;">
                                    <?php echo !empty($p['exp_date']) ? date("M d, Y", strtotime($p['exp_date'])) : 'N/A'; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group" style="justify-content: center;">
                                        <a href="edit_products.php?prdct_id=<?php echo (int)$p['prdct_id']; ?>" 
                                           class="action-btn edit" 
                                           title="Edit Product / Restock">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="action-btn delete" 
                                                title="Delete Product"
                                                onclick="confirmDeleteProduct(<?php echo (int)$p['prdct_id']; ?>, '<?php echo htmlspecialchars(addslashes($p['prdct_name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination Bar -->
            <div class="table-footer-toolbar">
                <div class="table-pagination-info">
                    Showing <strong><?php echo $start_item; ?></strong> to <strong><?php echo $end_item; ?></strong> of <strong><?php echo $total_records; ?></strong> products
                </div>

                <?php if ($total_pages > 1): ?>
                    <ul class="admin-pagination">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="view_products.php?cat=<?php echo $cat_filter; ?>&stock=<?php echo $stock_filter; ?>&page=<?php echo $page - 1; ?>" class="page-link" title="Previous Page">
                                    <i class="bx bx-chevron-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <span class="page-link disabled"><i class="bx bx-chevron-left"></i></span>
                            </li>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li>
                                <a href="view_products.php?cat=<?php echo $cat_filter; ?>&stock=<?php echo $stock_filter; ?>&page=<?php echo $i; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="view_products.php?cat=<?php echo $cat_filter; ?>&stock=<?php echo $stock_filter; ?>&page=<?php echo $page + 1; ?>" class="page-link" title="Next Page">
                                    <i class="bx bx-chevron-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li>
                                <span class="page-link disabled"><i class="bx bx-chevron-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="empty-state-box">
                <i class="bx bx-box"></i>
                <h4>No Products Found</h4>
                <p>
                    <?php if ($cat_filter > 0 || !empty($stock_filter)): ?>
                        No products match your selected filters.
                    <?php else: ?>
                        You haven't added any products to your catalog yet. Click below to add your first product.
                    <?php endif; ?>
                </p>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <a href="products.php" class="btn-action-primary">
                        <i class="bx bx-plus-circle"></i> Add Product Now
                    </a>
                    <?php if ($cat_filter > 0 || !empty($stock_filter)): ?>
                        <a href="view_products.php" class="btn-secondary">
                            View All Products
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Delete Product Confirmation Modal Overlay -->
<div class="admin-modal-overlay" id="deleteProductModal">
    <div class="admin-modal-card">
        <div class="admin-modal-icon danger">
            <i class="bx bx-trash"></i>
        </div>
        <h4>Delete Product?</h4>
        <p>Are you sure you want to delete <strong id="deleteProductName"></strong>? This item will be permanently removed from store catalog and customer searches.</p>
        <div class="admin-modal-actions">
            <button class="btn-secondary" onclick="closeDeleteProductModal()" style="height: 42px; font-size: 13.5px;">Cancel</button>
            <a href="#" id="deleteProductConfirmLink" class="btn-action-primary" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); height: 42px; font-size: 13.5px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);">
                Delete Product
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDeleteProduct(prodId, prodName) {
        document.getElementById('deleteProductName').textContent = prodName;
        document.getElementById('deleteProductConfirmLink').href = 'delete_products.php?prdct_id=' + prodId;
        document.getElementById('deleteProductModal').classList.add('show');
    }

    function closeDeleteProductModal() {
        document.getElementById('deleteProductModal').classList.remove('show');
    }

    document.getElementById('deleteProductModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteProductModal();
    });
</script>
