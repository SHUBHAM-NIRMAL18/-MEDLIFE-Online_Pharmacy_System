<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_login']) || empty($_SESSION['user_id'])) {
    header('location: customer_login.php?msg=1');
    exit();
}

$conn = get_db_connection();
$user_id = (int)$_SESSION['user_id'];
$pharmacy_id = get_current_pharmacy_id();

$msg = '';
$msg_type = '';

// Handle Direct Prescription Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnUploadRx'])) {
    $patient_name = trim($_POST['patient_name'] ?? '');
    $doctor_name = trim($_POST['doctor_name'] ?? '');
    $doctor_phone = trim($_POST['doctor_phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!empty($patient_name) && isset($_FILES['rx_file']) && !empty($_FILES['rx_file']['name'])) {
        $fileName = basename($_FILES['rx_file']['name']);
        $fileTmp = $_FILES['rx_file']['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($ext, $allowed)) {
            if (!is_dir('prescriptions')) {
                mkdir('prescriptions', 0777, true);
            }
            $targetPath = 'prescriptions/' . time() . '_' . rand(1000, 9999) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
            if (move_uploaded_file($fileTmp, $targetPath)) {
                $stmt = $conn->prepare("INSERT INTO tbl_customer_prescriptions (pharmacy_id, user_id, patient_name, doctor_name, doctor_phone, prescription_file, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                if ($stmt) {
                    $stmt->bind_param("iisssss", $pharmacy_id, $user_id, $patient_name, $doctor_name, $doctor_phone, $targetPath, $notes);
                    $stmt->execute();
                    $stmt->close();
                    $msg = 'Your prescription has been uploaded successfully! Our registered pharmacists are reviewing it.';
                    $msg_type = 'success';
                }
            } else {
                $msg = 'Failed to save uploaded file. Please try again.';
                $msg_type = 'error';
            }
        } else {
            $msg = 'Invalid file format. Please upload JPG, PNG, WEBP, or PDF.';
            $msg_type = 'error';
        }
    } else {
        $msg = 'Please provide patient name and select a prescription file.';
        $msg_type = 'error';
    }
}

// Fetch all Prescriptions for this customer (Both standalone RX and Order-linked RX)
$user_prescriptions = [];

