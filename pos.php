<?php
require_once 'config.php';

$active_admin_pharmacy_id = require_admin_tenant();
$conn = get_db_connection();
$pharmacy = get_pharmacy_details($active_admin_pharmacy_id);
$cashier_name = $_SESSION['admin_name'] ?? 'Admin';

// Process POS Checkout Submission (AJAX JSON or Form POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];

    $customer_name = trim($_POST['customer_name'] ?? 'Walk-in Customer');
    if (empty($customer_name)) $customer_name = 'Walk-in Customer';
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_pan = trim($_POST['customer_pan'] ?? '');
    
    $subtotal = (float)($_POST['subtotal'] ?? 0.00);
    $discount_percent = (float)($_POST['discount_percent'] ?? 0.00);
    $discount_amount = (float)($_POST['discount_amount'] ?? 0.00);
    $tax_percent = (float)($_POST['tax_percent'] ?? 0.00);
    $tax_amount = (float)($_POST['tax_amount'] ?? 0.00);
    $grand_total = (float)($_POST['grand_total'] ?? 0.00);
    
    $payment_method = trim($_POST['payment_method'] ?? 'Cash');
    $tendered_amount = (float)($_POST['tendered_amount'] ?? $grand_total);
    $change_amount = (float)($_POST['change_amount'] ?? 0.00);
    $notes = trim($_POST['notes'] ?? '');

    $items_raw = $_POST['items'] ?? '[]';
    $items = json_decode($items_raw, true);

    if (empty($items) || !is_array($items)) {
        $response['message'] = 'Cart is empty. Please add items to checkout.';
        echo json_encode($response);
        exit();
    }

    // Generate Unique Invoice Number (e.g. POS-PH1-2608-8492)
    $invoice_no = 'POS-' . date('ymd') . '-' . rand(1000, 9999);

    // Database Transaction
    $conn->begin_transaction();
    try {
        $stmt_s = $conn->prepare("INSERT INTO tbl_pos_sales (invoice_no, pharmacy_id, customer_name, customer_phone, customer_pan, subtotal, discount_percent, discount_amount, tax_percent, tax_amount, grand_total, payment_method, tendered_amount, change_amount, cashier_name, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt_s) {
            throw new Exception("Failed to prepare sales insert: " . $conn->error);
        }
        $stmt_s->bind_param("sisssddddddsddss", $invoice_no, $active_admin_pharmacy_id, $customer_name, $customer_phone, $customer_pan, $subtotal, $discount_percent, $discount_amount, $tax_percent, $tax_amount, $grand_total, $payment_method, $tendered_amount, $change_amount, $cashier_name, $notes);
        $stmt_s->execute();
        $sale_id = $conn->insert_id;
        $stmt_s->close();

        // Process items & update stock/batches (FEFO)
        $stmt_it = $conn->prepare("INSERT INTO tbl_pos_items (sale_id, prdct_id, batch_id, batch_number, prdct_name, quantity, unit_price, item_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($items as $it) {
            $pid = (int)($it['prdct_id'] ?? 0);
            $bid = !empty($it['batch_id']) ? (int)$it['batch_id'] : null;
            $bnum = trim($it['batch_number'] ?? '');
            $pname = trim($it['prdct_name'] ?? 'Medicine Item');
            $qty = max(1, (int)($it['quantity'] ?? 1));
            $price = (float)($it['unit_price'] ?? 0.00);
            $it_total = (float)($it['item_total'] ?? ($price * $qty));

            if ($stmt_it) {
                $stmt_it->bind_param("iiissidd", $sale_id, $pid, $bid, $bnum, $pname, $qty, $price, $it_total);
                $stmt_it->execute();
            }

            // Deduct total product stock
            $conn->query("UPDATE tbl_products SET stock_quantity = GREATEST(0, stock_quantity - $qty) WHERE prdct_id = $pid AND pharmacy_id = $active_admin_pharmacy_id");

            // Deduct batch quantity
            if ($bid && $bid > 0) {
                $conn->query("UPDATE tbl_product_batches SET quantity = GREATEST(0, quantity - $qty), status = IF(quantity <= $qty, 0, 1) WHERE batch_id = $bid AND pharmacy_id = $active_admin_pharmacy_id");
            } else {
                // If specific batch wasn't picked, deduct from earliest expiring active batch (FEFO)
                $b_find = $conn->query("SELECT batch_id, quantity FROM tbl_product_batches WHERE prdct_id = $pid AND pharmacy_id = $active_admin_pharmacy_id AND quantity > 0 ORDER BY exp_date ASC LIMIT 1");
                if ($b_find && $b_find->num_rows > 0) {
                    $b_row = $b_find->fetch_assoc();
                    $target_bid = (int)$b_row['batch_id'];
                    $conn->query("UPDATE tbl_product_batches SET quantity = GREATEST(0, quantity - $qty), status = IF(quantity <= $qty, 0, 1) WHERE batch_id = $target_bid");
                }
            }
        }
        if ($stmt_it) $stmt_it->close();

        $conn->commit();
        $response['success'] = true;
        $response['invoice_no'] = $invoice_no;
        $response['message'] = 'Sale completed successfully!';
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Checkout failed: ' . $e->getMessage();
    }

    echo json_encode($response);
    exit();
}

