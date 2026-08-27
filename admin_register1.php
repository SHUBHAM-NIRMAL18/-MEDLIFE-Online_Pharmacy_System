<?php
require_once 'config.php';
include_once 'dashboard.php';

$name = $email = $status = $message = '';
$err = [];
$conn = get_db_connection();

// Process Admin / Manager Registration
if (isset($_POST['btnadRegister'])) {
    if (isset($_POST['name']) && !empty(trim($_POST['name']))) {
        $name = trim($_POST['name']);
    } else {
        $err['name'] = 'Please enter full name';
    }

    if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Please enter a valid email address';
        } else {
            // Check duplicate email
            $dup_stmt = $conn->prepare("SELECT admin_id FROM tbl_admin WHERE email = ?");
            if ($dup_stmt) {
                $dup_stmt->bind_param("s", $email);
                $dup_stmt->execute();
                $dup_res = $dup_stmt->get_result();
                if ($dup_res && $dup_res->num_rows > 0) {
                    $err['email'] = 'An account with this email already exists';
                }
                $dup_stmt->close();
            }
        }
    } else {
        $err['email'] = 'Please enter email address';
    }

    $raw_password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $raw_conpassword = isset($_POST['conpassword']) ? trim($_POST['conpassword']) : '';

    if (empty($raw_password)) {
        $err['password'] = 'Please enter password';
    } elseif (strlen($raw_password) < 6) {
        $err['password'] = 'Password must be at least 6 characters long';
    }

    if (empty($raw_conpassword)) {
        $err['conpassword'] = 'Please confirm password';
    } elseif ($raw_password !== $raw_conpassword) {
        $err['conpassword'] = 'Passwords do not match';
    }

    $role = isset($_POST['role']) && !empty(trim($_POST['role'])) ? strtolower(trim($_POST['role'])) : 'admin';
    $status = 1;

    // Insert into Database if no errors
    if (count($err) === 0) {
        $pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
        $hashed_password = md5($raw_password);
        $stmt = $conn->prepare("INSERT INTO tbl_admin (name, email, password, status, role, pharmacy_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssi", $name, $email, $hashed_password, $status, $role, $pharmacy_id);
            $stmt->execute();
            if ($stmt->affected_rows === 1 && $stmt->insert_id > 0) {
                // If role is driver, also add to tbl_delivery_drivers for fleet tracking
                if ($role === 'driver') {
                    $d_phone = '980' . rand(1000000, 9999999);
                    $stmt_drv = $conn->prepare("INSERT INTO tbl_delivery_drivers (pharmacy_id, name, phone, email, password, vehicle_type, status) VALUES (?, ?, ?, ?, ?, 'Motorcycle', 1)");
                    if ($stmt_drv) {
                        $stmt_drv->bind_param("issss", $pharmacy_id, $name, $d_phone, $email, $hashed_password);
                        $stmt_drv->execute();
                        $stmt_drv->close();
                    }
                }
                $message = "Staff account (" . ucfirst($role) . ") created successfully!";
                $name = $email = '';
            } else {
                $err['general'] = "Failed to create user account.";
            }
            $stmt->close();
        } else {
            $err['general'] = "Database preparation query error.";
        }
    }
}

