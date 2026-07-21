<?php 
require_once 'config.php';

if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    header("Location: view_user.php?msg=1");
    exit();
}

$id = (int)$_GET['user_id'];
$err = [];
$conn = get_db_connection();

// Process Customer Account Update BEFORE rendering HTML / including dashboard.php
if (isset($_POST['btnUpdate'])) {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

    if (empty($name)) {
        $err['name'] = 'Please enter user full name';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err['email'] = 'Please enter a valid email address';
    }

    if (count($err) === 0) {
        $stmt = $conn->prepare("UPDATE tbl_user SET name = ?, email = ?, phone = ?, address = ?, gender = ? WHERE user_id = ?");
        if ($stmt) {
            $stmt->bind_param("sssssi", $name, $email, $phone, $address, $gender, $id);
            $stmt->execute();
            $stmt->close();
            header("Location: view_user.php?updated=1");
            exit();
        } else {
            $err['general'] = "Failed to prepare database update query.";
        }
    }
}

// Fetch Customer Account Details
$user = null;
$stmt = $conn->prepare("SELECT * FROM tbl_user WHERE user_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$user) {
    header("Location: view_user.php?msg=1");
    exit();
}

// NOW include dashboard HTML
include_once 'dashboard.php';
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
        <a href="view_user.php" class="breadcrumb-item">Manage Accounts</a>
        <span class="breadcrumb-separator"><i class="bx bx-chevron-right"></i></span>
        <span class="breadcrumb-item active">Edit User</span>
    </nav>

    <!-- Page Header -->
    <div class="account-page-header">
        <div>
            <h1><i class="bx bx-user-voice" style="color: var(--admin-accent, #059669);"></i> Edit Customer Account</h1>
            <p>Update personal profile, contact information, and shipping address for User #<?php echo $id; ?>.</p>
        </div>
        <a href="view_user.php" class="btn-secondary">
            <i class="bx bx-arrow-back"></i> Back to Accounts
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="account-grid">

        <!-- Left Column: Form Card -->
        <div class="account-card">
            <div class="account-card-header">
                <h3><i class="bx bx-edit-alt"></i> Customer Profile Information</h3>
            </div>

            <?php if (isset($err['general'])): ?>
                <div class="alert-banner error">
                    <i class="bx bx-error-circle"></i>
                    <span><?php echo htmlspecialchars($err['general'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form action="update_user.php?user_id=<?php echo $id; ?>" method="POST" autocomplete="off">
                
                <!-- Full Name -->
                <div class="form-group">
                    <label for="name" class="form-label">
                        Full Name <span class="required">*</span>
                    </label>
                    <div class="input-icon-wrapper">
                        <i class="bx bx-user"></i>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" 
                               required>
                    </div>
                    <?php if (isset($err['name'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Email & Phone (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email Address <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-envelope"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" 
                                   required>
                        </div>
                        <?php if (isset($err['email'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['email'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone" class="form-label">
                            Phone Number
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-phone"></i>
                            <input type="text" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Address & Gender (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="address" class="form-label">
                            Shipping Address
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-map-pin"></i>
                            <input type="text" 
                                   id="address" 
                                   name="address" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['address'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label">
                            Gender
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-user-pin"></i>
                            <select id="gender" name="gender" class="form-select">
                                <option value="" <?php echo empty($user['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                <option value="male" <?php echo strtolower($user['gender']) === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo strtolower($user['gender']) === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo strtolower($user['gender']) === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnUpdate" class="btn-submit">
                        <i class="bx bx-check-circle"></i> Save Changes
                    </button>
                    <a href="view_user.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Right Column: Sidebar Customer Profile Summary -->
        <div class="account-card account-tips-card">
            <h4><i class="bx bx-id-card"></i> Customer Overview</h4>
            
            <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <div class="user-avatar-circle" style="width: 54px; height: 54px; font-size: 22px; margin: 0 auto 10px;">
                    <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                </div>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a;">
                    <?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <div style="font-size: 12.5px; color: #64748b;">
                    <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
            </div>

            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Customer ID:</strong> #<?php echo $id; ?>
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Account Type:</strong> Registered Customer
                    </div>
                </li>
            </ul>
        </div>

    </div>

</div>