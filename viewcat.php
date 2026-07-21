<?php 
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();

// Helper to fetch category hierarchy recursively
function fetch_category_tree($conn, $parent_id = 0, $depth = 0) {
    $result = [];
    $stmt = $conn->prepare("SELECT c.*, p.cat_name AS parent_name FROM tbl_categories c LEFT JOIN tbl_categories p ON c.parent_id = p.cat_id WHERE c.parent_id = ? ORDER BY c.cat_name ASC");
    if ($stmt) {
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['depth'] = $depth;
                $result[] = $row;
                $children = fetch_category_tree($conn, $row['cat_id'], $depth + 1);
                $result = array_merge($result, $children);
            }
        }
        $stmt->close();
    }
    return $result;
}

$categories = fetch_category_tree($conn, 0, 0);

// If orphan subcategories exist (parent deleted), fetch them at root
$existing_ids = array_column($categories, 'cat_id');
$orphans_res = $conn->query("SELECT c.*, p.cat_name AS parent_name FROM tbl_categories c LEFT JOIN tbl_categories p ON c.parent_id = p.cat_id ORDER BY c.cat_id DESC");
if ($orphans_res && $orphans_res->num_rows > 0) {
    while ($r = $orphans_res->fetch_assoc()) {
        if (!in_array($r['cat_id'], $existing_ids)) {
            $r['depth'] = 0;
            $categories[] = $r;
        }
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
            <h1><i class="bx bx-category-alt" style="color: var(--admin-accent, #059669);"></i> Product Categories & Subcategories</h1>
            <p>Organize root categories and multi-level subcategories for your pharmacy catalog.</p>
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
            <span>Invalid Category Request. Please select a valid item.</span>
        </div>
    <?php endif; ?>

    <!-- Category Data Table Card -->
    <div class="category-table-card">
        <div class="table-header-toolbar">
            <h3><i class="bx bx-list-ul"></i> Category Hierarchy (<?php echo count($categories); ?>)</h3>
        </div>

        <?php if (!empty($categories)): ?>
            <div class="table-responsive">
                <table class="category-table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ID</th>
                            <th>Category Hierarchy Tree</th>
                            <th>Parent Category</th>
                            <th>Hierarchy Level</th>
                            <th>Visibility Status</th>
                            <th style="width: 130px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $c): ?>
                            <?php 
                            $depth = (int)$c['depth'];
                            $indent_prefix = "";
                            if ($depth > 0) {
                                $indent_prefix = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $depth - 1) . '└─ ';
                            }
                            ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;">#<?php echo $c['cat_id']; ?></td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; font-size: 14.5px; display: flex; align-items: center;">
                                        <span style="color: #059669; font-family: monospace; font-size: 14px; margin-right: 6px;"><?php echo $indent_prefix; ?></span>
                                        <?php if ($depth === 0): ?>
                                            <i class="bx bx-folder" style="color: #059669; font-size: 18px; margin-right: 6px;"></i>
                                        <?php else: ?>
                                            <i class="bx bx-subdirectory-right" style="color: #64748b; font-size: 17px; margin-right: 6px;"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($c['cat_name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($c['parent_name'])): ?>
                                        <span style="font-size: 12.5px; font-weight: 600; color: #334155; background: #f1f5f9; padding: 4px 10px; border-radius: 6px;">
                                            <i class="bx bx-git-branch"></i> <?php echo htmlspecialchars($c['parent_name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94a3b8; font-weight: 500;">Top-Level (Root)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($depth === 0): ?>
                                        <span class="status-badge active" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">Root Category</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;">Subcategory L<?php echo $depth; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($c['cat_status'] == 1): ?>
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
                                        <a href="edit_categories.php?cat_id=<?php echo (int)$c['cat_id']; ?>" 
                                           class="action-btn edit" 
                                           title="Edit Category">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="action-btn delete" 
                                                title="Delete Category"
                                                onclick="confirmDeleteCategory(<?php echo (int)$c['cat_id']; ?>, '<?php echo htmlspecialchars(addslashes($c['cat_name']), ENT_QUOTES, 'UTF-8'); ?>')">
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
                <p>You haven't created any product categories yet. Click below to add your first category.</p>
                <a href="categories.php" class="btn-action-primary">
                    <i class="bx bx-plus-circle"></i> Create First Category
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Custom Delete Category Confirmation Modal Overlay -->
<div class="admin-modal-overlay" id="deleteCategoryModal">
    <div class="admin-modal-card">
        <div class="admin-modal-icon danger">
            <i class="bx bx-trash"></i>
        </div>
        <h4>Delete Category?</h4>
        <p>Are you sure you want to delete <strong id="deleteCatName"></strong>? Any subcategories or products assigned to this category may be affected.</p>
        <div class="admin-modal-actions">
            <button class="btn-secondary" onclick="closeDeleteCategoryModal()" style="height: 42px; font-size: 13.5px;">Cancel</button>
            <a href="#" id="deleteCatConfirmLink" class="btn-action-primary" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); height: 42px; font-size: 13.5px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);">
                Delete Category
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDeleteCategory(catId, catName) {
        document.getElementById('deleteCatName').textContent = catName;
        document.getElementById('deleteCatConfirmLink').href = 'delete_categories.php?cat_id=' + catId;
        document.getElementById('deleteCategoryModal').classList.add('show');
    }

    function closeDeleteCategoryModal() {
        document.getElementById('deleteCategoryModal').classList.remove('show');
    }

    document.getElementById('deleteCategoryModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteCategoryModal();
    });
</script>
