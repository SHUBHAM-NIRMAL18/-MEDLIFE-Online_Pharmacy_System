<?php
require_once 'config.php';
$conn = get_db_connection();
$sql = 'SELECT * FROM tbl_products WHERE cat_id=3';
$supplements = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplements - Medlife</title>
</head>
<body>
<?php include('header.php') ?>

<main class="content-container" style="padding: 40px 24px; min-height: 60vh;">
    <h2 class="section-title">Supplements</h2>
    
    <div class="product-grid">
        <?php if ($supplements && $supplements->num_rows > 0): ?>
            <?php while($row = $supplements->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="medimg/<?php echo htmlspecialchars($row['prdct_img'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($row['prdct_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="product-price">Rs. <?php echo number_format($row['prdct_price'], 2); ?></p>
                        <div class="product-actions">
                            <a href="single.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-outline">Details</a>
                            <a href="addToCart.php?id=<?php echo $row['prdct_id']; ?>" class="btn btn-primary"><i class="bx bx-cart-add"></i> Add</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No supplements found.</p>
        <?php endif; ?>
    </div>
</main>

<?php include('footer.php') ?>
</body>
</html>