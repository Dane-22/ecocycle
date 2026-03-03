<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

try {
    $stmt = $pdo->prepare("SELECT br.order_id, bp.name, br.status, br.redeemed_at FROM bardproductsredeem br JOIN bardproducts bp ON br.product_id = bp.id WHERE br.user_id = ? AND br.user_type = ? AND (br.status = 'approved' OR br.status = 'rejected') ORDER BY br.redeemed_at DESC LIMIT 10");
    $stmt->execute([$user_id, $user_type]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
