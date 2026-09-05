<?php
require_once __DIR__ . '/stripe_config.php';

function pawmart_stripe_configured() {
    $key = defined('STRIPE_SECRET_KEY') ? STRIPE_SECRET_KEY : '';
    return is_string($key) && $key !== '' && strpos($key, 'sk_test_') === 0 && strpos($key, 'REPLACE_ME') === false;
}

function pawmart_app_base_url() {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim($dir, '/');
    return $scheme . '://' . $host . $dir;
}

function pawmart_stripe_request($method, $path, $params = null) {
    $url = 'https://api.stripe.com/v1' . $path;
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . STRIPE_SECRET_KEY,
        'Stripe-Version: 2024-06-20',
    ];
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    if (strtoupper($method) === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    }
    $raw = curl_exec($ch);
    $err = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [null, $err ?: 'Stripe request failed'];
    }
    $json = json_decode($raw, true);
    if ($code >= 400) {
        $msg = is_array($json) && isset($json['error']['message'])
            ? $json['error']['message']
            : ('Stripe HTTP ' . $code);
        return [null, $msg];
    }
    return [$json, null];
}

function pawmart_stripe_create_checkout_session($username, $cart, $successUrl, $cancelUrl) {
    $params = [
        'mode' => 'payment',
        'success_url' => $successUrl,
        'cancel_url' => $cancelUrl,
        'client_reference_id' => $username,
        'metadata[username]' => $username,
    ];
    $i = 0;
    foreach ($cart as $name => $data) {
        $qty = (int) $data['qty'];
        $amount = (int) round(((float) $data['price']) * 100);
        if ($qty < 1 || $amount < 1) {
            continue;
        }
        $params["line_items[$i][quantity]"] = $qty;
        $params["line_items[$i][price_data][currency]"] = 'usd';
        $params["line_items[$i][price_data][unit_amount]"] = $amount;
        $params["line_items[$i][price_data][product_data][name]"] = $name;
        $i++;
    }
    if ($i === 0) {
        return [null, 'Cart has no payable items'];
    }
    return pawmart_stripe_request('POST', '/checkout/sessions', $params);
}

function pawmart_stripe_retrieve_session($sessionId) {
    return pawmart_stripe_request('GET', '/checkout/sessions/' . rawurlencode($sessionId));
}
