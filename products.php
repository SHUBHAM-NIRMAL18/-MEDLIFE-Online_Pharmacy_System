<?php 
require_once 'config.php';
include_once 'dashboard.php';

$success = '';
$err = [];
$name = $company = $price = $manufactured = $expiry = $cat_id = '';

$conn = get_db_connection();

// Fetch categories for select dropdown
$categories = [];
$cat_res = $conn->query("SELECT * FROM tbl_categories ORDER BY cat_name ASC");
if ($cat_res && $cat_res->num_rows > 0) {
    while ($row = $cat_res->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Process Add Product Submission
if (isset($_POST['btnAdd'])) {
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $name = trim($_POST['name']);
    } else {
        $err['name'] = 'Please enter the product name';
    }

    if (isset($_POST['company']) && !empty(trim($_POST['company']))) {
        $company = trim($_POST['company']);
    } else {
        $err['company'] = 'Please enter the product manufacturer/company';
    }

    if (isset($_POST['price']) && !empty(trim($_POST['price']))) {
        $price = (float)$_POST['price'];
        if ($price <= 0) {
            $err['price'] = 'Please enter a valid price greater than 0';
        }
    } else {
        $err['price'] = 'Please enter the product price';
    }

    if (isset($_POST['manufactured']) && !empty(trim($_POST['manufactured']))) {
        $manufactured = trim($_POST['manufactured']);
        $manu = strtotime($manufactured);
        if ($manu > strtotime('today')) {
            $err['manufactured'] = 'Manufactured date cannot be in the future';
        }
    } else {
        $err['manufactured'] = 'Please enter the manufactured date';
    }

    if (isset($_POST['expiry']) && !empty(trim($_POST['expiry']))) {
        $expiry = trim($_POST['expiry']);
        $exp = strtotime($expiry);
        if (isset($manu) && $exp <= $manu) {
            $err['expiry'] = 'Expiry date must be after manufactured date';
        }
    } else {
        $err['expiry'] = 'Please enter the expiry date';
    }

    if (isset($_POST['category']) && !empty(trim($_POST['category']))) {
        $cat_id = (int)$_POST['category'];
    } else {
        $err['category'] = 'Please select a category type';
    }

    // Image Upload Handling
    $image_filename = '';
    if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (in_array(strtolower($file_type), $allowed_types)) {
            $raw_name = basename($_FILES['image']['name']);
            // Generate clean unique filename
            $ext = pathinfo($raw_name, PATHINFO_EXTENSION);
            $image_filename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            
            $target_dir = "medimg/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $target_path = $target_dir . $image_filename;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $err['image'] = 'Failed to upload image file to server.';
            }
        } else {
            $err['image'] = 'Invalid image format. Allowed: JPG, PNG, WEBP';
        }
    } else {
        $err['image'] = 'Please choose a product image';
    }

    // Insert into Database if no errors
    if (count($err) === 0) {
        $stmt = $conn->prepare("INSERT INTO tbl_products (prdct_name, prdct_company, prdct_price, manf_date, exp_date, prdct_img, cat_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdsssi", $name, $company, $price, $manufactured, $expiry, $image_filename, $cat_id);
            $stmt->execute();
            if ($stmt->affected_rows === 1 && $stmt->insert_id > 0) {
                $success = 'Product added successfully!';
                $name = $company = $price = $manufactured = $expiry = $cat_id = '';
            } else {
                $err['general'] = 'Error saving product into database.';
            }
            $stmt->close();
        } else {
            $err['general'] = 'Database preparation error.';
        }
    }
}

