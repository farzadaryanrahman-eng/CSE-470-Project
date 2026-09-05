<?php
// Feature 16: Stripe Online Payment Gateway (step 1 - create Checkout Session)
// Requires: composer require stripe/stripe-php, and real keys in stripe_config.php
session_start();
require_once('DBconnect.php');
require_once('stripe_config.php');
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['username'])) {
    header("Location: register.html");
    exit();
}

if (empty($_SESSION['cart'])) {
    header("Location: pawmart.php");
    exit();
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$lineItems = [];
foreach ($_SESSION['cart'] as $name => $data) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => $name],
            'unit_amount' => (int) round($data['price'] * 100), // Stripe uses cents
        ],
        'quantity' => $data['qty'],
    ];
}

// Build absolute base URL so Stripe can redirect back to this app
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

try {
    $checkoutSession = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $baseUrl . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $baseUrl . '/stripe_cancel.php',
        'customer_email' => null, // could be filled with the consumer's Email if desired
        'metadata' => ['username' => $_SESSION['username']],
    ]);

    // Record a Pending payment row so it shows in payment_history.php even
    // if the user abandons checkout on Stripe's page.
    $total = 0;
    foreach ($_SESSION['cart'] as $data) $total += $data['qty'] * $data['price'];
    $stmt = $conn->prepare("INSERT INTO payments (username, amount, method, status, stripe_session_id) VALUES (?, ?, 'card', 'Pending', ?)");
    $stmt->bind_param("sds", $_SESSION['username'], $total, $checkoutSession->id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $checkoutSession->url);
    exit();
} catch (Exception $e) {
    header("Location: pawmart.php?stripe_error=" . urlencode($e->getMessage()));
    exit();
}
