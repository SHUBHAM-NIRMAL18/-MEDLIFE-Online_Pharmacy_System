<?php
require_once 'config.php';
require_admin_role(['admin', 'pharmacist']);
include_once 'dashboard.php';

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;
$admin_id = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : 1;
$admin_name = $_SESSION['admin_name'] ?? 'Registered Pharmacist';

$msg = '';
$msg_type = '';

// Handle Approve Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnApproveRx'])) {
    $item_type = trim($_POST['item_type'] ?? 'order'); // 'order' or 'rx'
    $item_id = (int)($_POST['item_id'] ?? 0);
    $notes = trim($_POST['pharmacist_notes'] ?? '');

    if ($item_type === 'order' && $item_id > 0) {
        $stmt = $conn->prepare("UPDATE tbl_order SET prescription_status = 1, pharmacist_notes = ?, verified_by_pharmacist_id = ?, verified_at = NOW(), status = IF(status = 0, 3, status) WHERE order_id = ? AND pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("siii", $notes, $admin_id, $item_id, $pharmacy_id);
            $stmt->execute();
            $stmt->close();
            $msg = "Order #$item_id prescription approved & stamped by $admin_name. Order moved to Ready for Packing!";
            $msg_type = 'success';
        }
    } elseif ($item_type === 'rx' && $item_id > 0) {
        $stmt = $conn->prepare("UPDATE tbl_customer_prescriptions SET status = 1, pharmacist_notes = ?, verified_by = ?, verified_at = NOW() WHERE rx_id = ? AND pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("siii", $notes, $admin_id, $item_id, $pharmacy_id);
            $stmt->execute();
            $stmt->close();
            $msg = "Prescription RX #$item_id approved successfully!";
            $msg_type = 'success';
        }
    }
}

// Handle Reject Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnRejectRx'])) {
    $item_type = trim($_POST['item_type'] ?? 'order');
    $item_id = (int)($_POST['item_id'] ?? 0);
    $reason = trim($_POST['rejection_reason'] ?? 'Prescription verification requirements not met.');
    $custom_notes = trim($_POST['custom_notes'] ?? '');
    $full_notes = $reason . (!empty($custom_notes) ? " - " . $custom_notes : "");

    if ($item_type === 'order' && $item_id > 0) {
        $stmt = $conn->prepare("UPDATE tbl_order SET prescription_status = 2, prescription_rejection_reason = ?, pharmacist_notes = ?, verified_by_pharmacist_id = ?, verified_at = NOW() WHERE order_id = ? AND pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("ssiii", $reason, $full_notes, $admin_id, $item_id, $pharmacy_id);
            $stmt->execute();
            $stmt->close();
            $msg = "Order #$item_id prescription marked as Rejected. Customer has been alerted on live tracking.";
            $msg_type = 'error';
        }
    } elseif ($item_type === 'rx' && $item_id > 0) {
        $stmt = $conn->prepare("UPDATE tbl_customer_prescriptions SET status = 2, pharmacist_notes = ?, verified_by = ?, verified_at = NOW() WHERE rx_id = ? AND pharmacy_id = ?");
        if ($stmt) {
            $stmt->bind_param("siii", $full_notes, $admin_id, $item_id, $pharmacy_id);
            $stmt->execute();
            $stmt->close();
            $msg = "Prescription RX #$item_id marked as Rejected.";
            $msg_type = 'error';
        }
    }
}

// Filter selection
$filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';

// Fetch all Prescriptions from Orders with uploaded prescription
$order_rx_sql = "SELECT o.order_id, o.tracking_order, o.user_name, o.phone, o.address, o.prescription, 
                        o.prescription_status, o.prescription_rejection_reason, o.pharmacist_notes, 
                        o.verified_at, o.doctor_name, o.total, o.created_at, 'order' AS rx_type,
                        a.name AS verified_by_name
                 FROM tbl_order o
                 LEFT JOIN tbl_admin a ON o.verified_by_pharmacist_id = a.admin_id
                 WHERE o.pharmacy_id = $pharmacy_id AND o.prescription IS NOT NULL AND o.prescription != ''
                 ORDER BY o.order_id DESC";
$order_rx_res = $conn->query($order_rx_sql);

