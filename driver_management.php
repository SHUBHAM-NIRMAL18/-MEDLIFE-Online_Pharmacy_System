<?php
require_once 'config.php';
require_admin_role(['admin', 'pharmacist']);
include_once 'dashboard.php';

$conn = get_db_connection();
$pharmacy_id = isset($_SESSION['admin_pharmacy_id']) ? (int)$_SESSION['admin_pharmacy_id'] : 1;

$msg = '';
$msg_type = '';

// Handle Add Driver Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btnAddDriver'])) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $raw_pass = trim($_POST['password'] ?? '');
    $vehicle_type = trim($_POST['vehicle_type'] ?? 'Motorcycle');
    $vehicle_number = trim($_POST['vehicle_number'] ?? '');
    $license_number = trim($_POST['license_number'] ?? '');

    if (!empty($name) && !empty($phone) && !empty($email) && !empty($raw_pass)) {
        // Check duplicate email in tbl_delivery_drivers or tbl_admin
        $chk = $conn->prepare("SELECT driver_id FROM tbl_delivery_drivers WHERE email = ? OR phone = ?");
        if ($chk) {
            $chk->bind_param("ss", $email, $phone);
            $chk->execute();
            $c_res = $chk->get_result();
            if ($c_res && $c_res->num_rows > 0) {
                $msg = 'A driver with this email or phone number already exists.';
                $msg_type = 'error';
            } else {
                $hashed_pass = md5($raw_pass);
                $stmt = $conn->prepare("INSERT INTO tbl_delivery_drivers (pharmacy_id, name, phone, email, password, vehicle_type, vehicle_number, license_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
                if ($stmt) {
                    $stmt->bind_param("isssssss", $pharmacy_id, $name, $phone, $email, $hashed_pass, $vehicle_type, $vehicle_number, $license_number);
                    if ($stmt->execute()) {
                        // Also ensure account exists in tbl_admin with role = 'driver'
                        $chk_a = $conn->query("SELECT admin_id FROM tbl_admin WHERE email = '" . $conn->real_escape_string($email) . "'");
                        if ($chk_a && $chk_a->num_rows === 0) {
                            $conn->query("INSERT INTO tbl_admin (name, email, password, status, role, pharmacy_id) VALUES ('" . $conn->real_escape_string($name) . "', '" . $conn->real_escape_string($email) . "', '$hashed_pass', 1, 'driver', $pharmacy_id)");
                        }

                        $msg = "Delivery driver <strong>" . htmlspecialchars($name) . "</strong> registered successfully!";
                        $msg_type = 'success';
                    } else {
                        $msg = 'Database insertion error.';
                        $msg_type = 'error';
                    }
                    $stmt->close();
                }
            }
            $chk->close();
        }
    } else {
        $msg = 'Please fill in all required driver details.';
        $msg_type = 'error';
    }
}

// Handle Driver Status Toggle
if (isset($_GET['toggle_id']) && is_numeric($_GET['toggle_id'])) {
    $toggle_id = (int)$_GET['toggle_id'];
    $conn->query("UPDATE tbl_delivery_drivers SET status = IF(status = 1, 0, 1) WHERE driver_id = $toggle_id AND pharmacy_id = $pharmacy_id");
    $msg = 'Driver availability status updated.';
    $msg_type = 'success';
}

// Handle Delete Driver
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    $conn->query("DELETE FROM tbl_delivery_drivers WHERE driver_id = $del_id AND pharmacy_id = $pharmacy_id");
    $msg = 'Delivery driver removed from fleet.';
    $msg_type = 'success';
}

// Fetch Fleet Statistics
$total_drivers = 0;
$available_drivers = 0;
$on_delivery_drivers = 0;
$inactive_drivers = 0;

$st_res = $conn->query("SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) AS avail_cnt,
    SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS on_del_cnt,
    SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) AS inact_cnt
    FROM tbl_delivery_drivers WHERE pharmacy_id = $pharmacy_id");
if ($st_res && $st_res->num_rows > 0) {
    $st = $st_res->fetch_assoc();
    $total_drivers = (int)$st['total'];
    $available_drivers = (int)$st['avail_cnt'];
    $on_delivery_drivers = (int)$st['on_del_cnt'];
    $inactive_drivers = (int)$st['inact_cnt'];
}

// Fetch Drivers with their delivery metrics
$drivers_sql = "SELECT d.*, 
                (SELECT COUNT(*) FROM tbl_order o WHERE o.driver_id = d.driver_id AND o.status = 4) AS active_orders_cnt,
                (SELECT COUNT(*) FROM tbl_order o WHERE o.driver_id = d.driver_id AND o.status = 1) AS completed_orders_cnt
                FROM tbl_delivery_drivers d 
                WHERE d.pharmacy_id = $pharmacy_id 
                ORDER BY d.driver_id DESC";
