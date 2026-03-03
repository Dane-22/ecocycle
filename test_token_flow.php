<?php
require_once 'config/database.php';

echo '<h2>Test Token Flow</h2>';

// Step 1: Create a fresh token
echo '<h3>Step 1: Create Fresh Token</h3>';
$test_token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
$test_email = 'raynanbucsitcorial@gmail.com'; // Use existing user

echo 'Token: ' . htmlspecialchars($test_token) . '<br>';
echo 'Expires: ' . htmlspecialchars($expires) . '<br>';
echo 'Email: ' . htmlspecialchars($test_email) . '<br>';

// Step 2: Save to database
echo '<h3>Step 2: Save to Database</h3>';
try {
    $stmt = $pdo->prepare("UPDATE Buyers SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
    $result = $stmt->execute([$test_token, $expires, $test_email]);
    echo '<p style="color: green;">✓ Token saved to database</p>';
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Save failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Step 3: Retrieve from database
echo '<h3>Step 3: Retrieve from Database</h3>';
try {
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE email = ?");
    $stmt->execute([$test_email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo '<p style="color: green;">✓ User found</p>';
        echo 'DB Token: ' . htmlspecialchars($user['reset_token']) . '<br>';
        echo 'DB Expires: ' . htmlspecialchars($user['reset_token_expires']) . '<br>';
        
        // Check if tokens match
        if ($user['reset_token'] === $test_token) {
            echo '<p style="color: green;">✓ Tokens match exactly</p>';
        } else {
            echo '<p style="color: red;">✗ Tokens do not match!</p>';
            echo 'Original: ' . htmlspecialchars($test_token) . '<br>';
            echo 'Database: ' . htmlspecialchars($user['reset_token']) . '<br>';
        }
    } else {
        echo '<p style="color: red;">✗ User not found</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Retrieve failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Step 4: Test the exact query from reset-password.php
echo '<h3>Step 4: Test Reset Query</h3>';
try {
    $stmt = $pdo->prepare("SELECT * FROM Buyers WHERE reset_token = ?");
    $stmt->execute([$test_token]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo '<p style="color: green;">✓ Token found by query</p>';
        
        // Test PHP time comparison
        $expires_time = strtotime($user['reset_token_expires']);
        $current_time = time();
        
        echo 'Expires timestamp: ' . $expires_time . '<br>';
        echo 'Current timestamp: ' . $current_time . '<br>';
        echo 'Time difference: ' . ($expires_time - $current_time) . ' seconds<br>';
        
        if ($expires_time > $current_time) {
            echo '<p style="color: green;">✓ Token should be valid (not expired)</p>';
        } else {
            echo '<p style="color: red;">✗ Token is expired</p>';
        }
    } else {
        echo '<p style="color: red;">✗ Token not found by query</p>';
    }
} catch (Exception $e) {
    echo '<p style="color: red;">✗ Query failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

// Step 5: Generate test links
echo '<h3>Step 5: Test Links</h3>';
$link1 = 'reset-password.php?token=' . urlencode($test_token) . '&type=buyer';
$link2 = 'reset-password.php?token=' . $test_token . '&type=buyer';

echo '<h4>With URL encoding:</h4>';
echo '<a href="' . htmlspecialchars($link1) . '" target="_blank">' . htmlspecialchars($link1) . '</a><br>';

echo '<h4>Without URL encoding:</h4>';
echo '<a href="' . htmlspecialchars($link2) . '" target="_blank">' . htmlspecialchars($link2) . '</a><br>';

// Step 6: Show current URL parameters
echo '<h3>Step 6: Current URL Parameters</h3>';
if (isset($_GET['token'])) {
    echo 'Received token: ' . htmlspecialchars($_GET['token']) . '<br>';
    echo 'Token length: ' . strlen($_GET['token']) . '<br>';
    
    // Compare with original
    if ($_GET['token'] === $test_token) {
        echo '<p style="color: green;">✓ Received token matches original</p>';
    } else {
        echo '<p style="color: red;">✗ Received token does not match original</p>';
        echo 'Expected: ' . htmlspecialchars($test_token) . '<br>';
        echo 'Received: ' . htmlspecialchars($_GET['token']) . '<br>';
    }
} else {
    echo '<p>No token parameter in URL</p>';
}

echo '<h3>Step 7: Manual Test</h3>';
echo '<p>Copy this token and test manually:</p>';
echo '<input type="text" value="' . htmlspecialchars($test_token) . '" style="width: 400px; font-family: monospace;" readonly>';
echo '<br><small>Use this token to test reset-password.php manually</small>';
?>