// Fetch standalone customer prescription uploads
$cust_rx_sql = "SELECT c.rx_id, '' AS tracking_order, c.patient_name AS user_name, u.phone, u.address, 
                       c.prescription_file AS prescription, c.status AS prescription_status, 
                       '' AS prescription_rejection_reason, c.pharmacist_notes, c.verified_at, 
                       c.doctor_name, 0.00 AS total, c.created_at, 'rx' AS rx_type,
                       a.name AS verified_by_name
                FROM tbl_customer_prescriptions c
                LEFT JOIN tbl_user u ON c.user_id = u.user_id
                LEFT JOIN tbl_admin a ON c.verified_by = a.admin_id
                WHERE c.pharmacy_id = $pharmacy_id
                ORDER BY c.rx_id DESC";
$cust_rx_res = $conn->query($cust_rx_sql);

$all_prescriptions = [];
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

if ($order_rx_res && $order_rx_res->num_rows > 0) {
    while ($r = $order_rx_res->fetch_assoc()) {
        $st = (int)($r['prescription_status'] ?? 0);
        if ($st === 0) $pending_count++;
        elseif ($st === 1) $approved_count++;
        elseif ($st === 2) $rejected_count++;

        if ($filter === 'all' || 
           ($filter === 'pending' && $st === 0) || 
           ($filter === 'approved' && $st === 1) || 
           ($filter === 'rejected' && $st === 2)) {
            $all_prescriptions[] = $r;
        }
    }
}

if ($cust_rx_res && $cust_rx_res->num_rows > 0) {
    while ($r = $cust_rx_res->fetch_assoc()) {
        $st = (int)($r['prescription_status'] ?? 0);
        if ($st === 0) $pending_count++;
        elseif ($st === 1) $approved_count++;
        elseif ($st === 2) $rejected_count++;

        if ($filter === 'all' || 
           ($filter === 'pending' && $st === 0) || 
           ($filter === 'approved' && $st === 1) || 
           ($filter === 'rejected' && $st === 2)) {
            $all_prescriptions[] = $r;
        }
    }
}
$total_count = $pending_count + $approved_count + $rejected_count;
?>