$drivers_res = $conn->query($drivers_sql);
$drivers = [];
if ($drivers_res && $drivers_res->num_rows > 0) {
    while ($row = $drivers_res->fetch_assoc()) {
        $drivers[] = $row;
    }
}
?>

<link rel="stylesheet" href="css/product.css">
<style>
.fleet-wrapper {
  padding: 24px;
  max-width: 1400px;
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
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  flex-shrink: 0;
}
.kpi-icon.primary { background: rgba(99, 102, 241, 0.12); color: #6366f1; }
.kpi-icon.success { background: rgba(16, 185, 129, 0.12); color: #10b981; }
.kpi-icon.warning { background: rgba(245, 158, 11, 0.12); color: #f59e0b; }
.kpi-icon.danger { background: rgba(239, 68, 68, 0.12); color: #ef4444; }

.kpi-num {
  font-size: 24px;
  font-weight: 800;
  color: #0f172a;
}
.kpi-label {
  font-size: 13px;
  color: #64748b;
  font-weight: 500;
}

/* Modal styling */
.custom-modal {
  display: none;
  position: fixed;
  top: 0; left: 0; width: 100%; height: 100%;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(4px);
  z-index: 9999;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.custom-modal.open { display: flex; }
.modal-card {
  background: #ffffff;
  border-radius: 16px;
  max-width: 580px;
  width: 100%;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  overflow: hidden;
  animation: modalIn 0.2s ease-out;
}
@keyframes modalIn {
  from { opacity: 0; transform: scale(0.96); }
  to { opacity: 1; transform: scale(1); }
}
.modal-header {
  padding: 18px 24px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-header h3 {
  font-size: 17px;
  font-weight: 700;
  color: #0f172a;
  display: flex;
  align-items: center;
  gap: 8px;
}
.modal-close {
  background: transparent;
  border: none;
  font-size: 22px;
  color: #64748b;
  cursor: pointer;
}
.modal-body { padding: 24px; }
</style>

<div class="fleet-wrapper">

    <!-- Page Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                <i class='bx bx-cycling' style="color: #059669;"></i> Delivery Fleet & Courier Management
            </h1>
            <p style="font-size: 14px; color: #64748b; margin-top: 4px;">
                Manage delivery riders, assign live pharmacy orders, and track courier dispatch status.
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="driver_login.php" target="_blank" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 8px; font-weight: 600; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class='bx bx-mobile-alt'></i> Open Driver Portal
            </a>
            <button onclick="openModal('addDriverModal')" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); color: #ffffff; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                <i class='bx bx-user-plus'></i> Register New Rider
            </button>
        </div>
    </div>

    <!-- Alert Banner -->
    <?php if (!empty($msg)): ?>
        <div style="padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; <?php echo $msg_type === 'success' ? 'background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
            <i class='bx <?php echo $msg_type === 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?>' style="font-size: 20px;"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Grid -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon primary"><i class='bx bx-group'></i></div>
            <div>
                <div class="kpi-num"><?php echo $total_drivers; ?></div>
                <div class="kpi-label">Registered Couriers</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon success"><i class='bx bx-check-circle'></i></div>
            <div>
                <div class="kpi-num" style="color: #059669;"><?php echo $available_drivers; ?></div>
                <div class="kpi-label">Available / Active</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon warning"><i class='bx bx-navigation'></i></div>
            <div>
                <div class="kpi-num" style="color: #d97706;"><?php echo $on_delivery_drivers; ?></div>
                <div class="kpi-label">Currently on Delivery</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon danger"><i class='bx bx-user-x'></i></div>
            <div>
                <div class="kpi-num" style="color: #64748b;"><?php echo $inactive_drivers; ?></div>
                <div class="kpi-label">Inactive Riders</div>
            </div>
        </div>
    </div>

    <!-- Drivers Table -->
    <div style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
            <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class='bx bx-list-ul' style="color: #059669;"></i> Fleet Drivers Roster
            </h3>
            <span style="font-size: 12px; color: #64748b; font-weight: 600;">Showing <?php echo count($drivers); ?> drivers</span>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; color: #475569; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th style="padding: 14px 18px;">Rider Name</th>
                        <th style="padding: 14px 18px;">Contact & Login</th>
                        <th style="padding: 14px 18px;">Vehicle & License</th>
                        <th style="padding: 14px 18px; text-align: center;">Active Assigned</th>
                        <th style="padding: 14px 18px; text-align: center;">Completed</th>
                        <th style="padding: 14px 18px;">Status</th>
                        <th style="padding: 14px 18px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody style="divide-y: 1px solid #e2e8f0;">
                    <?php if (empty($drivers)): ?>
                        <tr>
                            <td colspan="7" style="padding: 48px 20px; text-align: center; color: #94a3b8;">
                                <i class='bx bx-cycling' style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                                <div style="font-size: 15px; font-weight: 600; color: #475569;">No Delivery Drivers Registered</div>
                                <div style="font-size: 13px; margin-top: 4px;">Click the button above to register your first delivery courier.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($drivers as $d): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#ffffff'">
                                <td style="padding: 14px 18px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(16, 185, 129, 0.12); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;">
                                            <i class='bx bx-cycling'></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($d['name']); ?></div>
                                            <div style="font-size: 11.5px; color: #64748b;">Rider #<?php echo $d['driver_id']; ?> &bull; Joined <?php echo date('M Y', strtotime($d['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 600; color: #0f172a;">
                                        <a href="tel:<?php echo htmlspecialchars($d['phone']); ?>" style="color: #059669; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class='bx bx-phone'></i> <?php echo htmlspecialchars($d['phone']); ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 12px; color: #64748b;"><?php echo htmlspecialchars($d['email']); ?></div>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 600; color: #1e293b;">
                                        <i class='bx bx-car' style="color: #64748b;"></i> <?php echo htmlspecialchars($d['vehicle_type']); ?>
                                        <?php if (!empty($d['vehicle_number'])): ?>
                                            <span style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; font-size: 11px; margin-left: 4px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($d['vehicle_number']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($d['license_number'])): ?>
                                        <div style="font-size: 11px; color: #64748b;">Lic: <?php echo htmlspecialchars($d['license_number']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: center;">
                                    <?php if ($d['active_orders_cnt'] > 0): ?>
                                        <span style="background: rgba(245, 158, 11, 0.15); color: #d97706; padding: 3px 10px; border-radius: 12px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px;">
                                            <i class='bx bx-package'></i> <?php echo $d['active_orders_cnt']; ?> on-road
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">0</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: center;">
                                    <span style="font-weight: 700; color: #059669;"><?php echo $d['completed_orders_cnt']; ?></span>
                                </td>
                                <td style="padding: 14px 18px;">
                                    <?php if ((int)$d['status'] === 1): ?>
                                        <span style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                            ● Available
                                        </span>
                                    <?php elseif ((int)$d['status'] === 2): ?>
                                        <span style="background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                            ● On Delivery
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">
                                            ● Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 14px 18px; text-align: right;">
                                    <div style="display: inline-flex; gap: 6px;">
                                        <a href="driver_management.php?toggle_id=<?php echo $d['driver_id']; ?>" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                                            Toggle Status
                                        </a>
                                        <a href="driver_management.php?delete_id=<?php echo $d['driver_id']; ?>" onclick="return confirm('Remove driver <?php echo htmlspecialchars($d['name'], ENT_QUOTES); ?> from fleet?')" style="background: rgba(239, 68, 68, 0.1); color: #dc2626; border: 1px solid rgba(239, 68, 68, 0.2); padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-decoration: none;">
                                            <i class='bx bx-trash'></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add New Driver -->
<div id="addDriverModal" class="custom-modal">
    <div class="modal-card">
        <div class="modal-header">
            <h3><i class='bx bx-cycling' style="color: #059669;"></i> Onboard New Delivery Driver</h3>
            <button class="modal-close" onclick="closeModal('addDriverModal')">&times;</button>
        </div>
        <form method="POST" action="driver_management.php">
            <div class="modal-body">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Full Name <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" placeholder="e.g. Ramesh Kumar" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Phone Number (Mobile) <span style="color: #ef4444;">*</span></label>
                        <input type="text" name="phone" placeholder="e.g. 9841000000" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Login Email <span style="color: #ef4444;">*</span></label>
                        <input type="email" name="email" placeholder="driver@store.com" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                    </div>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Portal Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" name="password" placeholder="Min 6 characters" required style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Vehicle Type</label>
                        <select name="vehicle_type" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                            <option value="Motorcycle" selected>Motorcycle / Bike</option>
                            <option value="Scooter">Scooter</option>
                            <option value="Bicycle">Bicycle</option>
                            <option value="Delivery Van">Delivery Van</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Vehicle Number Plate</label>
                        <input type="text" name="vehicle_number" placeholder="e.g. BA 2 PA 1234" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-family: monospace;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 6px;">Driver License Number</label>
                    <input type="text" name="license_number" placeholder="e.g. 01-06-0098765" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal('addDriverModal')" style="background: #f1f5f9; color: #475569; border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Cancel</button>
                    <button type="submit" name="btnAddDriver" style="background: #059669; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer;">Register Courier</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('custom-modal')) { e.target.classList.remove('open'); }
});
</script>
