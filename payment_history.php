<?php
// Feature 18: Payment History
session_start();
require_once('DBconnect.php');

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}
$user = $_SESSION['username'];

$stmt = $conn->prepare("SELECT amount, method, status, stripe_session_id, created_at FROM payments WHERE username = ? ORDER BY created_at DESC");
$stmt->bind_param("s", $user);
$stmt->execute();
$res = $stmt->get_result();
$payments = [];
while ($row = $res->fetch_assoc()) $payments[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment History - Pet Care Zone</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; margin:0; padding:20px; }
    h1 { text-align:center; color:#333; }
    .container { max-width:800px; margin:0 auto; }
    table { width:100%; border-collapse:collapse; background:#fff; border-radius:15px; overflow:hidden; box-shadow:0 4px 8px rgba(0,0,0,0.1); }
    th, td { padding:12px; text-align:left; border-bottom:1px solid #eee; }
    th { background:#ffb6c1; color:#fff; }
    .status-Completed { color:#27ae60; font-weight:bold; }
    .status-Pending { color:#e67e22; font-weight:bold; }
    .status-Cancelled { color:#e74c3c; font-weight:bold; }
    .method-badge { padding:3px 10px; border-radius:12px; font-size:0.8em; color:#fff; }
    .method-cash { background:#27ae60; }
    .method-card { background:#3498db; }
    .empty { text-align:center; color:#888; padding:30px; }
    .back-link { display:block; text-align:center; margin-top:20px; text-decoration:none; color:#555; }
</style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-file-invoice-dollar"></i> Payment History</h1>

    <?php if (empty($payments)): ?>
        <p class="empty">No payments recorded yet.</p>
    <?php else: ?>
    <table>
        <tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr>
        <?php foreach ($payments as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
            <td>$<?php echo number_format($p['amount'], 2); ?></td>
            <td><span class="method-badge method-<?php echo htmlspecialchars($p['method']); ?>"><?php echo htmlspecialchars(ucfirst($p['method'])); ?></span></td>
            <td class="status-<?php echo htmlspecialchars($p['status']); ?>"><?php echo htmlspecialchars($p['status']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <a href="page2.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