// Fetch all products for catalog & search
$products_sql = "SELECT p.*, c.cat_name, 
                 (SELECT b.batch_number FROM tbl_product_batches b WHERE b.prdct_id = p.prdct_id AND b.pharmacy_id = p.pharmacy_id AND b.quantity > 0 ORDER BY b.exp_date ASC LIMIT 1) AS active_batch,
                 (SELECT b.exp_date FROM tbl_product_batches b WHERE b.prdct_id = p.prdct_id AND b.pharmacy_id = p.pharmacy_id AND b.quantity > 0 ORDER BY b.exp_date ASC LIMIT 1) AS active_exp,
                 (SELECT b.batch_id FROM tbl_product_batches b WHERE b.prdct_id = p.prdct_id AND b.pharmacy_id = p.pharmacy_id AND b.quantity > 0 ORDER BY b.exp_date ASC LIMIT 1) AS active_batch_id
                 FROM tbl_products p 
                 LEFT JOIN tbl_categories c ON p.cat_id = c.cat_id 
                 WHERE p.pharmacy_id = $active_admin_pharmacy_id 
                 ORDER BY p.prdct_name ASC";
$products_res = $conn->query($products_sql);
$catalog_products = [];
if ($products_res && $products_res->num_rows > 0) {
    while ($r = $products_res->fetch_assoc()) {
        $catalog_products[] = $r;
    }
}

