<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Location: home.php');
    exit();
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$shipping_address = isset($_POST['shipping_address']) ? trim($_POST['shipping_address']) : '';
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod';

// Handle receipt data if provided for GCash (base64 data URL expected)
$savedReceiptPath = null;
if (!empty($_POST['receipt_data']) && $payment_method === 'gcash') {
    $receiptData = $_POST['receipt_data'];
    // Expect data URL like: data:image/png;base64,AAAA...
    if (preg_match('/^data:(image\/(png|jpeg|jpg));base64,/', $receiptData, $matches)) {
        $mimeType = $matches[1];
        $allowedExt = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg'];
        $ext = isset($allowedExt[$mimeType]) ? $allowedExt[$mimeType] : 'png';
        $dataPos = strpos($receiptData, ',');
        $base64 = substr($receiptData, $dataPos + 1);
        $decoded = base64_decode($base64);

        // Check decoded size (<= 5MB)
        if ($decoded !== false && strlen($decoded) <= 5 * 1024 * 1024) {
            $uploadDir = __DIR__ . '/uploads/receipts/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0755, true);
            }
            try {
                $filename = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            } catch (Exception $e) {
                $filename = time() . '_' . uniqid() . '.' . $ext;
            }
            $fullPath = $uploadDir . $filename;
            $saved = @file_put_contents($fullPath, $decoded);
            if ($saved !== false) {
                // Store web relative path
                $savedReceiptPath = 'uploads/receipts/' . $filename;
            }
        }
    }
}

if ($product_id <= 0 || $quantity <= 0 || empty($shipping_address)) {
    header('Location: buycheckout.php');
    exit();
}

$stmt = $pdo->prepare('SELECT product_id, price, stock_quantity FROM Products WHERE product_id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['stock_quantity'] < $quantity) {
    header('Location: buycheckout.php?error=out_of_stock');
    exit();
}

$total_price = $product['price'] * $quantity;
$handling_fee = round($total_price * 0.05);
$shipping_fee = 50;
$order_total = $total_price + $handling_fee + $shipping_fee;

// Handle EcoCoins payment validation
if ($payment_method === 'ecocoins') {
    // Calculate EcoCoins required (10 pesos = 1 ecocoin)
    $ecocoins_required = $order_total / 10;
    
    // Fetch user's current EcoCoins balance
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Buyers WHERE buyer_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $buyer = $stmt->fetch();
    $user_balance = $buyer ? (float)$buyer['ecocoins_balance'] : 0;
    
    // Check if user has sufficient EcoCoins
    if ($user_balance < $ecocoins_required) {
        header('Location: buycheckout.php?error=insufficient_ecocoins&required=' . $ecocoins_required . '&balance=' . $user_balance);
        exit();
    }
}

// Insert order
$stmt = $pdo->prepare('INSERT INTO Orders (buyer_id, shipping_address, payment_method, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
$stmt->execute([
    $_SESSION['user_id'],
    $shipping_address,
    $payment_method,
    $order_total,
    'pending'
]);
$order_id = $pdo->lastInsertId();

// Insert order item
$stmt = $pdo->prepare('INSERT INTO Order_Items (order_id, product_id, quantity, price, payment_receipt) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([
    $order_id,
    $product_id,
    $quantity,
    $product['price'],
    $savedReceiptPath
]);

// Update product stock
$stmt = $pdo->prepare('UPDATE Products SET stock_quantity = stock_quantity - ? WHERE product_id = ?');
$stmt->execute([$quantity, $product_id]);

// Award EcoCoins for purchase (1 EcoCoin per 100 pesos spent, decimals allowed)
$ecocoins_awarded = round($total_price / 100, 2);

// Deduct EcoCoins if payment method is EcoCoins
if ($payment_method === 'ecocoins') {
    $ecocoins_required = $order_total / 10;
    $stmt = $pdo->prepare('UPDATE Buyers SET ecocoins_balance = ecocoins_balance - ? WHERE buyer_id = ?');
    $stmt->execute([$ecocoins_required, $_SESSION['user_id']]);
    
    // Try to log transaction (with order_id if column exists)
    try {
        $stmt = $pdo->prepare('INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description, order_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], 'buyer', -$ecocoins_required, 'spend', "Payment for Order #$order_id", $order_id]);
    } catch (Exception $e) {
        // Fallback if order_id column doesn't exist
        $stmt = $pdo->prepare('INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$_SESSION['user_id'], 'buyer', -$ecocoins_required, 'spend', "Payment for Order #$order_id"]);
    }
}

// Award EcoCoins from purchase (separate from payment EcoCoins)
$stmt = $pdo->prepare('UPDATE Buyers SET ecocoins_balance = ecocoins_balance + ? WHERE buyer_id = ?');
$stmt->execute([$ecocoins_awarded, $_SESSION['user_id']]);

// Show SweetAlert and redirect with proper HTML structure
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success</title>
</head>
<body style="background:#fff;">
<script>
// Redirect directly to order receipt
window.location.href = "order-receipt.php?order_id=<?php echo $order_id; ?>&ecocoins_awarded=<?php echo $ecocoins_awarded; ?>&payment_method=<?php echo urlencode($payment_method); ?>";
</script>
</body>
</html>
