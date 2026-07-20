<?php 
require_once 'config.php';
$conn = get_db_connection();

$cat_id = 0;
$res = $conn->query("SELECT cat_id FROM tbl_categories WHERE LOWER(cat_name) LIKE '%supplement%' LIMIT 1");
if ($res && $res->num_rows > 0) {
    $cat_id = (int)$res->fetch_assoc()['cat_id'];
}

header("Location: view_products.php" . ($cat_id > 0 ? "?cat=" . $cat_id : ""));
exit();
?>