// Count total products for sidebar widget
$total_products = 0;
$prod_count_res = $conn->query("SELECT COUNT(*) AS total FROM tbl_products");
if ($prod_count_res) {
    $total_products = (int)$prod_count_res->fetch_assoc()['total'];
}
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
        <span class="breadcrumb-item active">Add Product</span>
    </nav>

    <!-- Page Header -->
    <div class="product-page-header">
        <div>
            <h1><i class="bx bx-package" style="color: var(--admin-accent, #059669);"></i> Add New Product</h1>
            <p>Add medicines, clinical equipment, or supplements to your pharmacy catalog.</p>
        </div>
        <a href="view_products.php" class="btn-secondary">
            <i class="bx bx-list-ul"></i> Manage Products
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="product-grid">

        <!-- Left Column: Product Entry Form -->
        <div class="product-card">
            <div class="product-card-header">
                <h3><i class="bx bx-edit"></i> Product Specifications</h3>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert-banner success">
                    <i class="bx bx-check-circle"></i>
                    <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($err['general'])): ?>
                <div class="alert-banner error">
                    <i class="bx bx-error-circle"></i>
                    <span><?php echo htmlspecialchars($err['general'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="products.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                
                <!-- Product Name -->
                <div class="form-group">
                    <label for="name" class="form-label">
                        Product Name <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-capsule"></i>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               placeholder="e.g. Paracetamol 500mg, Omeprazole 20mg" 
                               value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php if (isset($err['name'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Company & Category (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="company" class="form-label">
                            Manufacturer / Company <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-building"></i>
                            <input type="text" 
                                   id="company" 
                                   name="company" 
                                   class="form-control" 
                                   placeholder="e.g. Square Pharma, Pfizer" 
                                   value="<?php echo htmlspecialchars($company, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <?php if (isset($err['company'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['company'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="category" class="form-label">
                            Category Type <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-category"></i>
                            <select id="category" name="category" class="form-select">
                                <option value="" disabled <?php echo empty($cat_id) ? 'selected' : ''; ?>>Select Category</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['cat_id']; ?>" <?php echo $cat_id == $c['cat_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (isset($err['category'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['category'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Price, Manufacture Date & Expiry Date (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="price" class="form-label">
                            Price (Rs.) <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-purchase-tag"></i>
                            <input type="number" 
                                   step="0.01" 
                                   min="0.01" 
                                   id="price" 
                                   name="price" 
                                   class="form-control" 
                                   placeholder="0.00" 
                                   value="<?php echo htmlspecialchars($price, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <?php if (isset($err['price'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['price'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="manufactured" class="form-label">
                            Manufactured Date <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-calendar"></i>
                            <input type="date" 
                                   id="manufactured" 
                                   name="manufactured" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($manufactured, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <?php if (isset($err['manufactured'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['manufactured'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Expiry Date & Product Image (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="expiry" class="form-label">
                            Expiry Date <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-calendar-event"></i>
                            <input type="date" 
                                   id="expiry" 
                                   name="expiry" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($expiry, ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <?php if (isset($err['expiry'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['expiry'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Product Image <span class="required">*</span>
                        </label>
                        <div class="file-upload-box">
                            <i class="bx bx-cloud-upload file-upload-icon"></i>
                            <div class="file-upload-text" id="fileNameDisplay">Choose Image File</div>
                            <div class="file-upload-hint">JPG, PNG or WEBP (Max 5MB)</div>
                            <input type="file" 
                                   name="image" 
                                   id="productImageInput" 
                                   accept="image/*"
                                   onchange="updateFileName(this)">
                        </div>
                        <?php if (isset($err['image'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['image'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnAdd" class="btn-submit">
                        <i class="bx bx-plus-circle"></i> Save Product
                    </button>
                    <a href="view_products.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Right Column: Sidebar Guidelines & Total Stats -->
        <div class="product-card product-tips-card">
            <h4><i class="bx bx-bulb"></i> Product Guidelines</h4>
            
            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Clear Product Titles:</strong> Include dosage/strength (e.g., <em>500mg</em> or <em>10ml</em>) for customer clarity.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Date Verification:</strong> Double check expiry dates before publishing pharmacy products.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>High Quality Images:</strong> Upload clean product packaging images for higher customer conversion.
                    </div>
                </li>
            </ul>

            <div class="quick-stats-box" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div class="quick-stats-info">
                    <div style="font-size: 12px; color: #64748b; font-weight: 500;">Total Active Products</div>
                    <strong style="font-size: 18px; color: #0f172a; font-weight: 700;"><?php echo $total_products; ?> Products</strong>
                </div>
                <a href="view_products.php" class="btn-secondary" style="height: 38px; padding: 0 14px; font-size: 13px;">
                    View All
                </a>
            </div>
        </div>

    </div>

</div>

<script>
    function updateFileName(input) {
        var display = document.getElementById('fileNameDisplay');
        if (input.files && input.files[0]) {
            display.textContent = input.files[0].name;
        } else {
            display.textContent = 'Choose Image File';
        }
    }
</script>