<link rel="stylesheet" href="css/product.css">
<style>
.rx-page-wrapper {
  padding: 24px;
  max-width: 1440px;
  margin: 0 auto;
}
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.kpi-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 18px 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  display: flex;
  align-items: center;
  gap: 16px;
}
.kpi-icon {
  width: 48px; height: 48px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 24px; flex-shrink: 0;
}
.kpi-icon.primary { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
.kpi-icon.warning { background: rgba(245, 158, 11, 0.15); color: #d97706; }
.kpi-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.kpi-icon.danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

.kpi-num { font-size: 24px; font-weight: 800; color: #0f172a; }
.kpi-label { font-size: 13px; color: #64748b; font-weight: 500; }

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 8px;
}
.filter-tab {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: all 0.15s;
}
.filter-tab.active {
  background: #059669;
  color: #ffffff;
}
.tab-count {
  background: rgba(0,0,0,0.08);
  padding: 1px 7px;
  border-radius: 10px;
  font-size: 11px;
}
.filter-tab.active .tab-count {
  background: rgba(255,255,255,0.25);
  color: #ffffff;
}

/* Prescription Card Grid */
.rx-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 20px;
}
.rx-card {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  box-shadow: 0 2px 6px rgba(0,0,0,0.04);
  display: flex;
  flex-direction: column;
}
.rx-card-header {
  padding: 14px 18px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.rx-preview-box {
  height: 200px;
  background: #0f172a;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}
.rx-preview-box img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: transform 0.2s ease;
}
.rx-preview-box:hover img {
  transform: scale(1.03);
}
.zoom-badge {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(4px);
  color: #ffffff;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.rx-card-body {
  padding: 18px;
  flex: 1;
}
.rx-card-footer {
  padding: 14px 18px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  gap: 10px;
}

/* Status Badges */
.rx-status-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11.5px;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.rx-status-badge.pending { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
.rx-status-badge.approved { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.rx-status-badge.rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

/* Modal styling */
.custom-modal {
  display: none;
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.75);
  backdrop-filter: blur(5px);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.custom-modal.open { display: flex; }
.modal-card {
  background: #ffffff;
  border-radius: 16px;
  max-width: 540px;
  width: 100%;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  animation: modalIn 0.2s ease-out;
}
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
</style>

<div class="rx-page-wrapper">

    <!-- Page Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-plus-medical' style="color: #059669;"></i> Prescription Verification & Clinical Queue
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                Review incoming doctor prescriptions, verify patient dosages, and issue clinical approvals.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="admin_order.php" class="btn-action-primary" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 9px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class='bx bx-arrow-back'></i> View All Orders
            </a>
        </div>
    </div>

    <!-- Alert Notification -->
    <?php if (!empty($msg)): ?>
        <div style="padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; <?php echo $msg_type === 'success' ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
            <i class='bx <?php echo $msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>' style="font-size: 20px;"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Grid -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon primary"><i class='bx bx-file'></i></div>
            <div>
                <div class="kpi-num"><?php echo $total_count; ?></div>
                <div class="kpi-label">Total Uploaded RX</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warning"><i class='bx bx-time-five'></i></div>
            <div>
                <div class="kpi-num" style="color: #d97706;"><?php echo $pending_count; ?></div>
                <div class="kpi-label">Pending Pharmacist Review</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon success"><i class='bx bx-check-shield'></i></div>
            <div>
                <div class="kpi-num" style="color: #059669;"><?php echo $approved_count; ?></div>
                <div class="kpi-label">Clinically Approved</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon danger"><i class='bx bx-x-circle'></i></div>
            <div>
                <div class="kpi-num" style="color: #dc2626;"><?php echo $rejected_count; ?></div>
                <div class="kpi-label">Rejected / Illegible</div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="prescription_management.php?status=all" class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
            All RX Slips <span class="tab-count"><?php echo $total_count; ?></span>
        </a>
        <a href="prescription_management.php?status=pending" class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
            <i class='bx bx-bell'></i> Pending Review <span class="tab-count"><?php echo $pending_count; ?></span>
        </a>
        <a href="prescription_management.php?status=approved" class="filter-tab <?php echo $filter === 'approved' ? 'active' : ''; ?>">
            <i class='bx bx-check'></i> Approved <span class="tab-count"><?php echo $approved_count; ?></span>
        </a>
        <a href="prescription_management.php?status=rejected" class="filter-tab <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
            <i class='bx bx-x'></i> Rejected <span class="tab-count"><?php echo $rejected_count; ?></span>
        </a>
    </div>

    <!-- Prescriptions Grid -->
    <?php if (empty($all_prescriptions)): ?>
        <div style="text-align: center; padding: 60px 20px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <i class='bx bx-check-double' style="font-size: 52px; color: #10b981; margin-bottom: 12px; display: block;"></i>
            <h3 style="font-size: 18px; font-weight: 800; color: #0f172a;">No Prescriptions in this View</h3>
            <p style="font-size: 13.5px; color: #64748b; margin-top: 4px;">All customer prescription verification requests have been processed.</p>
        </div>
    <?php else: ?>
        <div class="rx-grid">
            <?php foreach ($all_prescriptions as $rx): ?>
                <?php 
                $is_order = ($rx['rx_type'] === 'order');
                $target_id = $is_order ? $rx['order_id'] : $rx['rx_id'];
                $rx_status = (int)($rx['prescription_status'] ?? 0);
                $file_path = $rx['prescription'];
                $is_pdf = (strtolower(pathinfo($file_path, PATHINFO_EXTENSION)) === 'pdf');
                ?>
                <div class="rx-card">
                    <div class="rx-card-header">
                        <div>
                            <strong style="font-size: 14px; color: #0f172a;">
                                <?php echo $is_order ? "Order #{$rx['order_id']}" : "Standalone RX #{$rx['rx_id']}"; ?>
                            </strong>
                            <?php if (!empty($rx['tracking_order'])): ?>
                                <span style="font-family: monospace; font-size: 11px; color: #64748b; margin-left: 4px;"><?php echo htmlspecialchars($rx['tracking_order']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($rx_status === 1): ?>
                                <span class="rx-status-badge approved"><i class='bx bx-check'></i> Approved</span>
                            <?php elseif ($rx_status === 2): ?>
                                <span class="rx-status-badge rejected"><i class='bx bx-x'></i> Rejected</span>
                            <?php else: ?>
                                <span class="rx-status-badge pending"><i class='bx bx-time'></i> Pending Review</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Prescription Slip Preview -->
                    <div class="rx-preview-box" onclick="openLightbox('<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>', '<?php echo $is_pdf ? 'pdf' : 'img'; ?>')">
                        <?php if ($is_pdf): ?>
                            <div style="text-align: center; color: #ffffff;">
                                <i class='bx bxs-file-pdf' style="font-size: 54px; color: #ef4444;"></i>
                                <div style="font-size: 12px; margin-top: 4px;">PDF Document - Click to Open</div>
                            </div>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($file_path, ENT_QUOTES); ?>" alt="Prescription Document" loading="lazy">
                        <?php endif; ?>
                        <div class="zoom-badge"><i class='bx bx-zoom-in'></i> Inspect Slip</div>
                    </div>

                    <!-- Card Body -->
                    <div class="rx-card-body">
                        <div style="margin-bottom: 12px;">
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                                <i class='bx bx-user' style="color: #059669;"></i> <?php echo htmlspecialchars($rx['user_name']); ?>
                            </div>
                            <div style="font-size: 12.5px; color: #64748b; margin-top: 2px;">
                                <i class='bx bx-phone'></i> <a href="tel:<?php echo htmlspecialchars($rx['phone']); ?>" style="color: #059669; text-decoration: none;"><?php echo htmlspecialchars($rx['phone'] ?? 'N/A'); ?></a>
                            </div>
                        </div>

                        <?php if (!empty($rx['doctor_name'])): ?>
                            <div style="font-size: 12px; color: #475569; margin-bottom: 8px;">
                                <strong>Prescribing Doctor:</strong> Dr. <?php echo htmlspecialchars($rx['doctor_name']); ?>
                            </div>
                        <?php endif; ?>

                        <div style="font-size: 11.5px; color: #94a3b8;">
                            Uploaded: <?php echo date("M d, Y h:i A", strtotime($rx['created_at'])); ?>
                        </div>

                        <!-- Verification Audit Stamp -->
                        <?php if ($rx_status !== 0 && !empty($rx['verified_by_name'])): ?>
                            <div style="margin-top: 12px; padding: 10px; border-radius: 8px; font-size: 12px; <?php echo $rx_status === 1 ? 'background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;' : 'background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;'; ?>">
                                <div style="font-weight: 700;">
                                    <i class='bx <?php echo $rx_status === 1 ? 'bx-check-shield' : 'bx-error-alt'; ?>'></i>
                                    Reviewed by: <?php echo htmlspecialchars($rx['verified_by_name']); ?>
                                </div>
                                <?php if (!empty($rx['pharmacist_notes'])): ?>
                                    <div style="margin-top: 4px; font-size: 11.5px; opacity: 0.9;">
                                        Note: <?php echo htmlspecialchars($rx['pharmacist_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card Actions -->
                    <div class="rx-card-footer">
                        <?php if ($rx_status === 0): ?>
                            <button onclick="openApproveModal('<?php echo $rx['rx_type']; ?>', <?php echo $target_id; ?>, '<?php echo htmlspecialchars(addslashes($rx['user_name'])); ?>')" style="flex: 1; background: #059669; color: #ffffff; border: none; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                                <i class='bx bx-check-circle'></i> Approve RX
                            </button>
                            <button onclick="openRejectModal('<?php echo $rx['rx_type']; ?>', <?php echo $target_id; ?>, '<?php echo htmlspecialchars(addslashes($rx['user_name'])); ?>')" style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); padding: 10px 14px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                                Reject
                            </button>
                        <?php else: ?>
                            <button onclick="openApproveModal('<?php echo $rx['rx_type']; ?>', <?php echo $target_id; ?>, '<?php echo htmlspecialchars(addslashes($rx['user_name'])); ?>')" style="flex: 1; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 8px; border-radius: 8px; font-weight: 600; font-size: 12.5px; cursor: pointer;">
                                Update Notes
                            </button>
                        <?php endif; ?>

                        <?php if ($is_order): ?>
                            <a href="view_order.php?order_id=<?php echo $rx['order_id']; ?>" style="background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; text-decoration: none; display: flex; align-items: center;">
                                Order &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Modal: Approve Prescription -->
<div id="approveModal" class="custom-modal">
    <div class="modal-card">
        <div style="padding: 16px 20px; background: #ecfdf5; border-bottom: 1px solid #a7f3d0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 800; color: #065f46; display: flex; align-items: center; gap: 6px;">
                <i class='bx bx-check-shield'></i> Approve Prescription
            </h3>
            <button onclick="closeModal('approveModal')" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #065f46;">&times;</button>
        </div>
        <form method="POST" action="prescription_management.php">
            <input type="hidden" name="item_type" id="approve_item_type">
            <input type="hidden" name="item_id" id="approve_item_id">
            <div style="padding: 20px;">
                <p style="font-size: 13.5px; color: #334155; margin-bottom: 14px;">
                    Approving prescription for <strong id="approve_patient_name">Patient</strong>. This will log your pharmacist verification stamp and advance the order.
                </p>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Pharmacist Dosage & Verification Notes</label>
                    <textarea name="pharmacist_notes" rows="3" placeholder="e.g. Valid prescription. Verified dosage: 500mg twice daily with meals." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('approveModal')" style="background: #f1f5f9; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="btnApproveRx" style="background: #059669; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">Confirm Approval</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reject Prescription -->
<div id="rejectModal" class="custom-modal">
    <div class="modal-card">
        <div style="padding: 16px 20px; background: #fef2f2; border-bottom: 1px solid #fecaca; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 800; color: #991b1b; display: flex; align-items: center; gap: 6px;">
                <i class='bx bx-error-circle'></i> Reject Prescription
            </h3>
            <button onclick="closeModal('rejectModal')" style="background: none; border: none; font-size: 22px; cursor: pointer; color: #991b1b;">&times;</button>
        </div>
        <form method="POST" action="prescription_management.php">
            <input type="hidden" name="item_type" id="reject_item_type">
            <input type="hidden" name="item_id" id="reject_item_id">
            <div style="padding: 20px;">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Clinical Reason for Rejection <span style="color: #ef4444;">*</span></label>
                    <select name="rejection_reason" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                        <option value="Doctor signature / Medical Council license stamp missing">Doctor signature / license stamp missing</option>
                        <option value="Prescription has expired (> 6 months validity)">Prescription has expired (> 6 months validity)</option>
                        <option value="Doctor handwriting or dosage quantity is illegible">Doctor handwriting or dosage is illegible</option>
                        <option value="Prescribed medicines do not match items ordered in cart">Prescribed medicines do not match items ordered</option>
                        <option value="Schedule-X / Narcotic medicine requires physical in-store prescription">Controlled substance requires in-person prescription</option>
                    </select>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Additional Pharmacist Instructions to Customer</label>
                    <textarea name="custom_notes" rows="2" placeholder="e.g. Please re-upload a clear photo with doctor seal visible." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;"></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('rejectModal')" style="background: #f1f5f9; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="btnRejectRx" style="background: #dc2626; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">Reject & Notify Customer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Lightbox Zoom Preview -->
<div id="lightboxModal" class="custom-modal" onclick="closeModal('lightboxModal')">
    <div style="max-width: 90vw; max-height: 90vh; position: relative;" onclick="event.stopPropagation()">
        <button onclick="closeModal('lightboxModal')" style="position: absolute; top: -40px; right: 0; background: #ffffff; color: #0f172a; border: none; width: 36px; height: 36px; border-radius: 50%; font-size: 20px; cursor: pointer; font-weight: 700;">&times;</button>
        <div id="lightboxContent" style="background: #ffffff; border-radius: 12px; padding: 8px; overflow: auto; max-height: 85vh;"></div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openApproveModal(type, id, name) {
    document.getElementById('approve_item_type').value = type;
    document.getElementById('approve_item_id').value = id;
    document.getElementById('approve_patient_name').innerText = name;
    openModal('approveModal');
}

function openRejectModal(type, id, name) {
    document.getElementById('reject_item_type').value = type;
    document.getElementById('reject_item_id').value = id;
    openModal('rejectModal');
}

function openLightbox(url, type) {
    const container = document.getElementById('lightboxContent');
    if (type === 'pdf') {
        container.innerHTML = `<iframe src="${url}" style="width: 80vw; height: 80vh; border: none; border-radius: 8px;"></iframe>`;
    } else {
        container.innerHTML = `<img src="${url}" style="max-width: 85vw; max-height: 80vh; object-fit: contain; border-radius: 8px; display: block;">`;
    }
    openModal('lightboxModal');
}
</script>
