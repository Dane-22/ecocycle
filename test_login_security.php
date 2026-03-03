<?php
// Test script for login security functionality
require_once 'config/database.php';

echo "<h1>Login Security Test</h1>";

// Test 1: Create login_attempts table
echo "<h2>Test 1: Creating login_attempts table</h2>";
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
    echo "<p style='color: green;'>✓ login_attempts table created successfully</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error creating table: " . $e->getMessage() . "</p>";
}

// Test 2: Check PHPMailer files exist
echo "<h2>Test 2: Checking PHPMailer files</h2>";
$required_files = [
    'PHPMailer/src/PHPMailer.php',
    'PHPMailer/src/SMTP.php',
    'PHPMailer/src/Exception.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p style='color: green;'>✓ $file exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $file missing</p>";
    }
}

// Test 3: Test database connection
echo "<h2>Test 3: Database connection</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test 4: Test login attempt recording
echo "<h2>Test 4: Testing login attempt recording</h2>";
try {
    $test_identifier = 'test_user_' . time();
    $stmt = $pdo->prepare("INSERT INTO login_attempts (login_identifier, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$test_identifier, '127.0.0.1', 'Test Browser']);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM login_attempts WHERE login_identifier = ?");
    $stmt->execute([$test_identifier]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✓ Login attempt recording works</p>";
    } else {
        echo "<p style='color: red;'>✗ Login attempt recording failed</p>";
    }
    
    // Clean up test data
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE login_identifier = ?");
    $stmt->execute([$test_identifier]);
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Login attempt test failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Summary</h2>";
echo "<p>All tests completed. If all items show green, the login security system is ready to use.</p>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
