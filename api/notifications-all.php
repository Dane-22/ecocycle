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

$notifications = [];

// Redeem notifications (approved/rejected)
try {
    $stmt = $pdo->prepare("SELECT br.order_id, bp.name, br.status, br.redeemed_at as date, 'redeem' as type FROM bardproductsredeem br JOIN bardproducts bp ON br.product_id = bp.id WHERE br.user_id = ? AND br.user_type = ? AND (br.status = 'approved' OR br.status = 'rejected') ORDER BY br.redeemed_at DESC LIMIT 10");
    $stmt->execute([$user_id, $user_type]);
    $redeem = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($redeem as $r) { $notifications[] = $r; }
} catch (PDOException $e) {}

// EcoCoins earned notifications (for sellers)
if ($user_type === 'seller') {
    try {
        // Ensure amount column exists in notifications table
        try {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2)");
        } catch (Exception $e) {
            // Column might already exist
        }
        
        $stmt = $pdo->prepare("SELECT order_id, product_name as name, COALESCE(amount, 0) as ecocoins_earned, created_at as date, 'ecocoin' as type FROM notifications WHERE user_id = ? AND user_type = 'seller' AND type = 'ecocoin' ORDER BY created_at DESC LIMIT 15");
        $stmt->execute([$user_id]);
        $ecocoins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($ecocoins as $e) { $notifications[] = $e; }
    } catch (PDOException $e) {}
}

// Order status updates (show all products ordered by this user with status updates)
try {
    $order_sql = "SELECT oi.order_id, p.name, oi.status, oi.order_item_id, oi.product_id, oi.updated_at as date, 'order' as type FROM order_items oi JOIN orders o ON oi.order_id = o.order_id JOIN products p ON oi.product_id = p.product_id WHERE o.buyer_id = ? AND oi.status IN ('shipped','delivered','confirmed','cancelled') ORDER BY oi.updated_at DESC";
    $stmt = $pdo->prepare($order_sql);
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orders as $o) { $notifications[] = $o; }
} catch (PDOException $e) {
    $order_sql_error = $e->getMessage();
}


// Sort all notifications by date desc
usort($notifications, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

// Limit to 30 most recent
$notifications = array_slice($notifications, 0, 30);

// Debug: output session and notifications for troubleshooting
if (isset($_GET['debug'])) {
    echo '<pre>';
    echo 'Session: ' . print_r($_SESSION, true) . "\n\n";
    echo 'Order SQL: ' . (isset($order_sql) ? $order_sql : 'N/A') . "\n";
    echo 'Order SQL Param: ' . (isset($user_id) ? $user_id : 'N/A') . "\n";
    if (isset($order_sql_error)) echo 'Order SQL Error: ' . $order_sql_error . "\n";
    echo 'Notifications: ' . print_r($notifications, true) . "\n\n";
    echo '</pre>';
    exit;
}
echo json_encode(['success' => true, 'notifications' => $notifications]);
