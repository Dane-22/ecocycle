<?php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';

$response = [
    'success' => false,
    'items' => [],
    'subtotal' => 0,
    'shipping_fee' => 50,
    'handling_fee' => 0,
    'total' => 0
];

if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer') {
    $buyer_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare('
        SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price
        FROM cart c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.buyer_id = ?
    ');
    $stmt->execute([$buyer_id]);
    $cart_items = $stmt->fetchAll();
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $response['items'][] = [
            'name' => $item['name'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)$item['price'],
            'total' => (float)$item_total
        ];
        $subtotal += $item_total;
    }
    $handling_fee = round($subtotal * 0.05);
    $shipping_fee = 50;
    $total = $subtotal + $shipping_fee + $handling_fee;
    $response['success'] = true;
    $response['subtotal'] = $subtotal;
    $response['shipping_fee'] = $shipping_fee;
    $response['handling_fee'] = $handling_fee;
    $response['total'] = $total;
}

echo json_encode($response);