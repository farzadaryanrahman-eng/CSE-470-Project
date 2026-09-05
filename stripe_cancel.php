<?php
session_start();
require_once('DBconnect.php');

// Mark the most recent pending Stripe payment for this user as Cancelled
if (isset($_SESSION['username'])) {
    $stmt = $conn->prepare("UPDATE payments SET status='Cancelled' WHERE username=? AND status='Pending' ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Cancelled - Pet Care Zone</title>
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    .box { background:#ffd2e3; padding:30px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); width:300px; text-align:center; }
    a { display:inline-block; margin-top:16px; background:#ff6fa5; color:#fff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold; }
</style>
</head>
<body>
    <div class="box">
        <h2>Payment Cancelled</h2>
        <p>No charge was made. Your cart is still saved.</p>
        <a href="pawmart.php">Return to Shop</a>
    </div>
</body>
</html>
