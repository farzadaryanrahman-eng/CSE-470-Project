<?php
// Feature 16: Stripe Online Payment Gateway (step 2 - confirm success)
// Stripe redirects here after a successful card payment. We verify the
// session with Stripe's API (never trust the redirect alone), then run
// the same stock-decrement + order recording logic as the cash flow.
session_start();
require_once('DBconnect.php');
require_once('stripe_config.php');
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['username']) || !isset($_GET['session_id'])) {
    header("Location: pawmart.php");
    exit();
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$message = "";

try {
    $checkoutSession = \Stripe\Checkout\Session::retrieve($_GET['session_id']);

    if ($checkoutSession->payment_status === 'paid' && !empty($_SESSION['cart'])) {
        $user = $_SESSION['username'];
        $totalCost = 0;
        foreach ($_SESSION['cart'] as $data) $totalCost += $data['qty'] * $data['price'];

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
            $method = "card";
            $orderStmt = $conn->prepare("INSERT INTO orders (username, total_amount, payment_method, status) VALUES (?, ?, ?, ?)");
            $orderStmt->bind_param("sdss", $user, $totalCost, $method, $status);
            $orderStmt->execute();
            $orderId = $orderStmt->insert_id;
            $orderStmt->close();

            $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $name => $data) {
                $itemStmt->bind_param("isid", $orderId, $name, $data['qty'], $data['price']);
                $itemStmt->execute();
            }
            $itemStmt->close();

            // Mark the pending payment row (created in create_checkout_session.php) as Completed
            $payStmt = $conn->prepare("UPDATE payments SET status='Completed', order_id=? WHERE stripe_session_id=?");
            $payStmt->bind_param("is", $orderId, $checkoutSession->id);
            $payStmt->execute();
            $payStmt->close();

            $conn->commit();
            $_SESSION['cart'] = [];
            $message = "Payment successful via card! Your order has been placed.";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Payment succeeded with Stripe, but there was an error recording your order: " . $e->getMessage() . ". Please contact support.";
        }
    } else {
        $message = "Payment was not completed.";
    }
} catch (Exception $e) {
    $message = "Could not verify payment: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Result - Pet Care Zone</title>
<style>
    body { font-family:'Segoe UI',Arial,sans-serif; background:#e6f4ff; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
    .box { background:#ffd2e3; padding:30px; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.1); width:320px; text-align:center; }
    a { display:inline-block; margin-top:16px; background:#10b981; color:#fff; text-decoration:none; padding:10px 16px; border-radius:8px; font-weight:bold; }
</style>
</head>
<body>
    <div class="box">
        <h2><i class="fas fa-check-circle"></i> Payment Result</h2>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="order_management.php">View My Orders</a>
    </div>
</body>
</html>
