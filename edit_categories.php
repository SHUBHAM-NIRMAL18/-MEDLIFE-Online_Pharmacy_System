<?php 
require_once 'config.php';
include_once 'dashboard.php';

if (!isset($_GET['cat_id']) || !is_numeric($_GET['cat_id'])) {
    header("Location: viewcat.php?msg=1");
    exit();
}

$id = (int)$_GET['cat_id'];
$message = '';
$err = [];
$conn = get_db_connection();

// Process category update
if (isset($_POST['btnUpdate'])) {
    $cat_name = isset($_POST['cat_name']) ? trim($_POST['cat_name']) : '';
    $cat_status = isset($_POST['cat_status']) ? trim($_POST['cat_status']) : '';

    if (empty($cat_name)) {
        $err['cat_name'] = 'Please enter the category name';
    }
    if (empty($cat_status)) {
        $err['cat_status'] = 'Please select the status';
    }

    if (count($err) === 0) {
        $stmt = $conn->prepare("UPDATE tbl_categories SET cat_name = ?, cat_status = ? WHERE cat_id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $cat_name, $cat_status, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: viewcat.php?updated=1");
            exit();
        } else {
            $err['general'] = "Failed to prepare database update query.";
        }
    }
}

// Fetch current category record
$category = null;
$stmt = $conn->prepare("SELECT * FROM tbl_categories WHERE cat_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $category = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$category) {
    header("Location: viewcat.php?msg=1");
    exit();
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
        <span class="breadcrumb-item active">Edit Category</span>
    </nav>

    <!-- Page Header -->
    <div class="category-page-header">
        <div>
            <h1><i class="bx bx-edit" style="color: var(--admin-accent, #059669);"></i> Edit Category</h1>
            <p>Modify category name and store visibility status for Category #<?php echo $id; ?>.</p>
        </div>
        <a href="viewcat.php" class="btn-secondary">
            <i class="bx bx-arrow-back"></i> Back to Categories
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="category-grid">

        <!-- Form Card -->
        <div class="category-card">
            <div class="category-card-header">
                <h3><i class="bx bx-edit-alt"></i> Update Category Details</h3>
            </div>

            <?php if (isset($err['general'])): ?>
                <div class="alert-banner error">
                    <i class="bx bx-error-circle"></i>
                    <span><?php echo htmlspecialchars($err['general'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="edit_categories.php?cat_id=<?php echo $id; ?>" method="POST" autocomplete="off">
                
                <!-- Category Name -->
                <div class="form-group">
                    <label for="cat_name" class="form-label">
                        Category Name <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-tag-alt"></i>
                        <input type="text" 
                               id="cat_name" 
                               name="cat_name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($category['cat_name'], ENT_QUOTES, 'UTF-8'); ?>"
                               required>
                    </div>
                    <?php if (isset($err['cat_name'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Status Dropdown -->
                <div class="form-group">
                    <label for="cat_status" class="form-label">
                        Visibility Status <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-toggle-right"></i>
                        <select id="cat_status" name="cat_status" class="form-select">
                            <option value="1" <?php echo $category['cat_status'] == 1 ? 'selected' : ''; ?>>Active (Visible in Store)</option>
                            <option value="2" <?php echo $category['cat_status'] == 2 ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                    <?php if (isset($err['cat_status'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['cat_status'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnUpdate" class="btn-submit">
                        <i class="bx bx-check-circle"></i> Save Changes
                    </button>
                    <a href="viewcat.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Sidebar Info Card -->
        <div class="category-card category-tips-card">
            <h4><i class="bx bx-info-circle"></i> Category Info</h4>
            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Category ID:</strong> #<?php echo $id; ?>
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Current Status:</strong> 
                        <?php echo $category['cat_status'] == 1 ? '<span class="status-badge active"><span class="status-dot"></span> Active</span>' : '<span class="status-badge inactive"><span class="status-dot"></span> Inactive</span>'; ?>
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Impact:</strong> Updating the status will immediately affect all products assigned to this category.
                    </div>
                </li>
            </ul>
        </div>

    </div>

</div>