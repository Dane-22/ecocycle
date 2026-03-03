<?php
require_once 'config/database.php';

echo '<h2>Database Structure Check</h2>';

echo '<h3>Buyers Table Columns:</h3>';
echo '<table border="1" style="border-collapse: collapse; margin-bottom: 20px;">';
echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>';

$stmt = $pdo->query('DESCRIBE Buyers');
while($row = $stmt->fetch()) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>Sellers Table Columns:</h3>';
echo '<table border="1" style="border-collapse: collapse; margin-bottom: 20px;">';
echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>';

$stmt = $pdo->query('DESCRIBE Sellers');
while($row = $stmt->fetch()) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
    echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<h3>Login Attempts Table Columns:</h3>';
echo '<table border="1" style="border-collapse: collapse;">';
echo '<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th></tr>';

try {
    $stmt = $pdo->query('DESCRIBE login_attempts');
    while($row = $stmt->fetch()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['Field']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Type']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Key']) . '</td>';
        echo '</tr>';
    }
} catch (Exception $e) {
    echo '<tr><td colspan="4">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
echo '</table>';
?>
