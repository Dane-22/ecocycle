<?php
require_once 'config/database.php';

echo '<h2>Database Setup for Login Security</h2>';

// Create login_attempts table
echo '<h3>Creating login_attempts table...</h3>';
try {
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        login_identifier VARCHAR(255) NOT NULL,
        attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        ip_address VARCHAR(45),
        user_agent TEXT,
        INDEX idx_login_identifier (login_identifier),
        INDEX idx_attempt_time (attempt_time)
    )";
    
    $pdo->exec($sql);
    echo '<p style="color: green;">✓ login_attempts table created successfully</p>';
} catch (PDOException $e) {
    echo '<p style="color: red;">✗ Error creating login_attempts table: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Add columns to Buyers table
echo '<h3>Adding reset columns to Buyers table...</h3>';
$buyer_columns = [
    'reset_token' => 'VARCHAR(255) NULL',
    'reset_token_expires' => 'DATETIME NULL',
    'reset_required' => 'TINYINT(1) DEFAULT 0',
    'failed_attempts' => 'INT DEFAULT 0',
    'last_failed_attempt' => 'DATETIME NULL'
];

foreach ($buyer_columns as $column => $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM Buyers LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE Buyers ADD COLUMN {$column} {$definition}";
            $pdo->exec($sql);
            echo '<p style="color: green;">✓ Added ' . htmlspecialchars($column) . ' to Buyers table</p>';
        } else {
            echo '<p style="color: blue;">→ ' . htmlspecialchars($column) . ' already exists in Buyers table</p>';
        }
    } catch (PDOException $e) {
        echo '<p style="color: red;">✗ Error adding ' . htmlspecialchars($column) . ' to Buyers: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// Add columns to Sellers table
echo '<h3>Adding reset columns to Sellers table...</h3>';
foreach ($buyer_columns as $column => $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM Sellers LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE Sellers ADD COLUMN {$column} {$definition}";
            $pdo->exec($sql);
            echo '<p style="color: green;">✓ Added ' . htmlspecialchars($column) . ' to Sellers table</p>';
        } else {
            echo '<p style="color: blue;">→ ' . htmlspecialchars($column) . ' already exists in Sellers table</p>';
        }
    } catch (PDOException $e) {
        echo '<p style="color: red;">✗ Error adding ' . htmlspecialchars($column) . ' to Sellers: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<h3>Setup Complete!</h3>';
echo '<p><a href="check_db_structure.php">Check Database Structure</a></p>';
echo '<p><a href="login.php">Go to Login Page</a></p>';
?>
