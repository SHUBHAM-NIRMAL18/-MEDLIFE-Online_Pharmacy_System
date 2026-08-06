<?php
require_once 'config.php';
include_once 'dashboard.php';

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUpdateSettings'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $delivery_fee = (float)($_POST['delivery_fee'] ?? 100.00);

    if (!empty($name) && !empty($email)) {
        $stmt_u = $conn->prepare("UPDATE tbl_pharmacies SET name = ?, email = ?, phone = ?, address = ?, delivery_fee = ? WHERE pharmacy_id = ?");
        if ($stmt_u) {
            $stmt_u->bind_param("ssssdi", $name, $email, $phone, $address, $delivery_fee, $pharmacy_id);
            if ($stmt_u->execute()) {
                $_SESSION['admin_pharmacy_name'] = $name;
                $msg = 'Pharmacy store details updated successfully!';
                $msg_type = 'success';
            } else {
                $msg = 'Failed to update store settings.';
                $msg_type = 'error';
            }
        }
    } else {
        $msg = 'Pharmacy name and email are required.';
        $msg_type = 'error';
    }
}

$pharmacy = get_pharmacy_details($pharmacy_id);
?>
<div class="admin-page-header">
  <div class="header-title">
    <h1><i class='bx bx-store-alt'></i> Pharmacy Store Settings</h1>
    <p>Manage store profile, contact information, and delivery pricing for your pharmacy.</p>
  </div>
</div>

<?php if (!empty($msg)): ?>
  <div style="padding: 14px 20px; border-radius: 8px; margin-bottom: 24px; font-size: 14px; font-weight: 500; <?php echo $msg_type === 'success' ? 'background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #6ee7b7;' : 'background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5;'; ?>">
    <i class='bx <?php echo $msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>'></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<div class="admin-card" style="max-width: 750px;">
  <div class="card-body" style="padding: 30px;">
    <form action="pharmacy_settings.php" method="POST">
      
      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Pharmacy Name *</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($pharmacy['name']); ?>" required style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff;">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
        <div>
          <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Contact Email *</label>
          <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($pharmacy['email']); ?>" required style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff;">
        </div>

        <div>
          <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Contact Phone Number</label>
          <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($pharmacy['phone']); ?>" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff;">
        </div>
      </div>

      <div style="margin-bottom: 20px;">
        <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Physical Address</label>
        <textarea name="address" rows="3" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff; resize: vertical;"><?php echo htmlspecialchars($pharmacy['address']); ?></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <div>
          <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Delivery Fee (रु.)</label>
          <input type="number" step="0.01" name="delivery_fee" class="form-control" value="<?php echo htmlspecialchars($pharmacy['delivery_fee']); ?>" style="width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff;">
        </div>

        <div>
          <label style="display: block; font-weight: 500; font-size: 14px; color: #cbd5e1; margin-bottom: 8px;">Current Plan</label>
          <input type="text" class="form-control" value="<?php echo htmlspecialchars($pharmacy['plan']); ?>" disabled style="width: 100%; padding: 12px; background: #1e293b; border: 1px solid #334155; border-radius: 8px; color: #94a3b8; cursor: not-allowed;">
        </div>
      </div>

      <button type="submit" name="btnUpdateSettings" class="btn-primary" style="padding: 12px 24px; background: #10b981; border: none; border-radius: 8px; color: #fff; font-weight: 600; font-size: 15px; cursor: pointer;">
        <i class='bx bx-save'></i> Save Store Settings
      </button>

    </form>
  </div>
</div>
<?php echo "</main></body></html>"; ?>
