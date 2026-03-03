<?php
require_once 'config/database.php';

echo '<h2>Debug Reset Token System</h2>';

// Test creating a token
echo '<h3>1. Creating Test Token</h3>';
$test_token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
echo 'Token: ' . htmlspecialchars($test_token) . '<br>';
echo 'Expires: ' . htmlspecialchars($expires) . '<br>';
echo 'Current Time: ' . date('Y-m-d H:i:s') . '<br>';

// Test database insert
echo '<h3>2. Testing Database Insert</h3>';
try {
    $stmt = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ? WHERE buyer_id = 2");
    $stmt->execute([$test_token, $expires]);
    echo '<p style="color: green;">✓ Token saved to database</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Error saving token: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test database retrieval
echo '<h3>3. Testing Database Retrieval</h3>';
try {
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$test_token]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo '<p style="color: green;">✓ Token found and valid</p>';
        echo 'DB Token Expires: ' . htmlspecialchars($user['reset_token_expires']) . '<br>';
        echo 'DB NOW(): ' . date('Y-m-d H:i:s') . '<br>';
        
        // Check the comparison manually
        $db_time = strtotime($user['reset_token_expires']);
        $now_time = strtotime(date('Y-m-d H:i:s'));
        echo 'DB Timestamp: ' . $db_time . '<br>';
        echo 'Now Timestamp: ' . $now_time . '<br>';
        echo 'Difference: ' . ($db_time - $now_time) . ' seconds<br>';
        
        if ($db_time > $now_time) {
            echo '<p style="color: green;">✓ Token should be valid</p>';
        } else {
            echo '<p style="color: red;">✗ Token should be expired</p>';
        }
    } else {
        echo '<p style="color: red;">✗ Token not found or expired</p>';
        
        // Check what's in the database
        $stmt = $pdo->prepare("SELECT reset_token, reset_token_expires FROM Buyers WHERE buyer_id = 2");
        $stmt->execute();
        $data = $stmt->fetch();
        echo 'Token in DB: ' . htmlspecialchars($data['reset_token'] ?? 'NULL') . '<br>';
        echo 'Expires in DB: ' . htmlspecialchars($data['reset_token_expires'] ?? 'NULL') . '<br>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Error retrieving token: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test the exact query from reset-password.php
echo '<h3>4. Testing Exact Query</h3>';
try {
    $sql = "SELECT * FROM Buyers WHERE reset_token = ? AND reset_token_expires > NOW()";
    echo 'SQL: ' . htmlspecialchars($sql) . '<br>';
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$test_token]);
    $results = $stmt->fetchAll();
    
    echo 'Results found: ' . count($results) . '<br>';
    
    // Show all buyers with tokens
    echo '<h4>All Buyers with Reset Tokens:</h4>';
    $stmt = $pdo->query("SELECT buyer_id, email, reset_token, reset_token_expires FROM Buyers WHERE reset_token IS NOT NULL");
    while ($row = $stmt->fetch()) {
        echo 'ID: ' . $row['buyer_id'] . ', Email: ' . htmlspecialchars($row['email']) . ', Expires: ' . htmlspecialchars($row['reset_token_expires']) . '<br>';
    }
    
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Query error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<h3>5. Test Direct Link</h3>';
$test_link = 'reset-password.php?token=' . urlencode($test_token) . '&type=buyer';
echo '<a href="' . htmlspecialchars($test_link) . '">Test Reset Link</a><br>';
echo '<small>This link should work for 1 hour</small>';
?>
