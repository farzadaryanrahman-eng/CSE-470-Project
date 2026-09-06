<?php
session_start();
require_once __DIR__ . '/DBconnect.php';
require_once __DIR__ . '/stripe_lib.php';
require_once __DIR__ . '/payment_log.php';

if (!isset($_SESSION['username'])) {
    header('Location: register.html');
    exit();
}

$message = '';
$ok = false;
$sessionId = isset($_GET['session_id']) ? trim($_GET['session_id']) : '';

if ($sessionId === '' || !pawmart_stripe_configured()) {
    $message = 'Missing Stripe session. If you completed payment, check Payment History in a moment.';
} else {
    list($session, $error) = pawmart_stripe_retrieve_session($sessionId);
    if ($error || !$session) {
        $message = 'Could not verify Stripe session: ' . ($error ?: 'unknown error');
    } elseif (($session['payment_status'] ?? '') !== 'paid' && ($session['status'] ?? '') !== 'complete') {
        $message = 'Payment is not complete yet. Status: ' . ($session['payment_status'] ?? $session['status'] ?? 'unknown');
    } else {
        $cart = [];
        if (!empty($_SESSION['stripe_cart_by_session'][$sessionId]) && is_array($_SESSION['stripe_cart_by_session'][$sessionId])) {
            $cart = $_SESSION['stripe_cart_by_session'][$sessionId];
        } elseif (!empty($_SESSION['cart'])) {
            $cart = $_SESSION['cart'];
        }

        $amount = isset($session['amount_total']) ? ((int) $session['amount_total']) / 100 : 0;
        $itemsJson = json_encode(array_map(function ($name, $data) {
            return ['name' => $name, 'qty' => (int) $data['qty'], 'price' => (float) $data['price']];
        }, array_keys($cart), $cart));

        $result = pawmart_log_payment(
            $conn,
            $_SESSION['username'],
            $amount,
            'stripe',
            'paid',
            $sessionId,
            $itemsJson
        );

        if ($result === 'inserted' && !empty($cart)) {
            pawmart_decrement_stock($conn, $cart);
            $_SESSION['cart'] = [];
            unset($_SESSION['stripe_cart_by_session'][$sessionId]);
        } elseif ($result === 'duplicate') {
            $_SESSION['cart'] = [];
            unset($_SESSION['stripe_cart_by_session'][$sessionId]);
        } elseif ($result === 'no_table') {
            $message = 'Payment succeeded, but the payments table is missing. Import schema_additions_video_stripe.sql.';
        }

        if ($result === 'inserted' || $result === 'duplicate') {
            $ok = true;
            $message = $result === 'duplicate'
                ? 'This Stripe payment was already recorded.'
                : 'Stripe payment successful. Stock updated and saved to payment history.';
        } elseif ($message === '') {
            $message = 'Payment verification failed while saving history.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment Success - PawMart</title>
  <style>
    body { font-family: Arial, sans-serif; background: #e6f4ff; margin: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .box { background: #ffd2e3; padding: 30px; border-radius: 12px; width: 360px; text-align: center; }
    a { display: block; margin-top: 12px; background: #10b981; color: #fff; text-decoration: none; padding: 10px; border-radius: 8px; }
    a.alt { background: #635bff; }
  </style>
</head>
<body>
  <div class="box">
    <h2><?php echo $ok ? 'Payment complete' : 'Payment status'; ?></h2>
    <p><?php echo htmlspecialchars($message); ?></p>
    <a href="payment_history.php">View payment history</a>
    <a class="alt" href="pawmart.php">Return to shop</a>
  </div>
</body>
</html>
