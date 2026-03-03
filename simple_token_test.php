<?php
require_once 'config/database.php';

echo '<h2>Simple Token Test</h2>';

// Test 1: Check database time vs PHP time
echo '<h3>Time Comparison</h3>';
$db_time_query = $pdo->query("SELECT NOW() as db_time");
$db_time = $db_time_query->fetch()['db_time'];
$php_time = date('Y-m-d H:i:s');

echo 'Database NOW(): ' . htmlspecialchars($db_time) . '<br>';
echo 'PHP time: ' . htmlspecialchars($php_time) . '<br>';
echo 'Difference: ' . (strtotime($db_time) - strtotime($php_time)) . ' seconds<br>';

// Test 2: Create a token with 2 hour expiration to be safe
echo '<h3>Creating 2-Hour Token</h3>';
$test_token = bin2hex(random_bytes(16)); // Shorter for testing
$expires = date('Y-m-d H:i:s', strtotime('+2 hours')); // 2 hours instead of 1

echo 'Test Token: ' . htmlspecialchars($test_token) . '<br>';
echo 'Expires: ' . htmlspecialchars($expires) . '<br>';

// Test 3: Save to database
echo '<h3>Saving to Database</h3>';
try {
    $stmt = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ? WHERE buyer_id = 2");
    $stmt->execute([$test_token, $expires]);
    echo '<p style="color: green;">✓ Token saved</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Save failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test 4: Test the exact query
echo '<h3>Testing Query</h3>';
try {
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$test_token]);
    $user = $stmt->fetch();
    
    echo 'Query: SELECT * FROM Buyers WHERE reset_token = ? AND reset_token_expires > NOW()<br>';
    echo 'Results: ' . ($user ? 'FOUND' : 'NOT FOUND') . '<br>';
    
    if ($user) {
        echo 'User ID: ' . $user['buyer_id'] . '<br>';
        echo 'Token Expires: ' . $user['reset_token_expires'] . '<br>';
    }
    
    // Test without time comparison
    echo '<h4>Without Time Check:</h4>';
    $stmt2 = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ?");
    $stmt2->execute([$test_token]);
    $user2 = $stmt2->fetch();
    echo 'Results without time: ' . ($user2 ? 'FOUND' : 'NOT FOUND') . '<br>';
    
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Query failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Test 5: Generate test link
echo '<h3>Test Link</h3>';
$test_link = 'reset-password.php?token=' . urlencode($test_token) . '&type=buyer';
echo '<a href="' . htmlspecialchars($test_link) . '" target="_blank">Test Reset Link</a><br>';
echo '<small>This should work for 2 hours</small>';

// Test 6: Show current tokens in database
echo '<h3>Current Tokens in Database</h3>';
$stmt = $pdo->query("SELECT buyer_id, email, reset_token, reset_token_expires FROM Buyers WHERE reset_token IS NOT NULL");
while ($row = $stmt->fetch()) {
    $is_expired = strtotime($row['reset_token_expires']) < time();
    echo 'ID: ' . $row['buyer_id'] . ', Email: ' . htmlspecialchars($row['email']) . ', Expires: ' . htmlspecialchars($row['reset_token_expires']) . ' (' . ($is_expired ? 'EXPIRED' : 'VALID') . ')<br>';
}
?>
