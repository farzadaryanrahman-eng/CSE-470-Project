<?php
// UPDATED again for Features 12/13 (Product Catalog + Catalog Management):
// products now come from the `items` table instead of being hardcoded,
// with search (?q=) and category filter (?category=) support. Wishlist,
// reviews links, and cart logic from before are unchanged.
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $name = $_POST['item_name'];
    $price = (float)$_POST['item_price'];

    if ($action === 'add' || $action === 'inc') {
        if (isset($_SESSION['cart'][$name])) {
            $_SESSION['cart'][$name]['qty']++;
        } else {
            $_SESSION['cart'][$name] = ['qty' => 1, 'price' => $price];
        }
    } elseif ($action === 'dec') {
        if (isset($_SESSION['cart'][$name])) {
            $_SESSION['cart'][$name]['qty']--;
            if ($_SESSION['cart'][$name]['qty'] <= 0) {
                unset($_SESSION['cart'][$name]);
            }
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    } elseif ($action === 'wishlist') {
        if (!isset($_SESSION['username'])) {
            header("Location: register.html");
            exit;
        }
        $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (username, item_name, item_price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $_SESSION['username'], $name, $price);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['q']) || isset($_GET['category']) ? '?' . http_build_query($_GET) : ''));
    exit;
}

function formatCurrency($n) {
    return '$' . number_format($n, 2);
}

// ---- Feature 12/13: build the catalog from the database ----
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';

$catRes = $conn->query("SELECT DISTINCT category FROM items ORDER BY category");
$categories = [];
while ($row = $catRes->fetch_assoc()) $categories[] = $row['category'];

$sql = "SELECT id, item_name, category, item_price, item_quantity, description, low_stock_threshold FROM items WHERE 1=1";
$types = "";
$params = [];

if ($search !== '') {
    $sql .= " AND item_name LIKE ?";
    $types .= "s";
    $params[] = "%$search%";
}
if ($categoryFilter !== '') {
    $sql .= " AND category = ?";
    $types .= "s";
    $params[] = $categoryFilter;
}
$sql .= " ORDER BY category, item_name";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
$productsByCategory = [];
while ($row = $res->fetch_assoc()) {
    $productsByCategory[$row['category']][] = $row;
}
$stmt->close();

