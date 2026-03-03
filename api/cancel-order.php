<?php
require_once '../config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    if ($order_id && $product_id) {
        try {
            $stmt = $pdo->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ? AND product_id = ?");
            $stmt->execute([$order_id, $product_id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing order_id or product_id']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
