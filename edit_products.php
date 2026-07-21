<?php 
require_once 'config.php';

if (!isset($_GET['prdct_id']) || !is_numeric($_GET['prdct_id'])) {
    header("Location: view_products.php?msg=1");
    exit();
}

$id = (int)$_GET['prdct_id'];
$err = [];
$conn = get_db_connection();

// Process Product Update BEFORE rendering HTML / including dashboard.php
if (isset($_POST['btnUpdate'])) {
    $prdct_name = isset($_POST['prdct_name']) ? trim($_POST['prdct_name']) : '';
    $prdct_company = isset($_POST['prdct_company']) ? trim($_POST['prdct_company']) : '';
    $prdct_price = isset($_POST['prdct_price']) ? (float)$_POST['prdct_price'] : 0;
    $manf_date = isset($_POST['manf_date']) ? trim($_POST['manf_date']) : '';
    $exp_date = isset($_POST['exp_date']) ? trim($_POST['exp_date']) : '';
    $cat_id = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
    $old_image = isset($_POST['old_image']) ? trim($_POST['old_image']) : '';
    $stock_quantity = isset($_POST['stock_quantity']) ? max(0, (int)$_POST['stock_quantity']) : 50;

    if (empty($prdct_name)) {
        $err['prdct_name'] = 'Please enter the product name';
    }
    if (empty($prdct_company)) {
        $err['prdct_company'] = 'Please enter the manufacturer/company';
    }
    if ($prdct_price <= 0) {
        $err['prdct_price'] = 'Please enter a valid price greater than 0';
    }
    if (empty($manf_date)) {
        $err['manf_date'] = 'Please select manufactured date';
    }
    if (empty($exp_date)) {
        $err['exp_date'] = 'Please select expiry date';
    }
    if ($cat_id <= 0) {
        $err['cat_id'] = 'Please select a category';
    }

    // New Image Upload Check
    $image_filename = $old_image;
    if (isset($_FILES['new_image']) && !empty($_FILES['new_image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['new_image']['type'];

        if (in_array(strtolower($file_type), $allowed_types)) {
            $raw_name = basename($_FILES['new_image']['name']);
            $ext = pathinfo($raw_name, PATHINFO_EXTENSION);
            $image_filename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

            $target_dir = "medimg/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_path = $target_dir . $image_filename;

            if (!move_uploaded_file($_FILES['new_image']['tmp_name'], $target_path)) {
                $err['image'] = 'Failed to upload new image file.';
            }
        } else {
            $err['image'] = 'Invalid image format. Allowed: JPG, PNG, WEBP';
        }
    }

    if (count($err) === 0) {
        $stmt = $conn->prepare("UPDATE tbl_products SET prdct_name = ?, prdct_company = ?, prdct_price = ?, manf_date = ?, exp_date = ?, cat_id = ?, prdct_img = ?, stock_quantity = ? WHERE prdct_id = ?");
        if ($stmt) {
            $stmt->bind_param("ssdssisii", $prdct_name, $prdct_company, $prdct_price, $manf_date, $exp_date, $cat_id, $image_filename, $stock_quantity, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: view_products.php?updated=1");
            exit();
        } else {
            $err['general'] = "Failed to prepare database update statement.";
        }
    }
}

// Fetch Product Details
$product = null;
$stmt = $conn->prepare("SELECT p.*, c.cat_name FROM tbl_products p LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id WHERE p.prdct_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $product = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    header("Location: view_products.php?msg=1");
    exit();
}

// Helper to fetch category options with indentation
function get_category_options($conn, $parent = 0, $indent = "", $selected = 0) {
    $html = "";
    $stmt = $conn->prepare("SELECT * FROM tbl_categories WHERE parent_id = ? ORDER BY cat_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $parent);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $is_sel = ($row['cat_id'] == $selected) ? "selected" : "";
                $prefix = !empty($indent) ? $indent . "└─ " : "";
                $html .= '<option value="' . $row['cat_id'] . '" ' . $is_sel . '>' . $prefix . htmlspecialchars($row['cat_name'], ENT_QUOTES, 'UTF-8') . '</option>';
                $html .= get_category_options($conn, $row['cat_id'], $indent . "&nbsp;&nbsp;&nbsp;&nbsp;", $selected);
            }
        }
        $stmt->close();
    }
    return $html;
}

// NOW include dashboard HTML
include_once 'dashboard.php';
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
        <a href="view_products.php" class="breadcrumb-item">Products</a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item active">Edit Product</span>
    </nav>

    <!-- Page Header -->
    <div class="product-page-header">
        <div>
            <h1><i class="bx bx-edit" style="color: var(--admin-accent, #059669);"></i> Edit Product</h1>
            <p>Update specifications, pricing, inventory stock, and packaging image for Product #<?php echo $id; ?>.</p>
        </div>
        <a href="view_products.php" class="btn-secondary">
            <i class="bx bx-arrow-back"></i> Back to Products
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="product-grid">

        <!-- Left Column: Form Card -->
        <div class="product-card">
            <div class="product-card-header">
                <h3><i class="bx bx-edit-alt"></i> Update Product Specifications</h3>
            </div>

            <?php if (isset($err['general'])): ?>
                <div class="alert-banner error">
                    <i class="bx bx-error-circle"></i>
                    <span><?php echo htmlspecialchars($err['general'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="edit_products.php?prdct_id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" name="old_image" value="<?php echo htmlspecialchars($product['prdct_img'], ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Product Name (Full Width) -->
                <div class="form-group">
                    <label for="prdct_name" class="form-label">
                        Product Name <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-capsule"></i>
                        <input type="text" 
                               id="prdct_name" 
                               name="prdct_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($product['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                    <?php if (isset($err['prdct_name'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Row 1: Company & Category -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="prdct_company" class="form-label">
                            Manufacturer / Company <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-building"></i>
                            <input type="text" 
                                   id="prdct_company" 
                                   name="prdct_company" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($product['prdct_company'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                        <?php if (isset($err['prdct_company'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['prdct_company'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="cat_id" class="form-label">
                            Category Type <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-category"></i>
                            <select id="cat_id" name="cat_id" class="form-select" required>
                                <option value="" disabled>Select Category</option>
                                <?php echo get_category_options($conn, 0, "", $product['cat_id']); ?>
                            </select>
                        </div>
                        <?php if (isset($err['cat_id'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['cat_id'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 2: Price & Stock Quantity -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="prdct_price" class="form-label">
                            Price (रु.) <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-purchase-tag"></i>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   id="prdct_price" 
                                   name="prdct_price" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($product['prdct_price'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                        <?php if (isset($err['prdct_price'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['prdct_price'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="stock_quantity" class="form-label">
                            Stock Quantity (Units) <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-layer"></i>
                            <input type="number" 
                                   min="0" 
                                   id="stock_quantity" 
                                   name="stock_quantity" 
                                   class="form-control" 
                                   value="<?php echo isset($product['stock_quantity']) ? (int)$product['stock_quantity'] : 50; ?>"
                                   required>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Manufactured Date & Expiry Date -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="manf_date" class="form-label">
                            Manufactured Date <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-calendar"></i>
                            <input type="date" 
                                   id="manf_date" 
                                   name="manf_date" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($product['manf_date'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                        <?php if (isset($err['manf_date'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['manf_date'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="exp_date" class="form-label">
                            Expiry Date <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-calendar-event"></i>
                            <input type="date" 
                                   id="exp_date" 
                                   name="exp_date" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($product['exp_date'], ENT_QUOTES, 'UTF-8'); ?>"
                                   required>
                        </div>
                        <?php if (isset($err['exp_date'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['exp_date'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Row 4: Replace Product Image (Full Width) -->
                <div class="form-group">
                    <label class="form-label">
                        Replace Product Image (Optional)
                    </label>
                    <div class="file-upload-box">
                        <i class="bx bx-cloud-upload file-upload-icon"></i>
                        <div class="file-upload-text" id="fileNameDisplay">Choose New Image</div>
                        <div class="file-upload-hint">Leave blank to keep existing image</div>
                        <input type="file" 
                               name="new_image" 
                               id="newImageInput" 
                               accept="image/*"
                               onchange="updateFileName(this)">
                    </div>
                    <?php if (isset($err['image'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['image'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnUpdate" class="btn-submit">
                        <i class="bx bx-check-circle"></i> Save Changes
                    </button>
                    <a href="view_products.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Right Column: Current Image Preview & Info -->
        <div class="product-card product-tips-card">
            <h4><i class="bx bx-image"></i> Current Product Image</h4>
            
            <div class="img-preview-card">
                <?php 
                $curr_img = !empty($product['prdct_img']) && file_exists('medimg/' . $product['prdct_img'])
                    ? 'medimg/' . htmlspecialchars($product['prdct_img'], ENT_QUOTES, 'UTF-8')
                    : 'medimg/default.png';
                ?>
                <img src="<?php echo $curr_img; ?>" 
                     alt="Current Product Image" 
                     class="img-preview-thumb">
                <div style="font-size: 13px; font-weight: 600; color: #0f172a;">
                    <?php echo htmlspecialchars($product['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Price: <strong>रु. <?php echo number_format($product['prdct_price'], 2); ?></strong> • Stock: <strong><?php echo (int)($product['stock_quantity'] ?? 50); ?> units</strong>
                </div>
            </div>

            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Product ID:</strong> #<?php echo $id; ?>
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Current Category:</strong> 
                        <span class="category-tag"><?php echo htmlspecialchars($product['cat_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </li>
            </ul>
        </div>

    </div>

</div>

<script>
    function updateFileName(input) {
        var display = document.getElementById('fileNameDisplay');
        if (input.files && input.files[0]) {
            display.textContent = input.files[0].name;
        } else {
            display.textContent = 'Choose New Image';
        }
    }
</script>