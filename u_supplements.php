<?php
require_once 'config.php';
$conn = get_db_connection();
$res = $conn->query("SELECT cat_id FROM tbl_categories WHERE LOWER(cat_name) LIKE '%supplement%' LIMIT 1");
$cat_id = ($res && $res->num_rows > 0) ? (int)$res->fetch_assoc()['cat_id'] : 3;
header("Location: search_products.php?cat=" . $cat_id);
exit();