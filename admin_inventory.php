<?php
// Features 19 & 20: Inventory Stock Management + Low Stock Alert
// Restricted to accounts with role='admin' in the consumer table.
// Run schema_additions_2.sql first, then manually promote a test account:
//   UPDATE consumer SET role='admin' WHERE Email='your@email.com';
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}
$user = $_SESSION['username'];

$stmt = $conn->prepare("SELECT role FROM consumer WHERE Name = ? LIMIT 1");
$stmt->bind_param("s", $user);
$stmt->execute();
$roleRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$roleRow || $roleRow['role'] !== 'admin') {
    header("Location: page2.php");
    exit();
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update_stock'])) {
        $id = (int)$_POST['item_id'];
        $qty = (int)$_POST['item_quantity'];
        $stmt = $conn->prepare("UPDATE items SET item_quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $qty, $id);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Stock updated.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    } elseif (isset($_POST['add_product'])) {
        $name = trim($_POST['item_name']);
        $category = trim($_POST['category']);
        $price = (float)$_POST['item_price'];
        $qty = (int)$_POST['item_quantity'];
        $desc = trim($_POST['description']);
        $threshold = (int)$_POST['low_stock_threshold'];
        $stmt = $conn->prepare("INSERT INTO items (item_name, category, item_price, item_quantity, description, low_stock_threshold) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdisi", $name, $category, $price, $qty, $desc, $threshold);
        $message = $stmt->execute()
            ? "<p style='color:green;font-weight:bold;'>Product added.</p>"
            : "<p style='color:red;'>Error: " . htmlspecialchars($stmt->error) . "</p>";
        $stmt->close();
    }
}

$res = $conn->query("SELECT id, item_name, category, item_price, item_quantity, low_stock_threshold FROM items ORDER BY category, item_name");
$items = [];
$lowStockCount = 0;
while ($row = $res->fetch_assoc()) {
    $row['low'] = $row['item_quantity'] < $row['low_stock_threshold'];
    if ($row['low']) $lowStockCount++;
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Inventory Admin - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:900px; margin:0 auto; }
    .card { background:#fff; padding:20px; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:20px; }
    .alert-banner { background:#fff3cd; color:#856404; border:1px solid #ffe08a; padding:12px 18px; border-radius:10px; margin-bottom:20px; text-align:center; font-weight:bold; }
    input, select, button { width:100%; padding:9px; margin:6px 0; border-radius:8px; border:1px solid #ccc; box-sizing:border-box; font-family:inherit; }
    button { background:#ffb6c1; font-weight:bold; cursor:pointer; border:none; }
    button:hover { background:#ff8fa3; }
    table { width:100%; border-collapse:collapse; }
    th, td { padding:10px; text-align:left; border-bottom:1px solid #eee; }
    th { background:#ffb6c1; color:#fff; }
    tr.low-row { background:#fff5f5; }
    .low-badge { background:#e74c3c; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.75em; margin-left:6px; }
    .qty-form { display:flex; gap:6px; align-items:center; }
    .qty-form input { width:80px; margin:0; }
    .qty-form button { width:auto; padding:6px 10px; font-size:0.85em; margin:0; }
    .add-grid { display:grid; grid-template-columns: 1fr 1fr; gap:10px; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-boxes"></i> Inventory Admin</h1>
    <?php echo $message; ?>

    <?php if ($lowStockCount > 0): ?>
        <div class="alert-banner">
            <i class="fas fa-triangle-exclamation"></i>
            <?php echo $lowStockCount; ?> product(s) are below their low-stock threshold.
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Add a New Product</h3>
        <form method="POST" class="add-grid">
            <input type="text" name="item_name" placeholder="Product Name" required>
            <input type="text" name="category" placeholder="Category (e.g. Food)" required>
            <input type="number" step="0.01" name="item_price" placeholder="Price" required>
            <input type="number" name="item_quantity" placeholder="Starting Quantity" required>
            <input type="number" name="low_stock_threshold" placeholder="Low Stock Threshold" value="5" required>
            <input type="text" name="description" placeholder="Short description" style="grid-column: span 2;">
            <button type="submit" name="add_product" value="1" style="grid-column: span 2;">Add Product</button>
        </form>
    </div>

    <div class="card">
        <h3>Current Stock</h3>
        <table>
            <tr><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Update</th></tr>
            <?php foreach ($items as $i): ?>
            <tr class="<?php echo $i['low'] ? 'low-row' : ''; ?>">
                <td><?php echo htmlspecialchars($i['item_name']); ?><?php if ($i['low']): ?><span class="low-badge">LOW</span><?php endif; ?></td>
                <td><?php echo htmlspecialchars($i['category']); ?></td>
                <td>$<?php echo number_format($i['item_price'], 2); ?></td>
                <td><?php echo $i['item_quantity']; ?> (threshold: <?php echo $i['low_stock_threshold']; ?>)</td>
                <td>
                    <form method="POST" class="qty-form">
                        <input type="hidden" name="item_id" value="<?php echo $i['id']; ?>">
                        <input type="number" name="item_quantity" value="<?php echo $i['item_quantity']; ?>" required>
                        <button type="submit" name="update_stock" value="1">Save</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
