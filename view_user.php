<?php 
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();

// Tab selection: 'customer' (default) vs 'admin'
$tab = isset($_GET['tab']) && $_GET['tab'] === 'admin' ? 'admin' : 'customer';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$limit = 8; // Items per page
$offset = ($page - 1) * $limit;

// Count total records
$total_records = 0;
if ($tab === 'admin') {
    $count_res = $conn->query("SELECT COUNT(*) AS total FROM tbl_admin");
    if ($count_res) {
        $total_records = (int)$count_res->fetch_assoc()['total'];
    }
} else {
    $count_res = $conn->query("SELECT COUNT(*) AS total FROM tbl_user");
    if ($count_res) {
        $total_records = (int)$count_res->fetch_assoc()['total'];
    }
}

$total_pages = max(1, (int)ceil($total_records / $limit));
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit;
}

// Fetch paginated account records
$accounts = [];
if ($tab === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM tbl_admin ORDER BY admin_id DESC LIMIT ? OFFSET ?");
} else {
    $stmt = $conn->prepare("SELECT * FROM tbl_user ORDER BY user_id DESC LIMIT ? OFFSET ?");
}

if ($stmt) {
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $accounts[] = $row;
        }
    }
    $stmt->close();
}

$start_item = $total_records > 0 ? $offset + 1 : 0;
$end_item = min($offset + $limit, $total_records);
?>

<!-- Include Dedicated Account CSS -->
<link rel="stylesheet" href="css/account.css">

