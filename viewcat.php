<?php 
require_once 'config.php';
include_once 'dashboard.php';

$categories = [];
$conn = get_db_connection();

// Fetch categories from database
$sql = "SELECT * FROM tbl_categories ORDER BY cat_id DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
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
        <span class="breadcrumb-item active">Categories</span>
    </nav>

    <!-- Page Header -->
    <div class="category-page-header">
        <div>
            <h1><i class="bx bx-category-alt" style="color: var(--admin-accent, #059669);"></i> Product Categories</h1>
            <p>Manage and organize medicine and healthcare categories in your pharmacy.</p>
        </div>
        <a href="categories.php" class="btn-action-primary">
            <i class="bx bx-plus-circle"></i> Add New Category
        </a>
    </div>

    <!-- Notification Banners -->
    <?php if (isset($_GET['action']) && $_GET['action'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>Category has been deleted successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>Category details updated successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 1): ?>
        <div class="alert-banner error">
            <i class="bx bx-error-circle"></i>
            <span>Invalid Category Request. Please select a valid category item.</span>
        </div>
    <?php endif; ?>

    <!-- Category Data Table Card -->
    <div class="category-table-card">
        <div class="table-header-toolbar">
            <h3><i class="bx bx-list-ul"></i> All Categories (<?php echo count($categories); ?>)</h3>
        </div>

        <?php if (!empty($categories)): ?>
            <div class="table-responsive">
                <table class="category-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">SN</th>
                            <th>Category Name</th>
                            <th style="width: 180px;">Status</th>
                            <th style="width: 140px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;"><?php echo $index + 1; ?></td>
                                <td>
                                    <strong style="color: #0f172a; font-size: 14.5px;">
                                        <?php echo htmlspecialchars($cat['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($cat['cat_status'] == 1): ?>
                                        <span class="status-badge active">
                                            <span class="status-dot"></span> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">
                                            <span class="status-dot"></span> Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btn-group" style="justify-content: center;">
                                        <a href="edit_categories.php?cat_id=<?php echo (int)$cat['cat_id']; ?>" 
                                           class="action-btn edit" 
                                           title="Edit Category">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="action-btn delete" 
                                                title="Delete Category"
                                                onclick="confirmDeleteCategory(<?php echo (int)$cat['cat_id']; ?>, '<?php echo htmlspecialchars(addslashes($cat['cat_name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state-box">
                <i class="bx bx-folder-open"></i>
                <h4>No Categories Found</h4>
                <p>You haven't added any product categories yet. Click below to add your first category.</p>
                <a href="categories.php" class="btn-action-primary">
                    <i class="bx bx-plus-circle"></i> Add Category Now
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Delete Category Confirmation Modal -->
<div class="admin-modal-overlay" id="deleteCategoryModal">
    <div class="admin-modal-card">
        <div class="admin-modal-icon danger">
            <i class="bx bx-trash"></i>
        </div>
        <h4>Delete Category?</h4>
        <p>Are you sure you want to delete category <strong id="deleteCategoryName"></strong>? Products assigned to this category may lose their categorization.</p>
        <div class="admin-modal-actions">
            <button class="btn-secondary" onclick="closeDeleteCategoryModal()" style="height: 42px; font-size: 13.5px;">Cancel</button>
            <a href="#" id="deleteCategoryConfirmLink" class="btn-action-primary" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); height: 42px; font-size: 13.5px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);">
                Delete Category
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDeleteCategory(catId, catName) {
        document.getElementById('deleteCategoryName').textContent = catName;
        document.getElementById('deleteCategoryConfirmLink').href = 'delete_categories.php?cat_id=' + catId;
        document.getElementById('deleteCategoryModal').classList.add('show');
    }

    function closeDeleteCategoryModal() {
        document.getElementById('deleteCategoryModal').classList.remove('show');
    }

    document.getElementById('deleteCategoryModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteCategoryModal();
    });
</script>
