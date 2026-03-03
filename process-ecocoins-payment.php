<?php
session_start();
require_once 'config/database.php';

// Ensure database connection is available
if (!isset($pdo)) {
    die("Database connection failed");
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home.php');
    exit();
}

// Get payment details
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$order_id = isset($_POST['order_id']) ? $_POST['order_id'] : '';
$shipping_address = isset($_POST['shipping_address']) ? $_POST['shipping_address'] : '';
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'ecocoins'; // Read from POST or default to 'ecocoins'

// Validate inputs
if ($amount <= 0 || empty($order_id)) {
    header('Location: home.php?error=invalid_payment_data');
    exit();
}

// Calculate EcoCoins required (1 Peso = 1 EcoCoin)
$ecocoins_required = $amount;

try {
    // Start transaction
    $pdo->beginTransaction();
    
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];
    $user_email = $_SESSION['email'];
    $user_username = $_SESSION['username'];
    
    // Get user's current EcoCoins balance
    $user_balance = 0;
    $buyer_balance = 0;
    $seller_balance = 0;
    
    // Check buyer balance
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Buyers WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$user_email, $user_username]);
    $row = $stmt->fetch();
    if ($row) {
        $buyer_balance = (float)$row['ecocoins_balance'];
        $user_balance += $buyer_balance;
    }
    
    // Check seller balance
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Sellers WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$user_email, $user_username]);
    $row = $stmt->fetch();
    if ($row) {
        $seller_balance = (float)$row['ecocoins_balance'];
        $user_balance += $seller_balance;
    }
    
    // Check if user has sufficient balance
    if ($user_balance < $ecocoins_required) {
        $pdo->rollBack();
        header('Location: ecocoins-payment.php?amount=' . urlencode($amount) . '&order_id=' . urlencode($order_id) . '&error=insufficient_balance');
        exit();
    }
    
    // Get cart items for the buyer
    $stmt = $pdo->prepare('
        SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price, p.image_url, p.stock_quantity, p.seller_id
        FROM Cart c
        JOIN Products p ON c.product_id = p.product_id
        WHERE c.buyer_id = ?
    ');
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll();
    
    if (empty($cart_items)) {
        $pdo->rollBack();
        header('Location: home.php?error=empty_cart');
        exit();
    }
    
    // Check stock availability
    foreach ($cart_items as $item) {
        if ($item['stock_quantity'] < $item['quantity']) {
            $pdo->rollBack();
            header('Location: home.php?error=insufficient_stock&product=' . urlencode($item['name']));
            exit();
        }
    }
    
    // Create order
    $stmt = $pdo->prepare('
        INSERT INTO Orders (buyer_id, total_amount, status, shipping_address, payment_method, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$user_id, $amount, 'pending', $shipping_address, $payment_method]);
    $order_id_db = $pdo->lastInsertId();
    
    // Create order items and update product stock
    foreach ($cart_items as $item) {
        // Insert order item
        $stmt = $pdo->prepare('
            INSERT INTO Order_Items (order_id, product_id, quantity, price)
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$order_id_db, $item['product_id'], $item['quantity'], $item['price']]);
        
        // Update product stock
        $stmt = $pdo->prepare('
            UPDATE Products 
            SET stock_quantity = stock_quantity - ? 
            WHERE product_id = ?
        ');
        $stmt->execute([$item['quantity'], $item['product_id']]);

        // Insert into purchase_history
        $stmt = $pdo->prepare('
            INSERT INTO purchase_history (order_id, buyer_id, product_id, seller_id, quantity, price, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $stmt->execute([
            $order_id_db,
            $user_id,
            $item['product_id'],
            $item['seller_id'],
            $item['quantity'],
            $item['price'],
            'pending'
        ]);
    }
    
    // Deduct EcoCoins from user balance
    // First, try to deduct from buyer balance
    if ($buyer_balance > 0) {
        $deduct_from_buyer = min($buyer_balance, $ecocoins_required);
        $stmt = $pdo->prepare('
            UPDATE Buyers 
            SET ecocoins_balance = ecocoins_balance - ? 
            WHERE email = ? OR username = ?
        ');
        $stmt->execute([$deduct_from_buyer, $user_email, $user_username]);
        
        // Log the transaction (with order_id if column exists)
        try {
            $stmt = $pdo->prepare('
                INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description, order_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$user_id, 'buyer', -$deduct_from_buyer, 'spend', "Payment for Order #$order_id_db", $order_id_db]);
        } catch (Exception $e) {
            // Fallback if order_id column doesn't exist
            $stmt = $pdo->prepare('
                INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$user_id, 'buyer', -$deduct_from_buyer, 'spend', "Payment for Order #$order_id_db"]);
        }
        
        $ecocoins_required -= $deduct_from_buyer;
    }
    
    // If still need to deduct more, deduct from seller balance
    if ($ecocoins_required > 0 && $seller_balance > 0) {
        $deduct_from_seller = min($seller_balance, $ecocoins_required);
        $stmt = $pdo->prepare('
            UPDATE Sellers 
            SET ecocoins_balance = ecocoins_balance - ? 
            WHERE email = ? OR username = ?
        ');
        $stmt->execute([$deduct_from_seller, $user_email, $user_username]);
        
        // Log the transaction (with order_id if column exists)
        try {
            $stmt = $pdo->prepare('
                INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description, order_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$user_id, 'seller', -$deduct_from_seller, 'spend', "Payment for Order #$order_id_db", $order_id_db]);
        } catch (Exception $e) {
            // Fallback if order_id column doesn't exist
            $stmt = $pdo->prepare('
                INSERT INTO ecocoins_transactions (user_id, user_type, amount, transaction_type, description)
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$user_id, 'seller', -$deduct_from_seller, 'spend', "Payment for Order #$order_id_db"]);
        }
    }
    
    // Clear cart
    $stmt = $pdo->prepare('DELETE FROM Cart WHERE buyer_id = ?');
    $stmt->execute([$user_id]);
    
    // Log transaction
    $stmt = $pdo->prepare('
        INSERT INTO transaction_logs (user_id, user_type, action, description, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $user_id, 
        $user_type, 
        'ecocoins_payment', 
        "Paid ₱$amount using EcoCoins for Order #$order_id_db",
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    // Redirect to receipt page
    header('Location: order-receipt.php?order_id=' . urlencode($order_id_db) . '&payment_method=ecocoins');
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Log error
    error_log("EcoCoins payment error: " . $e->getMessage());
    
    header('Location: ecocoins-payment.php?amount=' . urlencode($amount) . '&order_id=' . urlencode($order_id) . '&error=payment_failed');
    exit();
}
?>