// 1. Standalone RX
$st_res = $conn->query("SELECT c.*, a.name AS pharmacist_name 
                        FROM tbl_customer_prescriptions c 
                        LEFT JOIN tbl_admin a ON c.verified_by = a.admin_id 
                        WHERE c.user_id = $user_id 
                        ORDER BY c.rx_id DESC");
if ($st_res && $st_res->num_rows > 0) {
    while ($row = $st_res->fetch_assoc()) {
        $row['type'] = 'standalone';
        $user_prescriptions[] = $row;
    }
}

// 2. Order-linked RX
$ord_res = $conn->query("SELECT o.order_id, o.tracking_order, o.user_name AS patient_name, o.doctor_name, 
                                o.prescription AS prescription_file, o.prescription_status AS status, 
                                o.pharmacist_notes, o.prescription_rejection_reason, o.verified_at, 
                                o.created_at, a.name AS pharmacist_name 
                         FROM tbl_order o 
                         LEFT JOIN tbl_admin a ON o.verified_by_pharmacist_id = a.admin_id 
                         WHERE o.user_id = $user_id AND o.prescription IS NOT NULL AND o.prescription != '' 
                         ORDER BY o.order_id DESC");
if ($ord_res && $ord_res->num_rows > 0) {
    while ($row = $ord_res->fetch_assoc()) {
        $row['type'] = 'order';
        $user_prescriptions[] = $row;
    }
}

$page_title = "My Prescriptions";
$page_css = "css/account.css";
include('header.php');
?>

<main class="content-container" style="min-height: 70vh; padding: 40px 24px;">

    <!-- Page Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-file-blank' style="color: var(--primary);"></i> My Medical Prescriptions
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                Upload doctor prescriptions for pharmacist review, order refills, and track clinical approvals.
            </p>
        </div>
        <a href="user_dashboard.php" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px;">
            <i class='bx bx-user'></i> Dashboard
        </a>
    </div>

    <!-- Alert Notification -->
    <?php if (!empty($msg)): ?>
        <div style="padding: 14px 18px; border-radius: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; <?php echo $msg_type === 'success' ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
            <i class='bx <?php echo $msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>' style="font-size: 20px;"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 360px 1fr; gap: 24px; align-items: start;">

        <!-- Left: Upload Form Card -->
        <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-cloud-upload' style="color: var(--primary);"></i> Upload Doctor Prescription
            </h3>

            <form method="POST" action="customer_prescription.php" enctype="multipart/form-data">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Patient Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="patient_name" value="<?php echo htmlspecialchars($_SESSION['name'] ?? '', ENT_QUOTES); ?>" placeholder="e.g. Ramesh Sharma" required style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Doctor / Clinic Name</label>
                    <input type="text" name="doctor_name" placeholder="e.g. Dr. K. Adhikari (Civil Hospital)" style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Prescription Slip (Image or PDF) <span style="color: #ef4444;">*</span></label>
                    <input type="file" name="rx_file" accept=".jpg, .jpeg, .png, .webp, .pdf" required style="width: 100%; padding: 8px; border: 1.5px dashed #cbd5e1; border-radius: 8px; font-size: 12.5px; background: #f8fafc;">
                    <small style="display: block; color: #64748b; font-size: 11.5px; margin-top: 4px;">Clear photo showing doctor seal, signature & medicines.</small>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">Special Instructions / Notes</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Need 30 days dosage of prescribed tablet." style="width: 100%; padding: 10px 12px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px;"></textarea>
                </div>

                <button type="submit" name="btnUploadRx" class="btn btn-primary" style="width: 100%; justify-content: center; height: 42px; font-weight: 700;">
                    <i class='bx bx-upload'></i> Submit for Verification
                </button>
            </form>

            <div style="margin-top: 20px; padding: 14px; background: #f8fafc; border-radius: 10px; font-size: 12px; color: #475569; border: 1px solid #e2e8f0;">
                <strong style="color: #0f172a; display: block; margin-bottom: 4px;"><i class='bx bx-shield-quarter' style="color: #059669;"></i> Prescription Guidelines:</strong>
                &bull; Ensure the doctor's name, license number, and signature are clearly visible.<br>
                &bull; Prescriptions are verified within 15 minutes by our licensed pharmacists.
            </div>
        </div>

        <!-- Right: Uploaded Prescriptions List -->
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 16px;">
                Prescription History & Status (<?php echo count($user_prescriptions); ?>)
            </h3>

            <?php if (empty($user_prescriptions)): ?>
                <div style="text-align: center; padding: 50px 20px; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0;">
                    <i class='bx bx-file' style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                    <h4 style="font-size: 16px; font-weight: 700; color: #0f172a;">No Prescriptions Uploaded Yet</h4>
                    <p style="font-size: 13px; color: #64748b; margin-top: 4px;">Use the form on the left to upload your doctor's prescription slip.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 16px;">
                    <?php foreach ($user_prescriptions as $item): ?>
                        <?php 
                        $status = (int)($item['status'] ?? 0);
                        $file = $item['prescription_file'];
                        $is_pdf = (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf');
                        ?>
                        <div style="background: #ffffff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px; display: flex; gap: 18px; align-items: center; flex-wrap: wrap;">
                            
                            <!-- Thumbnail / Icon -->
                            <div style="width: 80px; height: 80px; border-radius: 10px; background: #0f172a; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                <?php if ($is_pdf): ?>
                                    <i class='bx bxs-file-pdf' style="font-size: 40px; color: #ef4444;"></i>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" alt="RX Slip" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php endif; ?>
                            </div>

                            <!-- Details -->
                            <div style="flex: 1; min-width: 220px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px;">
                                    <strong style="font-size: 15px; color: #0f172a;">
                                        Patient: <?php echo htmlspecialchars($item['patient_name']); ?>
                                    </strong>
                                    <div>
                                        <?php if ($status === 1): ?>
                                            <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                <i class='bx bx-check-shield'></i> Verified & Approved
                                            </span>
                                        <?php elseif ($status === 2): ?>
                                            <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                <i class='bx bx-x-circle'></i> Rejected
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 3px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                <i class='bx bx-time'></i> Under Pharmacist Review
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div style="font-size: 12.5px; color: #64748b;">
                                    <?php if (!empty($item['doctor_name'])): ?>
                                        Doctor: Dr. <?php echo htmlspecialchars($item['doctor_name']); ?> &bull; 
                                    <?php endif; ?>
                                    Uploaded: <?php echo date("M d, Y", strtotime($item['created_at'])); ?>
                                    <?php if ($item['type'] === 'order'): ?>
                                        &bull; <span style="color: var(--primary); font-weight: 600;">Linked to Order #<?php echo $item['order_id']; ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Pharmacist Notes / Rejection Reason -->
                                <?php if ($status === 1 && !empty($item['pharmacist_notes'])): ?>
                                    <div style="margin-top: 8px; font-size: 12px; color: #065f46; background: #f0fdf4; padding: 6px 10px; border-radius: 6px; border: 1px solid #bbf7d0;">
                                        <strong>Pharmacist Note:</strong> <?php echo htmlspecialchars($item['pharmacist_notes']); ?>
                                    </div>
                                <?php elseif ($status === 2 && (!empty($item['prescription_rejection_reason']) || !empty($item['pharmacist_notes']))): ?>
                                    <div style="margin-top: 8px; font-size: 12px; color: #991b1b; background: #fef2f2; padding: 6px 10px; border-radius: 6px; border: 1px solid #fecaca;">
                                        <strong>Rejection Reason:</strong> <?php echo htmlspecialchars($item['prescription_rejection_reason'] ?: $item['pharmacist_notes']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div>
                                <a href="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>" target="_blank" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class='bx bx-show'></i> View Slip
                                </a>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<?php include('footer.php'); ?>
