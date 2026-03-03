<?php
echo '<h2>Minimal Reset Test</h2>';

echo '<h3>URL Parameters Received:</h3>';
echo 'REQUEST_URI: ' . htmlspecialchars($_SERVER['REQUEST_URI']) . '<br>';
echo 'QUERY_STRING: ' . htmlspecialchars($_SERVER['QUERY_STRING']) . '<br>';

echo '<h3>GET Parameters:</h3>';
echo '<pre>';
print_r($_GET);
echo '</pre>';

$token = $_GET['token'] ?? 'NOT SET';
$type = $_GET['type'] ?? 'NOT SET';

echo '<h3>Extracted Values:</h3>';
echo 'Token: ' . htmlspecialchars($token) . '<br>';
echo 'Type: ' . htmlspecialchars($type) . '<br>';

if ($token && $type) {
    echo '<p style="color: green;">✓ Parameters received successfully!</p>';
    
    // Test database connection
    try {
        require_once 'config/database.php';
        $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo '<p style="color: green;">✓ Token found in database!</p>';
            echo 'User: ' . htmlspecialchars($user['email']) . '<br>';
            echo 'Expires: ' . htmlspecialchars($user['reset_token_expires']) . '<br>';
        } else {
            echo '<p style="color: red;">✗ Token not found in database</p>';
        }
    } catch (Exception $e) {
        echo '<p style="color: red;">✗ Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p style="color: red;">✗ Missing parameters</p>';
}

echo '<h3>Test Link:</h3>';
$test_token = 'f17b8b4e8b6f25fec397396cc3c4fef8';
$test_link = 'reset-minimal.php?token=' . $test_token . '&type=buyer';
echo '<a href="' . htmlspecialchars($test_link) . '">Test Reset Link</a>';
?>
