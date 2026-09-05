<?php
// UPDATED: adds "wishlist" POST action (Feature 11) alongside the existing
// cart actions, and a small nav bar linking to the new feature pages.
// Everything else is unchanged from the original file.
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
        // NEW: Feature 11 - Wishlist Management
        if (!isset($_SESSION['username'])) {
            header("Location: register.html");
            exit;
        }
        $stmt = $conn->prepare("INSERT IGNORE INTO wishlist (username, item_name, item_price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $_SESSION['username'], $name, $price);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

function formatCurrency($n) {
    return '$' . number_format($n, 2);
}

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
        .star { 
            width: 18px; 
            height: 18px; 
            background: #facc15; 
            border-radius: 50%; 
            display: inline-block; 
            margin-top: 6px; 
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
    </style> 
</head> 
<body> 
    <header>
        Paw Mart
        <div class="nav-links">
            <a href="wishlist.php"><i class="fas fa-heart"></i> Wishlist</a>
            <a href="order_management.php"><i class="fas fa-receipt"></i> My Orders</a>
        </div>
    </header>
    <div class="layout"> 
        <div>
            <div class="panel"> 
                <h2>Food</h2> 
                <div class="item"> 
                    <span>Dry Dog Food</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Dry Dog Food'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Dry Dog Food">
                            <input type="hidden" name="item_price" value="12.99">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Dry Dog Food">
                            <input type="hidden" name="item_price" value="12.99">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div> 
                <div class="item"> 
                    <span>Cat Treats</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Cat Treats'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Cat Treats">
                            <input type="hidden" name="item_price" value="5.49">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Cat Treats">
                            <input type="hidden" name="item_price" value="5.49">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div> 
            </div> 
            <div class="panel">
                <h2>Medicine</h2> 
                <div class="item"> 
                    <span>Vitamin Supplements</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Vitamin Supplements'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Vitamin Supplements">
                            <input type="hidden" name="item_price" value="9.99">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Vitamin Supplements">
                            <input type="hidden" name="item_price" value="9.99">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div> 
                <div class="item">
                    <span>Worming Tablets</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Worming Tablets'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Worming Tablets">
                            <input type="hidden" name="item_price" value="7.50">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Worming Tablets">
                            <input type="hidden" name="item_price" value="7.50">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div> 
                <span class="star" title="Featured"></span> 
            </div>
            <div class="panel"> 
                <h2>Accessories</h2> 
                <div class="item"> 
                    <span>Pet Collar</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Pet Collar'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Pet Collar">
                            <input type="hidden" name="item_price" value="6.99">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Pet Collar">
                            <input type="hidden" name="item_price" value="6.99">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div> 
                <div class="item"> 
                    <span>Chew Toys</span> 
                    <div class="item-actions">
                        <a class="review-link" href="reviews.php?item=<?php echo urlencode('Chew Toys'); ?>">Reviews</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Chew Toys">
                            <input type="hidden" name="item_price" value="8.25">
                            <button type="submit" name="action" value="wishlist" class="wish-btn" title="Add to wishlist">&hearts;</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="item_name" value="Chew Toys">
                            <input type="hidden" name="item_price" value="8.25">
                            <button type="submit" name="action" value="add">Add</button>
                        </form>
                    </div>
                </div>
            </div> 
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
