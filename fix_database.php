<?php
require_once 'config/database.php';

echo '<h2>Fixing Database Columns</h2>';

// SQL commands to add missing columns
$sql_commands = [
    // Create login_attempts table
    "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login_identifier VARCHAR(255) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        INDEX idx_login_identifier (login_identifier),
        INDEX idx_attempt_time (attempt_time)
    )",
    
    // Add columns to Buyers table
    "ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL",
    "ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL", 
    "ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS reset_required TINYINT(1) DEFAULT 0",
    "ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0",
    "ALTER TABLE Buyers ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME NULL",
    
    // Add columns to Sellers table  
    "ALTER TABLE Sellers ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL",
    "ALTER TABLE Sellers ADD COLUMN IF NOT EXISTS reset_token_expires DATETIME NULL",
    "ALTER TABLE Sellers ADD COLUMN IF NOT EXISTS reset_required TINYINT(1) DEFAULT 0", 
    "ALTER TABLE Sellers ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0",
    "ALTER TABLE Sellers ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME NULL"
];

foreach ($sql_commands as $index => $sql) {
    try {
        $pdo->exec($sql);
        echo '<p style="color: green;">✓ Command ' . ($index + 1) . ' executed successfully</p>';
    } catch (PDOException $e) {
        echo '<p style="color: orange;">→ Command ' . ($index + 1) . ' (may already exist): ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<h3>Database Fix Complete!</h3>';
echo '<p><a href="check_db_structure.php">Verify Database Structure</a></p>';
echo '<p><a href="login.php">Test Login System</a></p>';
?>
