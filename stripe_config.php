<?php
// Feature 16: Stripe config
//
// SETUP REQUIRED before Stripe checkout will work:
//   1. Sign up at https://dashboard.stripe.com (free, test mode is fine
//      for a class project -- no real charges happen in test mode).
//   2. Get your TEST keys from https://dashboard.stripe.com/test/apikeys
//   3. Paste them below, replacing the placeholder strings.
//   4. Install the Stripe PHP library from your project root:
//        composer require stripe/stripe-php
//      (this creates a vendor/ folder that create_checkout_session.php
//      and stripe_success.php both require)
//
// Never commit real secret keys to a public GitHub repo -- for a class
// project, add this exact filename to .gitignore, or use environment
// variables instead of hardcoding them here.

define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_SECRET_KEY_HERE');
