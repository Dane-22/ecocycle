<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session_check.php';

if (!isSeller()) {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit();
}

$seller_id = getCurrentUserId();

try {
    // Fetch delivered products for this seller
    $stmt = $pdo->prepare("SELECT oi.product_id, p.name, p.description, p.image_url, oi.updated_at as delivered_at, oi.order_id FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE p.seller_id = ? AND oi.status = 'delivered' ORDER BY oi.updated_at DESC LIMIT 20");
    $stmt->execute([$seller_id]);
    $delivered = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'delivered' => $delivered]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