// Fetch Categories for filters
$cats_res = $conn->query("SELECT * FROM tbl_categories WHERE pharmacy_id = $active_admin_pharmacy_id AND cat_status = 1 ORDER BY cat_name ASC");
$catalog_cats = [];
if ($cats_res && $cats_res->num_rows > 0) {
    while ($c = $cats_res->fetch_assoc()) {
        $catalog_cats[] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Terminal - <?php echo htmlspecialchars($pharmacy['name']); ?></title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/pos.css">
    <style>
        :root {
            --pos-bg: #0f172a;
            --pos-card-bg: #1e293b;
            --pos-border: #334155;
            --pos-text: #f8fafc;
            --pos-text-muted: #94a3b8;
            --pos-accent: #10b981;
            --pos-accent-hover: #059669;
            --pos-primary: #6366f1;
            --pos-danger: #ef4444;
            --pos-warning: #f59e0b;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        body {
            background-color: var(--pos-bg);
            color: var(--pos-text);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Bar */
        .pos-topbar {
            height: 56px;
            background: #1e293b;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            flex-shrink: 0;
        }
        .pos-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pos-brand-logo {
            background: var(--pos-accent);
            color: #ffffff;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
        }
        .pos-brand-title {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
        }
        .pos-tag {
            background: rgba(16, 185, 129, 0.2);
            color: var(--pos-accent);
            border: 1px solid rgba(16, 185, 129, 0.4);
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .pos-hotkeys {
            display: flex;
            gap: 14px;
            font-size: 12px;
            color: var(--pos-text-muted);
        }
        .pos-hotkey-badge {
            background: #334155;
            color: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 11px;
            margin-right: 4px;
        }
        .pos-topbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .pos-btn-nav {
            background: #334155;
            color: #f1f5f9;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .pos-btn-nav:hover {
            background: #475569;
            color: #ffffff;
        }

        /* Workspace Grid */
        .pos-workspace {
            display: grid;
            grid-template-columns: 1fr 440px;
            flex: 1;
            overflow: hidden;
        }

        /* Left Side: Catalog */
        .catalog-panel {
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--pos-border);
            background: #0b1120;
            overflow: hidden;
        }
        .catalog-toolbar {
            padding: 16px 20px 10px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #0f172a;
            border-bottom: 1px solid var(--pos-border);
        }
        .pos-search-wrapper {
            position: relative;
            width: 100%;
        }
        .pos-search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 20px;
        }
        .pos-search-input {
            width: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            padding: 12px 14px 12px 42px;
            border-radius: 10px;
            color: #ffffff;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s ease;
        }
        .pos-search-input:focus {
            border-color: var(--pos-accent);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
        }
        .cat-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            scrollbar-width: none;
        }
        .cat-pills::-webkit-scrollbar { display: none; }
        .cat-pill {
            background: #1e293b;
            color: #94a3b8;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
            border: 1px solid #334155;
            transition: all 0.15s ease;
        }
        .cat-pill:hover {
            color: #ffffff;
            border-color: #64748b;
        }
        .cat-pill.active {
            background: var(--pos-accent);
            color: #ffffff;
            border-color: var(--pos-accent);
        }

        .products-grid {
            padding: 16px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
            overflow-y: auto;
            flex: 1;
        }
        .product-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-2px);
            border-color: var(--pos-accent);
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        .product-card.out-of-stock {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }
        .prod-img {
            width: 100%;
            height: 90px;
            border-radius: 6px;
            object-fit: cover;
            background: #0f172a;
            margin-bottom: 8px;
        }
        .prod-title {
            font-size: 13px;
            font-weight: 700;
            color: #f8fafc;
            line-height: 1.3;
            margin-bottom: 3px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .prod-meta {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .prod-batch-badge {
            font-size: 10px;
            background: #0f172a;
            color: #38bdf8;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
            font-family: monospace;
        }
        .prod-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 6px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }
        .prod-price {
            font-size: 14px;
            font-weight: 800;
            color: #10b981;
        }
        .prod-stock-tag {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
        }
        .stock-in { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .stock-low { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .stock-out { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        /* Right Side: Bill Cart */
        .bill-panel {
            background: #0f172a;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }
        .customer-info-box {
            padding: 12px 16px;
            background: #1e293b;
            border-bottom: 1px solid var(--pos-border);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .pos-field-input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            padding: 6px 10px;
            border-radius: 6px;
            color: #ffffff;
            font-size: 12px;
            outline: none;
        }
        .pos-field-input:focus { border-color: var(--pos-accent); }

        .cart-items-wrapper {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .cart-table th {
            text-align: left;
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--pos-border);
        }
        .cart-item-row {
            border-bottom: 1px solid rgba(51, 65, 85, 0.5);
        }
        .cart-item-row td {
            padding: 10px 0;
            vertical-align: middle;
        }
        .qty-controls {
            display: inline-flex;
            align-items: center;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            overflow: hidden;
        }
        .qty-btn {
            background: transparent;
            border: none;
            color: #e2e8f0;
            padding: 4px 8px;
            font-size: 14px;
            cursor: pointer;
        }
        .qty-btn:hover { background: #334155; color: #ffffff; }
        .qty-val {
            width: 32px;
            text-align: center;
            background: transparent;
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 12px;
        }
        .btn-remove-item {
            background: transparent;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
        }

        /* Cart Summary & Checkout Footer */
        .bill-footer {
            background: #1e293b;
            border-top: 1px solid var(--pos-border);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--pos-text-muted);
        }
        .grand-total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #0f172a;
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 4px;
        }
        .grand-label {
            font-size: 14px;
            font-weight: 700;
            color: #f8fafc;
        }
        .grand-val {
            font-size: 22px;
            font-weight: 800;
            color: #10b981;
        }
        .btn-checkout-pay {
            width: 100%;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
            transition: all 0.2s ease;
        }
        .btn-checkout-pay:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        /* Checkout Modal */
        .pos-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .pos-modal-overlay.open { display: flex; }
        .pos-modal-box {
            background: #1e293b;
            border: 1px solid var(--pos-border);
            border-radius: 16px;
            max-width: 540px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            animation: modalPop 0.2s ease-out;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .pos-modal-header {
            padding: 16px 20px;
            background: #0f172a;
            border-bottom: 1px solid var(--pos-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .pos-modal-body {
            padding: 20px;
        }
        .pay-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }
        .pay-method-btn {
            background: #0f172a;
            border: 1px solid var(--pos-border);
            color: #ffffff;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .pay-method-btn.active {
            border-color: var(--pos-accent);
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        .quick-cash-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 10px;
        }
        .quick-cash-pill {
            background: #334155;
            color: #e2e8f0;
            border: none;
            padding: 8px 0;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .quick-cash-pill:hover { background: #475569; color: #ffffff; }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <header class="pos-topbar">
        <div class="pos-brand">
            <div class="pos-brand-logo"><i class='bx bx-plus-medical'></i></div>
            <div>
                <div class="pos-brand-title"><?php echo htmlspecialchars($pharmacy['name']); ?></div>
                <div style="font-size: 11px; color: var(--pos-text-muted);">PAN: <?php echo htmlspecialchars($pharmacy['pan_number'] ?? '609823145'); ?></div>
            </div>
            <span class="pos-tag">POS Cashier</span>
        </div>

        <div class="pos-hotkeys">
            <span><span class="pos-hotkey-badge">F2</span> Search</span>
            <span><span class="pos-hotkey-badge">F8</span> Discount</span>
            <span><span class="pos-hotkey-badge">F9</span> Pay</span>
            <span><span class="pos-hotkey-badge">ESC</span> Clear</span>
        </div>

        <div class="pos-topbar-actions">
            <div style="font-size: 13px; color: var(--pos-text-muted); margin-right: 8px;">
                <i class='bx bx-user'></i> <?php echo htmlspecialchars($cashier_name); ?>
            </div>
            <a href="pos_history.php" class="pos-btn-nav">
                <i class='bx bx-receipt'></i> Sales Log
            </a>
            <a href="batch_management.php" class="pos-btn-nav">
                <i class='bx bx-barcode-reader'></i> Batches
            </a>
            <a href="admin_home.php" class="pos-btn-nav" style="background: #ef4444; color: #fff;">
                <i class='bx bx-arrow-back'></i> Exit POS
            </a>
        </div>
    </header>

    <!-- Main Workspace -->
    <div class="pos-workspace">

        <!-- Left: Product Catalog -->
        <div class="catalog-panel">
            <div class="catalog-toolbar">
                <div class="pos-search-wrapper">
                    <i class='bx bx-search pos-search-icon'></i>
                    <input type="text" id="productSearchInput" class="pos-search-input" placeholder="Search medicine by name, brand, or batch (Press F2)..." autofocus autocomplete="off">
                </div>

                <div class="cat-pills">
                    <button class="cat-pill active" onclick="filterCategory(0, this)">All Medicines (<?php echo count($catalog_products); ?>)</button>
                    <?php foreach ($catalog_cats as $cat): ?>
                        <button class="cat-pill" onclick="filterCategory(<?php echo (int)$cat['cat_id']; ?>, this)"><?php echo htmlspecialchars($cat['cat_name']); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Grid -->
            <div class="products-grid" id="productsGrid">
                <?php if (empty($catalog_products)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; color: var(--pos-text-muted);">
                        <i class='bx bx-package' style="font-size: 48px; color: #475569;"></i>
                        <p style="margin-top: 10px; font-size: 15px;">No products in store catalog.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($catalog_products as $p): 
                        $stock = (int)($p['stock_quantity'] ?? 0);
                        $is_out = ($stock <= 0);
                        $img = !empty($p['prdct_img']) ? 'medimg/' . $p['prdct_img'] : 'img/product-default.png';
                        $batch_str = !empty($p['active_batch']) ? $p['active_batch'] : ($p['batch_number'] ?? 'BAT-GEN');
                        $exp_str = !empty($p['active_exp']) ? date('m/y', strtotime($p['active_exp'])) : '';
                    ?>
                        <div class="product-card <?php echo $is_out ? 'out-of-stock' : ''; ?>" 
                             data-id="<?php echo (int)$p['prdct_id']; ?>" 
                             data-name="<?php echo htmlspecialchars($p['prdct_name'], ENT_QUOTES); ?>" 
                             data-company="<?php echo htmlspecialchars($p['prdct_company'] ?? '', ENT_QUOTES); ?>" 
                             data-price="<?php echo (float)$p['prdct_price']; ?>" 
                             data-stock="<?php echo $stock; ?>" 
                             data-cat="<?php echo (int)$p['cat_id']; ?>" 
                             data-batch="<?php echo htmlspecialchars($batch_str, ENT_QUOTES); ?>" 
                             data-batch-id="<?php echo (int)($p['active_batch_id'] ?? 0); ?>" 
                             onclick="addToCart(<?php echo (int)$p['prdct_id']; ?>)">
                            <div>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="" class="prod-img">
                                <div class="prod-title"><?php echo htmlspecialchars($p['prdct_name']); ?></div>
                                <div class="prod-meta"><?php echo htmlspecialchars($p['prdct_company'] ?? 'Generic'); ?></div>
                                <div class="prod-batch-badge">Lot: <?php echo htmlspecialchars($batch_str); ?><?php if (!empty($exp_str)) echo ' &bull; Exp ' . $exp_str; ?></div>
                            </div>
                            <div class="prod-footer">
                                <span class="prod-price">रु. <?php echo number_format($p['prdct_price'], 2); ?></span>
                                <span class="prod-stock-tag <?php echo $is_out ? 'stock-out' : ($stock <= 10 ? 'stock-low' : 'stock-in'); ?>">
                                    <?php echo $is_out ? 'Out of Stock' : $stock . ' in stock'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right: Bill Cart & Checkout -->
        <div class="bill-panel">
            
            <!-- Customer Details Header -->
            <div class="customer-info-box">
                <div>
                    <input type="text" id="custName" class="pos-field-input" placeholder="Customer Name (Walk-in)" value="Walk-in Customer">
                </div>
                <div>
                    <input type="text" id="custPhone" class="pos-field-input" placeholder="Phone No. (e.g. 98...)">
                </div>
                <div style="grid-column: 1 / -1;">
                    <input type="text" id="custPan" class="pos-field-input" placeholder="Buyer VAT / PAN (Optional for Tax Bill)">
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="cart-items-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Medicine / Batch</th>
                            <th style="width: 25%; text-align: center;">Qty</th>
                            <th style="width: 20%; text-align: right;">Total</th>
                            <th style="width: 10%; text-align: right;"></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <!-- Populated by JS -->
                    </tbody>
                </table>
                <div id="emptyCartNotice" style="text-align: center; padding: 40px 10px; color: var(--pos-text-muted);">
                    <i class='bx bx-cart' style="font-size: 40px; color: #334155; margin-bottom: 8px;"></i>
                    <p style="font-size: 13px;">No medicines in current bill.<br>Click any product on the left or search to add.</p>
                </div>
            </div>

            <!-- Bill Calculations & Checkout Footer -->
            <div class="bill-footer">
                <div class="summary-row">
                    <span>Subtotal (<span id="cartItemsCount">0</span> items):</span>
                    <span>रु. <span id="lblSubtotal">0.00</span></span>
                </div>

                <div class="summary-row" style="align-items: center;">
                    <span style="display: flex; align-items: center; gap: 6px;">
                        Discount (%):
                        <input type="number" id="discountInput" min="0" max="100" step="0.5" value="0" style="width: 60px; background: #0f172a; border: 1px solid #334155; color: #fff; padding: 3px 6px; border-radius: 4px; font-size: 12px;" onchange="recalculateTotals()">
                    </span>
                    <span style="color: #f59e0b;">- रु. <span id="lblDiscountAmount">0.00</span></span>
                </div>

                <div class="summary-row" style="align-items: center;">
                    <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                        <input type="checkbox" id="taxToggle" onchange="recalculateTotals()">
                        <span>Add 13% VAT / Tax Invoice</span>
                    </label>
                    <span>+ रु. <span id="lblTaxAmount">0.00</span></span>
                </div>

                <div class="grand-total-box">
                    <div class="grand-label">TOTAL PAYABLE:</div>
                    <div class="grand-val">रु. <span id="lblGrandTotal">0.00</span></div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-top: 4px;">
                    <button type="button" onclick="clearCart()" style="background: #334155; color: #f87171; border: 1px solid #475569; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">
                        <i class='bx bx-trash'></i> Clear (ESC)
                    </button>
                    <button type="button" class="btn-checkout-pay" onclick="openCheckoutModal()">
                        <i class='bx bx-check-circle'></i> PAY & PRINT (F9)
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal: Payment & Receipt -->
    <div id="checkoutModal" class="pos-modal-overlay">
        <div class="pos-modal-box">
            <div class="pos-modal-header">
                <h3 style="font-size: 16px; font-weight: 700; color: #fff; display: flex; align-items: center; gap: 6px;">
                    <i class='bx bx-credit-card' style="color: #10b981;"></i> Complete POS Payment
                </h3>
                <button onclick="closeCheckoutModal()" style="background: transparent; border: none; font-size: 22px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>
            <div class="pos-modal-body">
                
                <div style="background: #0f172a; border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #334155;">
                    <div>
                        <div style="font-size: 12px; color: #94a3b8;">Total Bill Amount</div>
                        <div style="font-size: 22px; font-weight: 800; color: #10b981;">रु. <span id="modalGrandTotal">0.00</span></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 12px; color: #94a3b8;">Customer</div>
                        <div style="font-size: 14px; font-weight: 700; color: #f8fafc;" id="modalCustName">Walk-in</div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="pay-methods">
                    <button type="button" class="pay-method-btn active" onclick="setPayMethod('Cash', this)">
                        <i class='bx bx-money' style="font-size: 24px;"></i> Cash
                    </button>
                    <button type="button" class="pay-method-btn" onclick="setPayMethod('QR Pay', this)">
                        <i class='bx bx-qr-scan' style="font-size: 24px;"></i> QR / Fonepay
                    </button>
                    <button type="button" class="pay-method-btn" onclick="setPayMethod('Card', this)">
                        <i class='bx bx-credit-card-alt' style="font-size: 24px;"></i> Card POS
                    </button>
                </div>

                <!-- Cash Tendered Container -->
                <div id="cashFields">
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px;">Cash Tendered (रु.)</label>
                    <input type="number" id="tenderedInput" step="1" class="pos-search-input" style="font-size: 18px; font-weight: 700; padding: 10px 14px;" oninput="calculateChange()">

                    <div class="quick-cash-grid">
                        <button type="button" class="quick-cash-pill" onclick="setExactCash()">Exact</button>
                        <button type="button" class="quick-cash-pill" onclick="addCash(100)">+100</button>
                        <button type="button" class="quick-cash-pill" onclick="addCash(500)">+500</button>
                        <button type="button" class="quick-cash-pill" onclick="addCash(1000)">+1000</button>
                    </div>

                    <div style="margin-top: 14px; background: #0f172a; padding: 12px 16px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #334155;">
                        <span style="font-size: 14px; font-weight: 600; color: #cbd5e1;">Change Return:</span>
                        <span style="font-size: 18px; font-weight: 800; color: #38bdf8;">रु. <span id="lblChangeDue">0.00</span></span>
                    </div>
                </div>

                <!-- QR Demo Container -->
                <div id="qrFields" style="display: none; text-align: center; padding: 10px; background: #0f172a; border-radius: 10px; border: 1px solid #334155;">
                    <div style="font-size: 12px; color: #94a3b8; margin-bottom: 8px;">Scan to Pay via Fonepay / eSewa / Mobile Banking</div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=fonepay://pay?merchant=MedLife&amt=0" id="qrCodeImg" style="width: 130px; height: 130px; border-radius: 8px; background: #fff; padding: 6px; margin: 0 auto; display: block;" alt="QR Pay">
                    <div style="font-size: 11px; color: #10b981; font-weight: 700; margin-top: 8px;">Waiting for scan confirmation...</div>
                </div>

                <div style="margin-top: 20px; display: flex; gap: 10px;">
                    <button type="button" onclick="closeCheckoutModal()" style="flex: 1; background: #334155; color: #cbd5e1; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">Back</button>
                    <button type="button" id="btnSubmitSale" onclick="submitPOSSale()" style="flex: 2; background: #10b981; color: #ffffff; border: none; padding: 12px; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class='bx bx-printer'></i> Complete & Print (Enter)
                    </button>
                </div>

            </div>
        </div>
    </div>

    <!-- Thermal Print Frame (Hidden) -->
    <iframe id="receiptFrame" style="display: none;"></iframe>

    <script>
        // In-memory Cart
        let cart = [];
        let activePayMethod = 'Cash';

        // Products database cached in JS
        const productsDB = <?php echo json_encode($catalog_products); ?>;

        function addToCart(prdctId) {
            const prod = productsDB.find(p => parseInt(p.prdct_id) === parseInt(prdctId));
            if (!prod) return;

            if (parseInt(prod.stock_quantity) <= 0) {
                alert('This product is currently out of stock.');
                return;
            }

            const existing = cart.find(item => item.prdct_id === prdctId);
            if (existing) {
                if (existing.quantity < parseInt(prod.stock_quantity)) {
                    existing.quantity++;
                } else {
                    alert('Cannot add more than available stock (' + prod.stock_quantity + ' units).');
                }
            } else {
                cart.push({
                    prdct_id: parseInt(prod.prdct_id),
                    batch_id: prod.active_batch_id ? parseInt(prod.active_batch_id) : null,
                    batch_number: prod.active_batch || prod.batch_number || 'BAT-GEN',
                    prdct_name: prod.prdct_name,
                    unit_price: parseFloat(prod.prdct_price),
                    max_stock: parseInt(prod.stock_quantity),
                    quantity: 1
                });
            }

            renderCart();
        }

        function updateQty(prdctId, delta) {
            const item = cart.find(i => i.prdct_id === prdctId);
            if (!item) return;

            item.quantity += delta;
            if (item.quantity <= 0) {
                removeFromCart(prdctId);
                return;
            }
            if (item.quantity > item.max_stock) {
                item.quantity = item.max_stock;
                alert('Max available stock reached.');
            }
            renderCart();
        }

        function removeFromCart(prdctId) {
            cart = cart.filter(i => i.prdct_id !== prdctId);
            renderCart();
        }

        function clearCart() {
            if (cart.length > 0) {
                if (confirm('Are you sure you want to clear current POS bill?')) {
                    cart = [];
                    renderCart();
                }
            }
        }

        function renderCart() {
            const tbody = document.getElementById('cartTableBody');
            const emptyNotice = document.getElementById('emptyCartNotice');
            tbody.innerHTML = '';

            if (cart.length === 0) {
                emptyNotice.style.display = 'block';
            } else {
                emptyNotice.style.display = 'none';
                cart.forEach(item => {
                    const itemTotal = (item.unit_price * item.quantity).toFixed(2);
                    const tr = document.createElement('tr');
                    tr.className = 'cart-item-row';
                    tr.innerHTML = `
                        <td>
                            <div style="font-weight: 700; color: #f8fafc;">${item.prdct_name}</div>
                            <div style="font-size: 11px; color: #38bdf8; font-family: monospace;">Lot: ${item.batch_number} &bull; @ रु. ${item.unit_price.toFixed(2)}</div>
                        </td>
                        <td style="text-align: center;">
                            <div class="qty-controls">
                                <button type="button" class="qty-btn" onclick="updateQty(${item.prdct_id}, -1)">-</button>
                                <span class="qty-val">${item.quantity}</span>
                                <button type="button" class="qty-btn" onclick="updateQty(${item.prdct_id}, 1)">+</button>
                            </div>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #10b981;">
                            रु. ${itemTotal}
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="btn-remove-item" onclick="removeFromCart(${item.prdct_id})">
                                <i class='bx bx-x'></i>
                            </button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            recalculateTotals();
        }

        function recalculateTotals() {
            let subtotal = 0;
            let totalItems = 0;

            cart.forEach(item => {
                subtotal += (item.unit_price * item.quantity);
                totalItems += item.quantity;
            });

            const discountPct = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmt = (subtotal * (discountPct / 100));
            const taxable = Math.max(0, subtotal - discountAmt);

            const isTaxEnabled = document.getElementById('taxToggle').checked;
            const taxPct = isTaxEnabled ? 13 : 0;
            const taxAmt = isTaxEnabled ? (taxable * 0.13) : 0;

            const grandTotal = taxable + taxAmt;

            document.getElementById('cartItemsCount').textContent = totalItems;
            document.getElementById('lblSubtotal').textContent = subtotal.toFixed(2);
            document.getElementById('lblDiscountAmount').textContent = discountAmt.toFixed(2);
            document.getElementById('lblTaxAmount').textContent = taxAmt.toFixed(2);
            document.getElementById('lblGrandTotal').textContent = grandTotal.toFixed(2);
        }

        // Live Search filter
        document.getElementById('productSearchInput').addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.product-card');

            cards.forEach(card => {
                const name = card.dataset.name.toLowerCase();
                const company = card.dataset.company.toLowerCase();
                const batch = card.dataset.batch.toLowerCase();

                if (name.includes(query) || company.includes(query) || batch.includes(query)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Category filter
        function filterCategory(catId, btn) {
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');

            const cards = document.querySelectorAll('.product-card');
            cards.forEach(card => {
                if (catId === 0 || parseInt(card.dataset.cat) === parseInt(catId)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Modal Handlers
        function openCheckoutModal() {
            if (cart.length === 0) {
                alert('Please add medicines to bill before proceeding.');
                return;
            }

            const grandTotal = parseFloat(document.getElementById('lblGrandTotal').textContent);
            const custName = document.getElementById('custName').value.trim() || 'Walk-in Customer';

            document.getElementById('modalGrandTotal').textContent = grandTotal.toFixed(2);
            document.getElementById('modalCustName').textContent = custName;
            document.getElementById('tenderedInput').value = Math.ceil(grandTotal);

            // Update QR code data with amount
            document.getElementById('qrCodeImg').src = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent('fonepay://pay?merchant=MedLife&amt=' + grandTotal);

            calculateChange();
            document.getElementById('checkoutModal').classList.add('open');
            document.getElementById('tenderedInput').focus();
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('open');
        }

        function setPayMethod(method, btn) {
            activePayMethod = method;
            document.querySelectorAll('.pay-method-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            if (method === 'Cash') {
                document.getElementById('cashFields').style.display = 'block';
                document.getElementById('qrFields').style.display = 'none';
            } else if (method === 'QR Pay') {
                document.getElementById('cashFields').style.display = 'none';
                document.getElementById('qrFields').style.display = 'block';
            } else {
                document.getElementById('cashFields').style.display = 'none';
                document.getElementById('qrFields').style.display = 'none';
            }
        }

        function calculateChange() {
            const grandTotal = parseFloat(document.getElementById('lblGrandTotal').textContent);
            const tendered = parseFloat(document.getElementById('tenderedInput').value) || 0;
            const change = Math.max(0, tendered - grandTotal);
            document.getElementById('lblChangeDue').textContent = change.toFixed(2);
        }

        function setExactCash() {
            const grandTotal = parseFloat(document.getElementById('lblGrandTotal').textContent);
            document.getElementById('tenderedInput').value = grandTotal.toFixed(2);
            calculateChange();
        }

        function addCash(amount) {
            const cur = parseFloat(document.getElementById('tenderedInput').value) || 0;
            document.getElementById('tenderedInput').value = (cur + amount).toFixed(2);
            calculateChange();
        }

        // Submit Sale via AJAX
        function submitPOSSale() {
            const btn = document.getElementById('btnSubmitSale');
            btn.disabled = true;
            btn.innerHTML = "<i class='bx bx-loader-alt bx-spin'></i> Processing...";

            const subtotal = parseFloat(document.getElementById('lblSubtotal').textContent);
            const discountPct = parseFloat(document.getElementById('discountInput').value) || 0;
            const discountAmt = parseFloat(document.getElementById('lblDiscountAmount').textContent);
            const isTaxEnabled = document.getElementById('taxToggle').checked;
            const taxPct = isTaxEnabled ? 13 : 0;
            const taxAmt = parseFloat(document.getElementById('lblTaxAmount').textContent);
            const grandTotal = parseFloat(document.getElementById('lblGrandTotal').textContent);
            const tendered = parseFloat(document.getElementById('tenderedInput').value) || grandTotal;
            const change = parseFloat(document.getElementById('lblChangeDue').textContent) || 0;

            const postData = new FormData();
            postData.append('action', 'checkout');
            postData.append('customer_name', document.getElementById('custName').value.trim());
            postData.append('customer_phone', document.getElementById('custPhone').value.trim());
            postData.append('customer_pan', document.getElementById('custPan').value.trim());
            postData.append('subtotal', subtotal);
            postData.append('discount_percent', discountPct);
            postData.append('discount_amount', discountAmt);
            postData.append('tax_percent', taxPct);
            postData.append('tax_amount', taxAmt);
            postData.append('grand_total', grandTotal);
            postData.append('payment_method', activePayMethod);
            postData.append('tendered_amount', tendered);
            postData.append('change_amount', change);
            postData.append('items', JSON.stringify(cart.map(i => ({
                prdct_id: i.prdct_id,
                batch_id: i.batch_id,
                batch_number: i.batch_number,
                prdct_name: i.prdct_name,
                quantity: i.quantity,
                unit_price: i.unit_price,
                item_total: (i.unit_price * i.quantity)
            }))));

            fetch('pos.php', {
                method: 'POST',
                body: postData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = "<i class='bx bx-printer'></i> Complete & Print (Enter)";

                if (data.success) {
                    closeCheckoutModal();
                    // Open thermal receipt print window
                    const receiptUrl = 'pos_receipt.php?inv=' + encodeURIComponent(data.invoice_no) + '&autoprint=1';
                    window.open(receiptUrl, 'POSReceipt', 'width=400,height=600');
                    
                    // Reset POS
                    cart = [];
                    document.getElementById('custName').value = 'Walk-in Customer';
                    document.getElementById('custPhone').value = '';
                    document.getElementById('custPan').value = '';
                    document.getElementById('discountInput').value = '0';
                    document.getElementById('taxToggle').checked = false;
                    renderCart();
                    document.getElementById('productSearchInput').focus();
                } else {
                    alert('Sale error: ' + data.message);
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = "<i class='bx bx-printer'></i> Complete & Print (Enter)";
                alert('Connection error while saving POS sale.');
            });
        }

        // Global Keyboard Shortcuts
        window.addEventListener('keydown', function(e) {
            if (e.key === 'F2') {
                e.preventDefault();
                document.getElementById('productSearchInput').focus();
                document.getElementById('productSearchInput').select();
            } else if (e.key === 'F8') {
                e.preventDefault();
                document.getElementById('discountInput').focus();
                document.getElementById('discountInput').select();
            } else if (e.key === 'F9') {
                e.preventDefault();
                openCheckoutModal();
            } else if (e.key === 'Escape') {
                if (document.getElementById('checkoutModal').classList.contains('open')) {
                    closeCheckoutModal();
                } else {
                    clearCart();
                }
            }
        });
    </script>
</body>
</html>
