<?php
// UPDATED again for Feature 16 (Stripe) and Feature 18 (Payment History):
// - "Cash" still works exactly as before, and now also logs a row into
//   `payments` so it shows up in payment_history.php.
// - "Card" now redirects to create_checkout_session.php for a real Stripe
//   Checkout flow instead of just being a cosmetic radio button.
session_start();

require_once('DBconnect.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if (isset($_POST['confirm_payment'])) {
    $paymentMethod = $_POST['payment'] ?? 'cash';

    if ($paymentMethod === 'card') {
        header("Location: create_checkout_session.php");
        exit();
    }

    // Cash flow (unchanged logic, now also logs to `payments`)
    if (!empty($_SESSION['cart'])) {
        $user = $_SESSION['username'] ?? 'guest';
        $totalCost = 0;
        foreach ($_SESSION['cart'] as $data) {
            $totalCost += $data['qty'] * $data['price'];
        }

        $conn->begin_transaction();
        try {
            foreach ($_SESSION['cart'] as $name => $data) {
                $qty = $data['qty'];
                $stmt = $conn->prepare("UPDATE items SET item_quantity = item_quantity - ? WHERE item_name = ?");
                $stmt->bind_param("is", $qty, $name);
                $stmt->execute();
                $stmt->close();
            }

            $status = "Completed";
            $orderStmt = $conn->prepare("INSERT INTO orders (username, total_amount, payment_method, status) VALUES (?, ?, ?, ?)");
            $orderStmt->bind_param("sdss", $user, $totalCost, $paymentMethod, $status);
            $orderStmt->execute();
            $orderId = $orderStmt->insert_id;
            $orderStmt->close();

            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $name => $data) {
                $itemStmt->bind_param("isid", $orderId, $name, $data['qty'], $data['price']);
                $itemStmt->execute();
            }
            $itemStmt->close();

            // NEW: log this cash payment too (Feature 18 - Payment History)
            $payStmt = $conn->prepare("INSERT INTO payments (order_id, username, amount, method, status) VALUES (?, ?, ?, 'cash', 'Completed')");
            $payStmt->bind_param("isd", $orderId, $user, $totalCost);
            $payStmt->execute();
            $payStmt->close();

            $conn->commit();
            $_SESSION['cart'] = [];
            $message = "Payment Successful! Stock updated. <a href='order_management.php' style='color:#059669;font-weight:bold;'>View your order &rarr;</a>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Cart is empty!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Page</title>
  <style>
   
    body {
      font-family: Arial, sans-serif;
      background: #e6f4ff;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .payment-container {
      background: #ffd2e3; 
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      width: 300px;
      text-align: center;
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
    }

    .option {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f9f9f9;
      padding: 10px 15px;
      margin: 10px 0;
      border-radius: 8px;
      cursor: pointer;
      transition: 0.3s;
    }

    .option:hover {
      background: #e6f0ff;
    }

    .option label {
      flex: 1;
      text-align: left;
      cursor: pointer;
      font-size: 16px;
      color: #444;
    }

    input[type="radio"] {
      accent-color: #ff69b4; 
      border: none; 
      outline: none; 
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    .confirm-btn {
      margin-top: 20px;
      padding: 12px;
      width: 100%;
      background: #ff6fa5;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      cursor: pointer;
      transition: 0.3s;
    }

    .confirm-btn:hover {
      background: #5DE2E7;
    }
  
  </style>
</head>
<body>
  <div class="payment-container">
    <h2>Select Payment Method</h2>
    <?php if ($message): ?>
        <p class="status-msg"><?php echo $message; ?></p>
        <a href="pawmart.php" class="home-btn">Return to Shop</a>
    <?php else: ?>
        <form method="POST">
            <div class="option">
              <label for="cash">Cash</label>
              <input type="radio" id="cash" name="payment" value="cash" checked>
            </div>
            <div class="option">
              <label for="card">Card (Stripe)</label>
              <input type="radio" id="card" name="payment" value="card">
            </div>
            <button type="submit" name="confirm_payment" class="confirm-btn">Confirm Payment</button>
        </form>
    <?php endif; ?>
  </div>
</body>
</html>
