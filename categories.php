<?php 
require_once 'config.php';
include_once 'dashboard.php';

$message = '';
$err = [];
$categories = '';
$status = '';

$conn = get_db_connection();

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

    if (count($err) === 0) {
        $stmt = $conn->prepare("INSERT INTO tbl_categories (cat_name, cat_status) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $categories, $status);
            $stmt->execute();
            if ($stmt->affected_rows === 1 && $stmt->insert_id > 0) {
                $message = "Category created successfully!";
                $categories = '';
                $status = '';
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
            <h1><i class="bx bx-folder-plus" style="color: var(--admin-accent, #059669);"></i> Add New Category</h1>
            <p>Create product categories to organize medicines, health supplements, and medical devices.</p>
        </div>
        <a href="viewcat.php" class="btn-secondary">
            <i class="bx bx-list-ul"></i> View All Categories
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="category-grid">

        <!-- Left Column: Add Category Form Card -->
        <div class="category-card">
            <div class="category-card-header">
                <h3><i class="bx bx-edit"></i> Category Information</h3>
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
                               placeholder="e.g. Prescription Medicines, Health Supplements" 
                               value="<?php echo htmlspecialchars($categories, ENT_QUOTES, 'UTF-8'); ?>">
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
                        <select id="status" name="status" class="form-select">
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

        <!-- Right Column: Guidelines & Quick Stats Card -->
        <div class="category-card category-tips-card">
            <h4><i class="bx bx-bulb"></i> Category Guidelines</h4>
            
            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Descriptive Naming:</strong> Use clear names like <em>Diabetes Care</em> or <em>First Aid Supplies</em> so customers can find products easily.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Active Status:</strong> Categories marked as <em>Active</em> will immediately appear in customer search and navigation menus.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Organization:</strong> Group similar medicine types together for better inventory tracking.
                    </div>
                </li>
            </ul>

            <div class="quick-stats-box">
                <div class="quick-stats-info">
                    <div>Total Active Categories</div>
                    <strong><?php echo $total_categories; ?> Categories</strong>
                </div>
                <a href="viewcat.php" class="btn-secondary" style="height: 38px; padding: 0 14px; font-size: 13px;">
                    Manage
                </a>
            </div>
        </div>

    </div>

</div>