<div class="admin-page-wrapper">

    <!-- Breadcrumb Navigation -->
    <nav class="admin-breadcrumb" aria-label="breadcrumb">
        <a href="admin_home.php" class="breadcrumb-item">
            <i class="bx bx-home-alt"></i> Dashboard
        </a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item">Accounts</span>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item active">Manage Accounts</span>
    </nav>

    <!-- Page Header -->
    <div class="account-page-header">
        <div>
            <h1><i class="bx bx-group" style="color: var(--admin-accent, #059669);"></i> Manage Accounts</h1>
            <p>View, edit, or manage registered customer accounts and system admin/manager credentials.</p>
        </div>
        <a href="admin_register1.php" class="btn-action-primary">
            <i class="bx bx-user-plus"></i> Add Admin / Manager
        </a>
    </div>

    <!-- Notification Banners -->
    <?php if (isset($_GET['action']) && $_GET['action'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>Account deleted successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="alert-banner success">
            <i class="bx bx-check-circle"></i>
            <span>User details updated successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 1): ?>
        <div class="alert-banner error">
            <i class="bx bx-error-circle"></i>
            <span>Invalid Request. Please select a valid account.</span>
        </div>
    <?php endif; ?>

    <!-- Account Data Table Card -->
    <div class="account-table-card">
        <div class="table-header-toolbar">
            <div class="account-tab-group">
                <a href="view_user.php?tab=customer" class="account-tab-btn <?php echo $tab === 'customer' ? 'active' : ''; ?>">
                    <i class="bx bx-user"></i> Registered Customers
                </a>
                <a href="view_user.php?tab=admin" class="account-tab-btn <?php echo $tab === 'admin' ? 'active' : ''; ?>">
                    <i class="bx bx-shield-quarter"></i> Admins & Managers
                </a>
            </div>

            <div style="font-size: 13px; color: #64748b; font-weight: 500;">
                Total: <strong><?php echo $total_records; ?></strong> <?php echo $tab === 'admin' ? 'Staff Accounts' : 'Customers'; ?>
            </div>
        </div>

        <?php if (!empty($accounts)): ?>
            <div class="table-responsive">
                <table class="account-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">SN</th>
                            <th>User Profile</th>
                            <?php if ($tab === 'customer'): ?>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Gender</th>
                            <?php else: ?>
                                <th>Role & Access</th>
                                <th>Status</th>
                            <?php endif; ?>
                            <th style="width: 120px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $index => $acc): ?>
                            <tr>
                                <td style="font-weight: 600; color: #64748b;"><?php echo $offset + $index + 1; ?></td>
                                <td>
                                    <div class="user-avatar-cell">
                                        <div class="user-avatar-circle">
                                            <?php echo strtoupper(substr($acc['name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div class="user-info-text">
                                            <strong><?php echo htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small><?php echo htmlspecialchars($acc['email'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </div>
                                    </div>
                                </td>

                                <?php if ($tab === 'customer'): ?>
                                    <td style="color: #475569; font-weight: 500;">
                                        <?php echo !empty($acc['phone']) ? htmlspecialchars($acc['phone'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>
                                    </td>
                                    <td style="color: #475569;">
                                        <?php echo !empty($acc['address']) ? htmlspecialchars($acc['address'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <span class="role-badge customer">
                                            <i class="bx bx-user-check"></i> <?php echo !empty($acc['gender']) ? ucfirst(htmlspecialchars($acc['gender'], ENT_QUOTES, 'UTF-8')) : 'Customer'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btn-group" style="justify-content: center;">
                                            <a href="update_user.php?user_id=<?php echo (int)$acc['user_id']; ?>" 
                                               class="action-btn edit" 
                                               title="Edit Customer">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="action-btn delete" 
                                                    title="Delete Customer"
                                                    onclick="confirmDeleteAccount('customer', <?php echo (int)$acc['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($acc['name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <?php if ((int)$acc['status'] === 1): ?>
                                            <span class="role-badge admin">
                                                <i class="bx bx-shield-alt-2"></i> Administrator
                                            </span>
                                        <?php elseif ((int)$acc['status'] === 2): ?>
                                            <span class="role-badge manager">
                                                <i class="bx bx-briefcase-alt-2"></i> Store Manager
                                            </span>
                                        <?php else: ?>
                                            <span class="role-badge inactive">
                                                <i class="bx bx-block"></i> Inactive Staff
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$acc['status'] > 0): ?>
                                            <span style="color: #047857; font-weight: 600; font-size: 12.5px;">● Active</span>
                                        <?php else: ?>
                                            <span style="color: #dc2626; font-weight: 600; font-size: 12.5px;">● Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btn-group" style="justify-content: center;">
                                            <button type="button" 
                                                    class="action-btn delete" 
                                                    title="Delete Staff Account"
                                                    onclick="confirmDeleteAccount('admin', <?php echo (int)$acc['admin_id']; ?>, '<?php echo htmlspecialchars(addslashes($acc['name']), ENT_QUOTES, 'UTF-8'); ?>')">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Pagination Bar -->
            <div class="table-footer-toolbar">
                <div class="table-pagination-info">
                    Showing <strong><?php echo $start_item; ?></strong> to <strong><?php echo $end_item; ?></strong> of <strong><?php echo $total_records; ?></strong> accounts
                </div>

                <?php if ($total_pages > 1): ?>
                    <ul class="admin-pagination">
                        <!-- Previous Page -->
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="view_user.php?tab=<?php echo $tab; ?>&page=<?php echo $page - 1; ?>" class="page-link" title="Previous Page">
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
                                <a href="view_user.php?tab=<?php echo $tab; ?>&page=<?php echo $i; ?>" 
                                   class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="view_user.php?tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>" class="page-link" title="Next Page">
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
                <i class="bx bx-user-x"></i>
                <h4>No Accounts Found</h4>
                <p>No <?php echo $tab === 'admin' ? 'admin/manager' : 'customer'; ?> accounts match your view.</p>
                <?php if ($tab === 'admin'): ?>
                    <a href="admin_register1.php" class="btn-action-primary">
                        <i class="bx bx-user-plus"></i> Add Admin / Manager
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Delete Account Confirmation Modal Overlay -->
<div class="admin-modal-overlay" id="deleteAccountModal">
    <div class="admin-modal-card">
        <div class="admin-modal-icon danger">
            <i class="bx bx-user-x"></i>
        </div>
        <h4>Delete Account?</h4>
        <p>Are you sure you want to delete account <strong id="deleteAccountName"></strong>? This action cannot be undone.</p>
        <div class="admin-modal-actions">
            <button class="btn-secondary" onclick="closeDeleteAccountModal()" style="height: 42px; font-size: 13.5px;">Cancel</button>
            <a href="#" id="deleteAccountConfirmLink" class="btn-action-primary" style="background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); height: 42px; font-size: 13.5px; box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3);">
                Delete Account
            </a>
        </div>
    </div>
</div>

<script>
    function confirmDeleteAccount(type, accId, accName) {
        document.getElementById('deleteAccountName').textContent = accName;
        var targetUrl = (type === 'admin') 
            ? 'delete_user.php?admin_id=' + accId 
            : 'delete_user.php?user_id=' + accId;
        document.getElementById('deleteAccountConfirmLink').href = targetUrl;
        document.getElementById('deleteAccountModal').classList.add('show');
    }

    function closeDeleteAccountModal() {
        document.getElementById('deleteAccountModal').classList.remove('show');
    }

    document.getElementById('deleteAccountModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteAccountModal();
    });
</script>