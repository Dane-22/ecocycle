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

// Get order details
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$shipping_address = isset($_POST['shipping_address']) ? $_POST['shipping_address'] : (isset($_GET['shipping_address']) ? $_GET['shipping_address'] : '');
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cod'; // Read from POST or default to 'cod'

// Validate inputs
if ($amount <= 0 || empty($shipping_address)) {
	header('Location: home.php?error=invalid_order_data');
	exit();
}

try {
	// Start transaction
	$pdo->beginTransaction();

	$user_id = $_SESSION['user_id'];

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
	$stmt = $pdo->prepare(
		"INSERT INTO Orders (buyer_id, total_amount, status, shipping_address, payment_method, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
	);
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
	}

	// Commit transaction
	$pdo->commit();

	// Log COD transaction with all required fields
	$user_type = 'buyer';
	$action = 'order_placed';
	$description = 'COD order placed, Order ID: ' . $order_id_db . ', Amount: ' . $amount;
	$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
	$stmt = $pdo->prepare("INSERT INTO transaction_logs (user_id, user_type, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
	$stmt->execute([$user_id, $user_type, $action, $description, $ip_address, $user_agent]);

	// Save each purchased item in purchase_history table
	foreach ($cart_items as $item) {
		$stmt = $pdo->prepare('INSERT INTO purchase_history (order_id, buyer_id, product_id, seller_id, quantity, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
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


	// Calculate product subtotal (exclude shipping fee)
	$product_subtotal = 0;
	foreach ($cart_items as $item) {
		$product_subtotal += $item['price'] * $item['quantity'];
	}
	// Calculate ecocoins to award (fractional allowed)
	$ecocoins_awarded = round($product_subtotal / 100, 2); // 2 decimal places for precision
	if ($ecocoins_awarded > 0) {
		// Update user's ecocoins balance
		$stmt = $pdo->prepare('UPDATE Buyers SET ecocoins_balance = ecocoins_balance + ? WHERE buyer_id = ?');
		$stmt->execute([$ecocoins_awarded, $user_id]);
	}

	// Optionally clear cart
	$stmt = $pdo->prepare('DELETE FROM Cart WHERE buyer_id = ?');
	$stmt->execute([$user_id]);

	// Redirect to order receipt or confirmation, passing ecocoins_awarded
	header('Location: order-receipt.php?order_id=' . urlencode($order_id_db) . '&ecocoins_awarded=' . urlencode($ecocoins_awarded));
	exit();

} catch (Exception $e) {
	// Only roll back if a transaction is active
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	$error_detail = urlencode($e->getMessage());
	error_log('COD Payment Error: ' . $e->getMessage());
	header('Location: home.php?error=cod_payment_failed&detail=' . $error_detail);
	exit();
}
?>