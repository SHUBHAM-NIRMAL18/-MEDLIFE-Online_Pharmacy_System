<?php 
require_once 'config.php';
include_once 'dashboard.php';

$message = '';
$err = [];
$categories = '';
$status = '';
$parent_id = 0;

$conn = get_db_connection();

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

// Process form submission
if (isset($_POST['btnAdd'])) {
    if (isset($_POST['categories']) && !empty(trim($_POST['categories']))) {
        $categories = trim($_POST['categories']);
    } else {
        $err['categories'] = 'Please enter the category name';
    }

    if (isset($_POST['status']) && !empty(trim($_POST['status']))) {
        $status = trim($_POST['status']);
    } else {
        $err['status'] = 'Please select the status';
    }

    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;

    if (count($err) === 0) {
        $stmt = $conn->prepare("INSERT INTO tbl_categories (cat_name, cat_status, parent_id) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssi", $categories, $status, $parent_id);
            $stmt->execute();
            if ($stmt->affected_rows === 1 && $stmt->insert_id > 0) {
                $message = "Category created successfully!";
                $categories = '';
                $status = '';
                $parent_id = 0;
            } else {
                $err['general'] = "Error inserting category into database.";
            }
            $stmt->close();
        } else {
            $err['general'] = "Database query preparation failed.";
        }
    }
}

// Fetch total categories count for stats display
$total_categories = 0;
$cat_count_res = $conn->query("SELECT COUNT(*) AS total FROM tbl_categories");
if ($cat_count_res) {
    $total_categories = (int)$cat_count_res->fetch_assoc()['total'];
}
?>

<!-- Include Dedicated Category CSS -->
<link rel="stylesheet" href="css/add_category.css">

<div class="admin-page-wrapper">

    <!-- Breadcrumb Navigation -->
    <nav class="admin-breadcrumb" aria-label="breadcrumb">
        <a href="admin_home.php" class="breadcrumb-item">
            <i class="bx bx-home-alt"></i> Dashboard
        </a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item">Catalog</span>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <a href="viewcat.php" class="breadcrumb-item">Categories</a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item active">Add Category</span>
    </nav>

    <!-- Page Header -->
    <div class="category-page-header">
        <div>
            <h1><i class="bx bx-plus-circle" style="color: var(--admin-accent, #059669);"></i> Add New Category / Subcategory</h1>
            <p>Define new pharmacy categories or nest subcategories under an existing parent category.</p>
        </div>
        <a href="viewcat.php" class="btn-secondary">
            <i class="bx bx-arrow-back"></i> View All Categories
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="category-grid">

        <!-- Form Card -->
        <div class="category-card">
            <div class="category-card-header">
                <h3><i class="bx bx-folder-plus"></i> Category Details</h3>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert-banner success">
                    <i class="bx bx-check-circle"></i>
                    <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($err['general'])): ?>
                <div class="alert-banner error">
                    <i class="bx bx-error-circle"></i>
                    <span><?php echo htmlspecialchars($err['general'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="categories.php" method="POST" autocomplete="off">
                
                <!-- Parent Category Selector -->
                <div class="form-group">
                    <label for="parent_id" class="form-label">
                        Parent Category Level
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-git-repo-forked"></i>
                        <select id="parent_id" name="parent_id" class="form-select">
                            <option value="0" <?php echo $parent_id == 0 ? 'selected' : ''; ?>>Root Category (Top-Level Category)</option>
                            <?php echo get_category_options($conn, 0, "", $parent_id); ?>
                        </select>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        Select a parent category to create a subcategory (e.g. <em>Pain Relief</em> under <em>Medicines</em>).
                    </div>
                </div>

                <!-- Category Name -->
                <div class="form-group">
                    <label for="categories" class="form-label">
                        Category Name <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-tag-alt"></i>
                        <input type="text" 
                               id="categories" 
                               name="categories" 
                               class="form-control" 
                               placeholder="e.g. Pain Relief, Fever & Cold, Antibiotics" 
                               value="<?php echo htmlspecialchars($categories, ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                    <?php if (isset($err['categories'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['categories'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Status Dropdown -->
                <div class="form-group">
                    <label for="status" class="form-label">
                        Visibility Status <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-toggle-right"></i>
                        <select id="status" name="status" class="form-select" required>
                            <option value="" disabled <?php echo empty($status) ? 'selected' : ''; ?>>Select Status</option>
                            <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active (Visible in Store)</option>
                            <option value="2" <?php echo $status === '2' ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                    <?php if (isset($err['status'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['status'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnAdd" class="btn-submit">
                        <i class="bx bx-plus-circle"></i> Save Category
                    </button>
                    <a href="viewcat.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Sidebar Info Card -->
        <div class="category-card category-tips-card">
            <h4><i class="bx bx-info-circle"></i> Category Guidelines</h4>
            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Multi-Level Support:</strong> You can create infinite subcategory levels (e.g. <em>Medicines</em> → <em>Pain Relief</em> → <em>Paracetamol & NSAIDs</em>).
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Total Categories:</strong> Currently managing <strong><?php echo $total_categories; ?></strong> categories.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Store Navigation:</strong> Top-level categories appear on the main storefront navigation bar with subcategories in hover dropdown menus.
                    </div>
                </li>
            </ul>
        </div>

    </div>

</div>
