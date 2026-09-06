<?php
session_start();
require_once __DIR__ . '/DBconnect.php';
require_once __DIR__ . '/stripe_lib.php';

if (!isset($_SESSION['username'])) {
    header('Location: register.html');
    exit();
}

if (empty($_SESSION['cart'])) {
    header('Location: pawmart.php');
    exit();
}

if (!pawmart_stripe_configured()) {
    $setup = true;
} else {
    $setup = false;
    $base = pawmart_app_base_url();
    $success = $base . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}';
    $cancel = $base . '/stripe_cancel.php';
    list($session, $error) = pawmart_stripe_create_checkout_session(
        $_SESSION['username'],
        $_SESSION['cart'],
        $success,
        $cancel
    );
    if ($session && !empty($session['id']) && !empty($session['url'])) {
        $_SESSION['stripe_cart_by_session'][$session['id']] = $_SESSION['cart'];
        header('Location: ' . $session['url']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Stripe Checkout - PawMart</title>
  <style>
    body { font-family: Arial, sans-serif; background: #e6f4ff; margin: 0; padding: 40px 16px; }
    .box { max-width: 480px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 12px; }
    h2 { margin-top: 0; }
    code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; }
    a { color: #635bff; }
    .back { display: inline-block; margin-top: 16px; color: #555; text-decoration: none; }
  </style>
</head>
<body>
  <div class="box">
    <h2>Stripe Checkout</h2>
    <?php if ($setup): ?>
      <p>Stripe test keys are not configured yet.</p>
      <ol>
        <li>Create a free Stripe account and open <a href="https://dashboard.stripe.com/test/apikeys" target="_blank" rel="noopener">Test API keys</a>.</li>
        <li>Copy <code>stripe_config.local.example.php</code> to <code>stripe_config.local.php</code>.</li>
        <li>Paste your <code>sk_test_</code> and <code>pk_test_</code> keys there.</li>
        <li>Reload this page.</li>
      </ol>
    <?php else: ?>
      <p>Could not start Stripe Checkout<?php echo isset($error) ? ': ' . htmlspecialchars($error) : '.'; ?></p>
      <p>Confirm PHP curl is enabled and the test secret key is correct.</p>
    <?php endif; ?>
    <a class="back" href="payment.php">Back to payment</a>
  </div>
</body>
</html>
