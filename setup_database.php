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

// Add columns to buyers table
echo '<h3>Adding reset columns to buyers table...</h3>';
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
        $stmt = $pdo->prepare("SHOW COLUMNS FROM buyers LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE buyers ADD COLUMN {$column} {$definition}";
            $pdo->exec($sql);
            echo '<p style="color: green;">✓ Added ' . htmlspecialchars($column) . ' to buyers table</p>';
        } else {
            echo '<p style="color: blue;">→ ' . htmlspecialchars($column) . ' already exists in buyers table</p>';
        }
    } catch (PDOException $e) {
        echo '<p style="color: red;">✗ Error adding ' . htmlspecialchars($column) . ' to buyers: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

// Add columns to sellers table
echo '<h3>Adding reset columns to sellers table...</h3>';
foreach ($buyer_columns as $column => $definition) {
    try {
        // Check if column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM sellers LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            $sql = "ALTER TABLE sellers ADD COLUMN {$column} {$definition}";
            $pdo->exec($sql);
            echo '<p style="color: green;">✓ Added ' . htmlspecialchars($column) . ' to sellers table</p>';
        } else {
            echo '<p style="color: blue;">→ ' . htmlspecialchars($column) . ' already exists in sellers table</p>';
        }
    } catch (PDOException $e) {
        echo '<p style="color: red;">✗ Error adding ' . htmlspecialchars($column) . ' to sellers: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}

echo '<h3>Setup Complete!</h3>';
echo '<p><a href="check_db_structure.php">Check Database Structure</a></p>';
echo '<p><a href="login.php">Go to Login Page</a></p>';
?>
