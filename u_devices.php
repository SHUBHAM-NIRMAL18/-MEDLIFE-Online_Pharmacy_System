<?php
require_once 'config.php';
$conn = get_db_connection();
$curr_pharm_id = get_current_pharmacy_id();

$stmt = $conn->prepare("SELECT cat_id FROM tbl_categories WHERE (LOWER(cat_name) LIKE '%device%' OR LOWER(cat_name) LIKE '%monitor%' OR LOWER(cat_name) LIKE '%medical%') AND pharmacy_id = ? LIMIT 1");
$cat_id = 0;
if ($stmt) {
    $stmt->bind_param("i", $curr_pharm_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $cat_id = (int)$res->fetch_assoc()['cat_id'];
    }
    $stmt->close();
}

if ($cat_id > 0) {
    header("Location: search_products.php?cat=" . $cat_id);
} else {
    header("Location: search_products.php?search=device");
}
exit();