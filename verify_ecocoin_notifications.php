<?php
session_start();
require_once 'config/database.php';
require_once 'config/session_check.php';

if (!isSeller()) {
    header("Location: login.php");
    exit();
}

$seller_id = getCurrentUserId();
$info = [];

try {
    // Check if notifications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    $table_exists = $stmt->fetch() ? true : false;
    $info['notifications_table_exists'] = $table_exists;
    
    if ($table_exists) {
        // Check table structure
        $stmt = $pdo->query("DESCRIBE notifications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $info['table_columns'] = $columns;
        
        // Ensure amount column exists
        try {
            $pdo->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS amount DECIMAL(10,2)");
            $info['amount_column_added'] = true;
        } catch (Exception $e) {
            $info['amount_column_error'] = $e->getMessage();
        }
    }
    
    // Count ecocoin notifications for this seller
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, SUM(amount) as total_amount 
            FROM notifications 
            WHERE user_id = ? 
            AND user_type = 'seller' 
            AND type = 'ecocoin'
        ");
        $stmt->execute([$seller_id]);
        $result = $stmt->fetch();
        $info['ecocoin_notification_count'] = $result['count'];
        $info['total_ecocoins'] = $result['total_amount'];
    } catch (Exception $e) {
        $info['count_error'] = $e->getMessage();
    }
    
    // Get all ecocoin notifications
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            AND user_type = 'seller' 
            AND type = 'ecocoin'
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$seller_id]);
        $info['ecocoin_notifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $info['notifications_error'] = $e->getMessage();
    }
    
} catch (Exception $e) {
    $info['general_error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($info, JSON_PRETTY_PRINT);
?>
