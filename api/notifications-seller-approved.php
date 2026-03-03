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
    // Fetch products for this seller that have been approved by admin
    $stmt = $pdo->prepare("SELECT product_id, name, description, image_url, created_at FROM Products WHERE seller_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$seller_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'products' => $products]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