$totalQty = 0;
$totalCost = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalQty += $item['qty'];
    $totalCost += ($item['qty'] * $item['price']);
}
?>
<!DOCTYPE html> 
<html lang="en">
<head> 
    <meta charset="UTF-8" /> 
    <title>Paw Mart</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style> 
     body { 
        font-family: Arial, sans-serif;
        margin: 0; 
        background: #e6f4ff; 
        } 
      header {
        background: #ff6fa5; 
        color: #fff; 
        padding: 14px 20px; 
        font-size: 20px; 
        font-weight: 700; 
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        } 
      .nav-links a {
        color: #fff;
        font-size: 13px;
        font-weight: normal;
        text-decoration: none;
        margin-left: 12px;
        background: rgba(255,255,255,0.2);
        padding: 5px 10px;
        border-radius: 12px;
        }
      .nav-links a:hover { background: rgba(255,255,255,0.35); }
      .filter-bar {
        display: flex;
        gap: 10px;
        padding: 14px 16px 0 16px;
        flex-wrap: wrap;
        }
      .filter-bar input, .filter-bar select {
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        }
      .filter-bar button {
        padding: 8px 14px;
        border: none;
        border-radius: 8px;
        background: #ff6fa5;
        color: #fff;
        cursor: pointer;
        }
      .layout { 
        display: grid; 
        grid-template-columns: 1fr 320px; 
        gap: 16px; 
        padding: 16px; 
        } 
      .panel { 
        background: #ffd2e3; 
        border-radius: 16px; 
        padding: 14px; 
        margin-bottom: 16px;
        } 
      .panel h2 { 
        margin: 0 0 10px; 
        font-size: 18px; 
        } 
      .item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 8px 10px; 
        background: #fff;
        border-radius: 10px; 
        margin-bottom: 8px; 
        flex-wrap: wrap;
        gap: 6px;
        } 
      .item-info { display: flex; flex-direction: column; }
      .item-price { color: #555; font-size: 0.85em; }
      .low-stock-tag { color: #e74c3c; font-size: 0.75em; font-weight: bold; }
      .item-actions { display: flex; align-items: center; gap: 6px; }
      .item button { 
        padding: 6px 10px; 
        border: none; 
        border-radius: 8px; 
        background: #ff6fa5; 
        color: #fff; 
        cursor: pointer; 
        }
      .wish-btn {
        background: #fff !important;
        color: #ff6fa5 !important;
        border: 1px solid #ff6fa5 !important;
        }
      .review-link {
        font-size: 12px;
        color: #555;
        text-decoration: none;
        white-space: nowrap;
        }
      .review-link:hover { text-decoration: underline; }
      .cart { 
        background: #fff; 
        border: 2px dashed #cbd5e1; 
        border-radius: 16px; 
        padding: 14px; 
        } 
      .cart h2 { 
        margin: 0 0 8px; 
        font-size: 18px;
         } 
      .cart-empty { 
        color: #64748b; 
        font-style: italic; 
        } 
      .cart-items { 
        list-style: none; 
        padding: 0; 
        margin: 8px 0; 
        } 
      .cart-items li { 
        display: grid; 
        grid-template-columns: 1fr auto auto auto; 
        gap: 8px;
        align-items: center; 
        padding: 8px; 
        background: #f8fafc; 
        border-radius: 8px; 
        margin-bottom: 6px; 
        } 
        .cart-btn { 
            border: none; 
            background: #ff6fa5; 
            color: #fff; 
            border-radius: 6px; 
            padding: 4px 8px; 
            cursor: pointer; 
            } 
        .qty { 
            padding: 2px 8px; 
            border-radius: 6px; 
            background: #fff; 
            border: 1px solid #cbd5e1; 
            } 
        .cart-footer { 
            display: flex; 
            justify-content: space-between;
            align-items: center; 
            margin-top: 10px; 
            } 
        .clear-btn { 
            border: none; 
            background: #ef4444; 
            color: #fff; 
            border-radius: 8px; 
            padding: 8px 10px; 
            cursor: pointer; 
            } 
	    .pay-btn { display: block; text-align: center; 
		 background: #10b981; color: #fff; 
		 text-decoration: none; 
		 padding: 12px; 
		 border-radius: 8px; 
		 font-weight: bold; }
        .pay-btn:hover { 
		background: #059669; 
		}
        .no-results { text-align: center; color: #777; padding: 30px; }
    </style> 
</head> 
<body> 
    <header>
        Paw Mart
        <div class="nav-links">
            <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
            <a href="order_management.php"><i class="fas fa-receipt"></i> My Orders</a>
            <a href="payment_history.php"><i class="fas fa-file-invoice-dollar"></i> Payments</a>
        </div>
    </header>

    <form class="filter-bar" method="GET">
        <input type="text" name="q" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $categoryFilter === $c ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($c); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
        <?php if ($search !== '' || $categoryFilter !== ''): ?>
            <a href="pawmart.php" style="align-self:center; color:#555;">Clear</a>
        <?php endif; ?>
    </form>

    <div class="layout"> 
        <div>
            <?php if (empty($productsByCategory)): ?>
                <p class="no-results">No products match your search.</p>
            <?php else: ?>
                <?php foreach ($productsByCategory as $category => $products): ?>
                <div class="panel">
                    <h2><?php echo htmlspecialchars($category); ?></h2>
                    <?php foreach ($products as $p): ?>
                        <div class="item">
                            <div class="item-info">
                                <span><?php echo htmlspecialchars($p['item_name']); ?></span>
                                <span class="item-price">
                                    <?php echo formatCurrency($p['item_price']); ?>
                                    <?php if ($p['item_quantity'] <= 0): ?>
                                        <span class="low-stock-tag">Out of stock</span>
                                    <?php elseif ($p['item_quantity'] < $p['low_stock_threshold']): ?>
                                        <span class="low-stock-tag">Only <?php echo $p['item_quantity']; ?> left!</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="item-actions">
                                <a class="review-link" href="reviews.php?item=<?php echo urlencode($p['item_name']); ?>">Reviews</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($p['item_name']); ?>">
                                    <input type="hidden" name="item_price" value="<?php echo $p['item_price']; ?>">
                                    <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($p['item_name']); ?>">
                                    <input type="hidden" name="item_price" value="<?php echo $p['item_price']; ?>">
                                    <button type="submit" name="action" value="add" <?php echo $p['item_quantity'] <= 0 ? 'disabled' : ''; ?>>Add</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <aside class="cart" aria-label="Cart"> 
            <h2>Cart</h2> 
            <?php if (empty($_SESSION['cart'])): ?>
                <p class="cart-empty">Your cart is empty.</p> 
            <?php else: ?>
                <ul class="cart-items">
                    <?php foreach ($_SESSION['cart'] as $name => $data): ?>
                        <li> 
                          <span><?php echo htmlspecialchars($name); ?></span> 
                          <span class="qty"><?php echo $data['qty']; ?></span> 
                          <form method="POST" style="display:inline;">
                              <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($name); ?>">
                              <input type="hidden" name="item_price" value="<?php echo $data['price']; ?>">
                              <button type="submit" name="action" value="inc" class="cart-btn">+</button> 
                              <button type="submit" name="action" value="dec" class="cart-btn">−</button>
                          </form>
                        </li> 
                    <?php endforeach; ?>
                </ul> 
            <?php endif; ?>

            <div class="cart-footer"> 
                <div class="cart-footer-top">
                    <strong>Items: <?php echo $totalQty; ?> • Total: <?php echo formatCurrency($totalCost); ?></strong> 
                    <form method="POST">
                        <button type="submit" name="action" value="clear" class="clear-btn" <?php echo empty($_SESSION['cart']) ? 'disabled' : ''; ?>>Clear cart</button> 
                    </form>
                </div>

                <?php if (!empty($_SESSION['cart'])): ?>
                    <a href="payment.php" class="pay-btn">Proceed to Payment</a>
                <?php endif; ?>
            </div> 
        </aside>
    </div>
    <a href="page2.php" class="home-link"> 
        <img src="home.png" height="400" width="400"> 
    </a>
</body> 
</html>
