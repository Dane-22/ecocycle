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
    // Mark notifications as viewed by setting session variable
    $_SESSION['notifications_viewed'] = true;
    
    echo json_encode(['success' => true, 'message' => 'Notifications marked as viewed']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
