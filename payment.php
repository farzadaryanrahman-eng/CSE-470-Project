<?php
session_start();

require_once('DBconnect.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if (isset($_POST['confirm_payment'])) {
    if (!empty($_SESSION['cart'])) {
        $conn->begin_transaction();
        try {
            foreach ($_SESSION['cart'] as $name => $data) {
                $qty = $data['qty'];
                $sql = "UPDATE items SET item_quantity = item_quantity - $qty WHERE item_name = '$name'";
                mysqli_query($conn, $sql);
            }
            $conn->commit();
            $_SESSION['cart'] = [];
            $message = "Payment Successful! Stock updated.";
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
              <label for="card">Card</label>
              <input type="radio" id="card" name="payment" value="card">
            </div>
            <button type="submit" name="confirm_payment" class="confirm-btn">Confirm Payment</button>
        </form>
    <?php endif; ?>
  </div>
</body>
</html>