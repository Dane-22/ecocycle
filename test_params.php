<?php
echo '<h2>Test URL Parameters</h2>';

echo '<h3>Current URL:</h3>';
echo 'Full URL: ' . htmlspecialchars($_SERVER['REQUEST_URI']) . '<br>';
echo 'Protocol: ' . htmlspecialchars($_SERVER['REQUEST_SCHEME'] ?? 'unknown') . '<br>';
echo 'Host: ' . htmlspecialchars($_SERVER['HTTP_HOST']) . '<br>';
echo 'Query String: ' . htmlspecialchars($_SERVER['QUERY_STRING']) . '<br>';

echo '<h3>GET Parameters:</h3>';
echo '<pre>';
print_r($_GET);
echo '</pre>';

echo '<h3>Test Links:</h3>';
$test_token = 'f17b8b4e8b6f25fec397396cc3c4fef8';
$link1 = 'http://' . $_SERVER['HTTP_HOST'] . '/ecocycle/test_params.php?token=' . $test_token . '&type=buyer';
$link2 = 'http://' . $_SERVER['HTTP_HOST'] . '/ecocycle/test_params.php?token=' . urlencode($test_token) . '&type=buyer';

echo '<a href="' . htmlspecialchars($link1) . '">Test Link 1 (no encoding)</a><br>';
echo '<a href="' . htmlspecialchars($link2) . '">Test Link 2 (with encoding)</a><br>';

echo '<h3>Manual Test:</h3>';
echo '<p>Try this URL directly: <a href="http://' . $_SERVER['HTTP_HOST'] . '/ecocycle/test_params.php?token=' . $test_token . '&type=buyer">http://' . $_SERVER['HTTP_HOST'] . '/ecocycle/test_params.php?token=' . $test_token . '&type=buyer</a></p>';

echo '<h3>Test Reset Link:</h3>';
$reset_link = 'http://' . $_SERVER['HTTP_HOST'] . '/ecocycle/reset-password.php?token=' . $test_token . '&type=buyer';
echo '<a href="' . htmlspecialchars($reset_link) . '">Test Reset Password Link</a><br>';
?>
