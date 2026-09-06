<?php
function pawmart_table_exists($conn, $table) {
    $safe = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '$safe'");
    return $res && $res->num_rows > 0;
}

function pawmart_log_payment($conn, $username, $amount, $method, $status, $stripe_session_id, $items_json) {
    if (!pawmart_table_exists($conn, 'payments')) {
        return 'no_table';
    }

    $sessionId = ($stripe_session_id !== null && $stripe_session_id !== '') ? $stripe_session_id : null;

    if ($sessionId) {
        $check = $conn->prepare('SELECT id FROM payments WHERE stripe_session_id = ? LIMIT 1');
        $check->bind_param('s', $sessionId);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();
        if ($exists) {
            return 'duplicate';
        }
    }

    if ($sessionId === null) {
        $stmt = $conn->prepare(
            'INSERT INTO payments (username, amount, method, status, stripe_session_id, items_json) VALUES (?, ?, ?, ?, NULL, ?)'
        );
        $stmt->bind_param('sdsss', $username, $amount, $method, $status, $items_json);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO payments (username, amount, method, status, stripe_session_id, items_json) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sdssss', $username, $amount, $method, $status, $sessionId, $items_json);
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? 'inserted' : 'error';
}

function pawmart_log_cart_payment($conn, $username, $method, $status, $stripe_session_id, $cart) {
    $amount = 0.0;
    $items = [];
    foreach ($cart as $name => $data) {
        $qty = (int) $data['qty'];
        $price = (float) $data['price'];
        $amount += $qty * $price;
        $items[] = ['name' => $name, 'qty' => $qty, 'price' => $price];
    }
    return pawmart_log_payment(
        $conn,
        $username,
        $amount,
        $method,
        $status,
        $stripe_session_id,
        json_encode($items)
    );
}

function pawmart_decrement_stock($conn, $cart) {
    $stmt = $conn->prepare('UPDATE items SET item_quantity = item_quantity - ? WHERE item_name = ?');
    if (!$stmt) {
        return false;
    }
    foreach ($cart as $name => $data) {
        $qty = (int) $data['qty'];
        $stmt->bind_param('is', $qty, $name);
        $stmt->execute();
    }
    $stmt->close();
    return true;
}
