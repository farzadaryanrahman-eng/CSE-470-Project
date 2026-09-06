<?php
// Stripe TEST keys. Copy stripe_config.local.example.php to stripe_config.local.php
// and paste keys from https://dashboard.stripe.com/test/apikeys
if (file_exists(__DIR__ . '/stripe_config.local.php')) {
    require_once __DIR__ . '/stripe_config.local.php';
}

if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
}
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
}
