<?php
/**
 * Migration: Add order_id column to ecocoins_transactions table
 * Run this file once to add the column if it doesn't exist
 */

require_once __DIR__ . '/../config/database.php';

try {
    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM ecocoins_transactions LIKE 'order_id'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $pdo->exec("ALTER TABLE ecocoins_transactions ADD COLUMN order_id INT(11) NULL AFTER transaction_id");
        echo "✓ Successfully added order_id column to ecocoins_transactions table";
    } else {
        echo "✓ order_id column already exists";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
