<?php
session_start();
require_once __DIR__ . '/DBconnect.php';
require_once __DIR__ . '/payment_log.php';

if (!isset($_SESSION['username'])) {
    header('Location: register.html');
    exit();
}

$user = $_SESSION['username'];
$rows = [];
$tableMissing = !pawmart_table_exists($conn, 'payments');

if (!$tableMissing) {
    $stmt = $conn->prepare(
        'SELECT id, amount, method, status, stripe_session_id, items_json, created_at
         FROM payments WHERE username = ? ORDER BY created_at DESC, id DESC'
    );
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

function pawmart_format_items($json) {
    $items = json_decode($json, true);
    if (!is_array($items) || empty($items)) {
        return '—';
    }
    $parts = [];
    foreach ($items as $item) {
        $name = isset($item['name']) ? $item['name'] : 'item';
        $qty = isset($item['qty']) ? (int) $item['qty'] : 1;
        $parts[] = htmlspecialchars($name) . ' × ' . $qty;
    }
    return implode(', ', $parts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment History - PawMart</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #e6f4ff; margin: 0; padding: 20px; }
    h1 { text-align: center; color: #333; }
    .container { max-width: 900px; margin: 0 auto; }
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; }
    th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
    th { background: #ff6fa5; color: #fff; }
    .empty { text-align: center; color: #777; padding: 30px; background: #fff; border-radius: 12px; }
    .warn { background: #fff3cd; color: #856404; padding: 12px; border-radius: 10px; margin-bottom: 16px; }
    .back-link { display: block; text-align: center; margin-top: 20px; text-decoration: none; color: #555; }
    .method { text-transform: capitalize; }
  </style>
</head>
<body>
<div class="container">
  <h1><i class="fas fa-file-invoice-dollar"></i> Payment History</h1>

  <?php if ($tableMissing): ?>
    <p class="warn">The <code>payments</code> table is missing. Import <code>schema_additions_video_stripe.sql</code> in phpMyAdmin, then refresh.</p>
  <?php elseif (empty($rows)): ?>
    <p class="empty">No payments yet. Pay from Paw Mart with Stripe or confirm cash/card checkout.</p>
  <?php else: ?>
    <table>
      <tr>
        <th>Date</th>
        <th>Items</th>
        <th>Method</th>
        <th>Status</th>
        <th>Amount</th>
      </tr>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['created_at']); ?></td>
          <td><?php echo pawmart_format_items($row['items_json']); ?></td>
          <td class="method"><?php echo htmlspecialchars($row['method']); ?></td>
          <td><?php echo htmlspecialchars($row['status']); ?></td>
          <td>$<?php echo number_format((float) $row['amount'], 2); ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>

  <a href="pawmart.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to shop</a>
  <a href="page2.php" class="back-link">Back to dashboard</a>
</div>
</body>
</html>