// Fetch total admin accounts count for widget scoped to this pharmacy
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
$total_admins = 0;
$admin_cnt_res = $conn->query("SELECT COUNT(*) AS total FROM tbl_admin WHERE pharmacy_id = $pharmacy_id");
if ($admin_cnt_res) {
    $total_admins = (int)$admin_cnt_res->fetch_assoc()['total'];
}
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
        <span class="breadcrumb-item active">Add Admin / Manager</span>
    </nav>

    <!-- Page Header -->
    <div class="account-page-header">
        <div>
            <h1><i class="bx bx-user-plus" style="color: var(--admin-accent, #059669);"></i> Add Admin / Manager User</h1>
            <p>Create administrative accounts with system privileges to manage store operations.</p>
        </div>
        <a href="view_user.php" class="btn-secondary">
            <i class="bx bx-group"></i> Manage Accounts
        </a>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="account-grid">

        <!-- Left Column: Registration Form -->
        <div class="account-card">
            <div class="account-card-header">
                <h3><i class="bx bx-user-pin"></i> Account Credentials</h3>
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

            <form action="admin_register1.php" method="POST" autocomplete="off">
                
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
                               placeholder="e.g. John Doe, Pharmacy Manager" 
                               value="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <?php if (isset($err['name'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['name'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Email Address -->
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
                               placeholder="e.g. admin@medlife.com" 
                               value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <span id="msg_emails" style="font-size: 12px; margin-top: 4px; display: block;"></span>
                    <?php if (isset($err['email'])): ?>
                        <div class="field-error">
                            <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['email'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Password & Confirm Password (Row) -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="password" class="form-label">
                            Password <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-lock-alt"></i>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control" 
                                   placeholder="••••••••">
                        </div>
                        <?php if (isset($err['password'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['password'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="conpassword" class="form-label">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <div class="input-icon-wrapper">
                            <i class="bx bx-lock-check"></i>
                            <input type="password" 
                                   id="conpassword" 
                                   name="conpassword" 
                                   class="form-control" 
                                   placeholder="••••••••">
                        </div>
                        <?php if (isset($err['conpassword'])): ?>
                            <div class="field-error">
                                <i class="bx bx-error"></i> <?php echo htmlspecialchars($err['conpassword'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account Role / Status Selector -->
                <div class="form-group">
                    <label class="form-label">
                        Account Staff Role & Privilege Level <span class="required">*</span>
                    </label>
                    <div class="role-radio-group" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <label class="role-radio-card" style="padding: 12px; display: flex; gap: 10px; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="admin" <?php echo empty($role) || $role === 'admin' ? 'checked' : ''; ?>>
                            <div class="role-radio-info">
                                <div style="font-weight: 700; color: #0f172a;"><i class='bx bx-shield-quarter' style="color: #6366f1;"></i> Store Admin</div>
                                <small style="color: #64748b; font-size: 11px;">Full System & Financial Access</small>
                            </div>
                        </label>

                        <label class="role-radio-card" style="padding: 12px; display: flex; gap: 10px; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="pharmacist" <?php echo $role === 'pharmacist' ? 'checked' : ''; ?>>
                            <div class="role-radio-info">
                                <div style="font-weight: 700; color: #0f172a;"><i class='bx bx-plus-medical' style="color: #10b981;"></i> Pharmacist</div>
                                <small style="color: #64748b; font-size: 11px;">Catalog, Batches & Orders</small>
                            </div>
                        </label>

                        <label class="role-radio-card" style="padding: 12px; display: flex; gap: 10px; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="cashier" <?php echo $role === 'cashier' ? 'checked' : ''; ?>>
                            <div class="role-radio-info">
                                <div style="font-weight: 700; color: #0f172a;"><i class='bx bx-purchase-tag-alt' style="color: #f59e0b;"></i> POS Cashier</div>
                                <small style="color: #64748b; font-size: 11px;">Counter Billing & POS Receipts</small>
                            </div>
                        </label>

                        <label class="role-radio-card" style="padding: 12px; display: flex; gap: 10px; align-items: center; border: 1px solid #cbd5e1; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="driver" <?php echo $role === 'driver' ? 'checked' : ''; ?>>
                            <div class="role-radio-info">
                                <div style="font-weight: 700; color: #0f172a;"><i class='bx bx-cycling' style="color: #3b82f6;"></i> Delivery Driver</div>
                                <small style="color: #64748b; font-size: 11px;">Rider Mobile Delivery Portal</small>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" name="btnadRegister" class="btn-submit">
                        <i class="bx bx-user-check"></i> Create Staff Account
                    </button>
                    <a href="view_user.php" class="btn-secondary">
                        Cancel
                    </a>
                </div>

            </form>
        </div>

        <!-- Right Column: Sidebar Guidelines & Quick Stats -->
        <div class="account-card account-tips-card">
            <h4><i class="bx bx-shield-quarter"></i> Staff Roles & Permissions</h4>
            
            <ul class="tips-list">
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Store Admin:</strong> Full operational control over inventory, store settings, pricing, staff accounts, and financial reports.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Registered Pharmacist:</strong> Authorized to manage pharmaceutical catalog, expiry batches, customer prescriptions, and order dispatching.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>POS Cashier:</strong> Optimized for in-store walk-in checkout counter billing and sales history without access to administrative settings.
                    </div>
                </li>
                <li>
                    <i class="bx bx-check-shield"></i>
                    <div>
                        <strong>Delivery Driver:</strong> Access to mobile order delivery portal, GPS maps navigation, customer phone calls, and COD collection.
                    </div>
                </li>
            </ul>

            <div class="quick-stats-box" style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div class="quick-stats-info">
                    <div style="font-size: 12px; color: #64748b; font-weight: 500;">Active Staff Accounts</div>
                    <strong style="font-size: 18px; color: #0f172a; font-weight: 700;"><?php echo $total_admins; ?> Staff</strong>
                </div>
                <a href="view_user.php?tab=admin" class="btn-secondary" style="height: 38px; padding: 0 14px; font-size: 13px;">
                    Manage
                </a>
            </div>
        </div>

    </div>

</div>

<!-- Real-time Email Check AJAX Script -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('#email').keyup(function(){
            var email = $(this).val();
            if (email.trim().length > 3) {
                $.ajax({
                    url: 'check_email.php',
                    data: {'email': email},
                    dataType: 'text',
                    method: 'post',
                    success: function(resp){
                        $('#msg_emails').html(resp);
                        if(resp.trim() === 'Email Available') {
                            $('#msg_emails').css({color: '#047857', fontWeight: '600'});
                        } else {
                            $('#msg_emails').css({color: '#dc2626', fontWeight: '600'});
                        }
                    }
                });
            } else {
                $('#msg_emails').html('');
            }
        });
    });
</script>
