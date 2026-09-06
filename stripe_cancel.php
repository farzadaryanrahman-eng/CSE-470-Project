<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Checkout cancelled - PawMart</title>
  <style>
    body { font-family: Arial, sans-serif; background: #e6f4ff; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .box { background: #fff; padding: 30px; border-radius: 12px; width: 320px; text-align: center; }
    a { display: inline-block; margin-top: 12px; background: #ff6fa5; color: #fff; text-decoration: none; padding: 10px 14px; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Checkout cancelled</h2>
    <p>No charge was made. Your cart is still here.</p>
    <a href="payment.php">Back to payment</a>
  </div>
</body>
</html>
