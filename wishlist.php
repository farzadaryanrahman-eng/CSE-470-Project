<?php
// Feature 11: Wishlist Management
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

$user = $_SESSION['username'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'remove' && isset($_POST['item_name'])) {
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE username = ? AND item_name = ?");
        $stmt->bind_param("ss", $user, $_POST['item_name']);
        $stmt->execute();
        $stmt->close();
    } elseif ($action === 'move_to_cart' && isset($_POST['item_name'], $_POST['item_price'])) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $name = $_POST['item_name'];
        $price = (float)$_POST['item_price'];
        if (isset($_SESSION['cart'][$name])) {
            $_SESSION['cart'][$name]['qty']++;
        } else {
            $_SESSION['cart'][$name] = ['qty' => 1, 'price' => $price];
        }
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE username = ? AND item_name = ?");
        $stmt->bind_param("ss", $user, $name);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: wishlist.php");
    exit();
}

$stmt = $conn->prepare("SELECT item_name, item_price FROM wishlist WHERE username = ? ORDER BY added_at DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$items = [];
while ($row = $res->fetch_assoc()) $items[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Wishlist - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:700px; margin:0 auto; }
    .item { display:flex; justify-content:space-between; align-items:center; background:#fff; padding:14px 18px; border-radius:12px; margin-bottom:10px; box-shadow:0 4px 8px rgba(0,0,0,0.08); flex-wrap:wrap; gap:8px; }
    .item-name { font-weight:bold; }
    .item-price { color:#888; margin-left:10px; }
    form.inline { display:inline-block; margin-left:8px; }
    button { padding:8px 14px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; }
    .btn-cart { background:#10b981; color:#fff; }
    .btn-remove { background:#ef4444; color:#fff; }
    .empty { text-align:center; color:#888; padding:30px; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-heart"></i> My Wishlist</h1>

    <?php if (empty($items)): ?>
        <p class="empty">Your wishlist is empty. Add items from <a href="pawmart.php">Paw Mart</a>.</p>
    <?php else: ?>
        <?php foreach ($items as $i): ?>
            <div class="item">
                <div><span class="item-name"><?php echo htmlspecialchars($i['item_name']); ?></span>
                <span class="item-price">$<?php echo number_format($i['item_price'], 2); ?></span></div>
                <div>
                    <form class="inline" method="POST">
                        <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($i['item_name']); ?>">
                        <input type="hidden" name="item_price" value="<?php echo $i['item_price']; ?>">
                        <button type="submit" name="action" value="move_to_cart" class="btn-cart">Move to Cart</button>
                    </form>
                    <form class="inline" method="POST">
                        <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($i['item_name']); ?>">
                        <button type="submit" name="action" value="remove" class="btn-remove">Remove</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
