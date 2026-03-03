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
    // Ensure amount column exists in notifications table
    try {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2)");
    } catch (Exception $e) {
        // Column might already exist
    }
    
    // Fetch ecocoin notifications for this seller
    $stmt = $pdo->prepare("
        SELECT 
            id,
            order_id,
            product_id,
            product_name,
            COALESCE(amount, 0) as ecocoins_earned,
            created_at,
            'ecocoin' as type
        FROM notifications 
        WHERE user_id = ? 
        AND user_type = 'seller' 
        AND type = 'ecocoin' 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$seller_id]);
    $ecocoins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'ecocoins' => $ecocoins, 'count' => count($ecocoins)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


