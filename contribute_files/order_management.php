<?php
// Feature 17: Order Management
// Requires payment.php to be recording orders/order_items (see updated
// payment.php provided alongside this file).
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}
$user = $_SESSION['username'];

$stmt = $conn->prepare("SELECT id, total_amount, payment_method, status, order_date FROM orders WHERE username = ? ORDER BY order_date DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$orders = [];
while ($row = $res->fetch_assoc()) $orders[] = $row;
$stmt->close();

foreach ($orders as &$order) {
    $itemStmt = $conn->prepare("SELECT item_name, quantity, price FROM order_items WHERE order_id = ?");
    $itemStmt->bind_param("i", $order['id']);
    $itemStmt->execute();
    $itemRes = $itemStmt->get_result();
    $order['items'] = [];
    while ($item = $itemRes->fetch_assoc()) $order['items'][] = $item;
    $itemStmt->close();
}
unset($order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:800px; margin:0 auto; }
    .order-card { background:#fff; border-radius:15px; box-shadow:0 4px 8px rgba(0,0,0,0.1); margin-bottom:16px; overflow:hidden; }
    .order-header { background:#ff6fa5; color:#fff; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px; }
    .order-body { padding:14px 18px; }
    .order-items-list { list-style:none; padding:0; margin:0; }
    .order-items-list li { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #f0f0f0; }
    .status-pill { padding:4px 12px; border-radius:12px; font-size:0.85em; font-weight:bold; }
    .status-Completed { background:#d4f8e8; color:#1a7d4c; }
    .status-Pending { background:#fff3cd; color:#856404; }
    .empty { text-align:center; color:#888; padding:30px; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-receipt"></i> My Orders</h1>

    <?php if (empty($orders)): ?>
        <p class="empty">You haven't placed any orders yet. Visit <a href="pawmart.php">Paw Mart</a>!</p>
    <?php else: ?>
        <?php foreach ($orders as $o): ?>
            <div class="order-card">
                <div class="order-header">
                    <span>Order #<?php echo $o['id']; ?> &middot; <?php echo htmlspecialchars($o['order_date']); ?></span>
                    <span class="status-pill status-<?php echo htmlspecialchars($o['status']); ?>"><?php echo htmlspecialchars($o['status']); ?></span>
                </div>
                <div class="order-body">
                    <ul class="order-items-list">
                        <?php foreach ($o['items'] as $it): ?>
                            <li><span><?php echo htmlspecialchars($it['item_name']); ?> &times; <?php echo $it['quantity']; ?></span>
                                <span>$<?php echo number_format($it['quantity'] * $it['price'], 2); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="text-align:right; font-weight:bold; margin-top:10px;">
                        Total: $<?php echo number_format($o['total_amount'], 2); ?> &middot;
                        Paid via <?php echo htmlspecialchars(ucfirst($o['payment_method'])); ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
