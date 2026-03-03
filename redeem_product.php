<?php
// filepath: c:\xampp\htdocs\EcocycleNluc\redeem_product.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header('Content-Type: application/json');
require 'config/database.php';
require 'config/phpmailer_config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$productId = $_POST['productId'] ?? null;
$cost = $_POST['cost'] ?? null;
$orderId = $_POST['orderId'] ?? null;

if (
    !isset($productId, $cost, $orderId) ||
    !is_numeric($productId) ||
    !is_numeric($cost) ||
    empty($orderId)
) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Insert into Redemptions table
    $stmt = $pdo->prepare("INSERT INTO Redemptions (user_id, product_id, ecocoins_spent, order_id, redeemed_at) VALUES (?, ?, ?, ?, NOW())");
    $success = $stmt->execute([$user_id, $productId, $cost, $orderId]);

    if (!$success) {
        throw new Exception('Database error inserting into Redemptions');
    }

    // Insert into bardproductsredeem table
    $stmt = $pdo->prepare("INSERT INTO bardproductsredeem 
        (user_id, user_type, product_id, quantity, ecocoins_spent, status, cost, order_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $user_id,
        'buyer',
        $productId,
        1,
        $cost,
        'pending',
        $cost,
        $orderId
    ]);

    if (!$success) {
        throw new Exception('Database error inserting into bardproductsredeem');
    }

    // Deduct EcoCoins from user's balance (Buyers table)
    $stmt = $pdo->prepare("UPDATE Buyers SET ecocoins_balance = ecocoins_balance - ? WHERE buyer_id = ?");
    $success = $stmt->execute([$cost, $user_id]);

    if (!$success) {
        throw new Exception('Database error updating buyer ecocoins_balance');
    }

    // Commit transaction
    $pdo->commit();

    // Fetch user details for email
    $stmt = $pdo->prepare("SELECT fullname, email FROM Buyers WHERE buyer_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Fetch product name for email
        $stmt = $pdo->prepare("SELECT name FROM bardproducts WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            // Send confirmation email
            sendRedemptionConfirmationEmail(
                $user['email'],
                $user['fullname'],
                $product['name'],
                $orderId,
                $cost
            );
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}