<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

// Get order details
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

if ($order_id <= 0) {
    header('Location: home.php');
    exit();
}

// Fetch order details
try {
    // Fetch order info and buyer info
    $stmt = $pdo->prepare('
        SELECT o.*, b.fullname as buyer_name, b.email as buyer_email, b.phone_number, b.address as shipping_address
        FROM Orders o
        JOIN Buyers b ON o.buyer_id = b.buyer_id
        WHERE o.order_id = ?
    ');
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: home.php');
        exit();
    }

    // Fetch order items
    $stmt = $pdo->prepare('
        SELECT oi.*, p.name as product_name, p.image_url, s.fullname as seller_name
        FROM Order_Items oi
        JOIN Products p ON oi.product_id = p.product_id
        JOIN Sellers s ON p.seller_id = s.seller_id
        WHERE oi.order_id = ?
    ');
    $stmt->execute([$order_id]);

    $order_items = $stmt->fetchAll();

    // Calculate totals
    $subtotal = 0;
    foreach ($order_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $shipping_fee = 50;
    $handling_fee = ($subtotal) * 0.05;
    $total_amount = $subtotal + $shipping_fee + $handling_fee;

    // Fetch buyer's current EcoCoins balance for EcoCoins payment display
    $buyer_ecocoins_balance = 0;
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Buyers WHERE buyer_id = ?');
    $stmt->execute([$order['buyer_id']]);
    $buyer = $stmt->fetch();
    if ($buyer) {
        $buyer_ecocoins_balance = (float)$buyer['ecocoins_balance'];
    }

    // Normalize the current payment method
    $current_payment_method = $payment_method;
    if (empty($current_payment_method) && isset($order['payment_method'])) {
        $current_payment_method = $order['payment_method'];
    }

} catch (Exception $e) {
    header('Location: home.php');
    exit();
}

// Build receipt HTML for SweetAlert
$receipt_html = '';
$receipt_html .= '<div style="text-align: left; padding: 15px; max-width: 500px; margin: 0 auto;">';
$receipt_html .= '<div style="border-bottom: 2px solid #ddd; padding-bottom: 10px; margin-bottom: 10px;">';
$receipt_html .= '<h6 style="margin: 0; font-weight: bold;">Order #' . htmlspecialchars($order_id) . '</h6>';
$receipt_html .= '<small style="color: #666;">' . date('M j, Y g:i A', strtotime($order['created_at'])) . '</small>';
$receipt_html .= '</div>';

// Order Items
$receipt_html .= '<div style="margin-bottom: 15px;">';
$receipt_html .= '<h6 style="font-weight: bold; margin-bottom: 8px;">Items:</h6>';
foreach ($order_items as $item) {
    $receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;">';
    $receipt_html .= '<span>' . htmlspecialchars($item['product_name']) . ' (x' . $item['quantity'] . ')</span>';
    $receipt_html .= '<span>₱' . number_format($item['price'] * $item['quantity'], 2) . '</span>';
    $receipt_html .= '</div>';
}
$receipt_html .= '</div>';

// Summary
$receipt_html .= '<div style="border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 10px 0; margin-bottom: 15px;">';
$receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;">';
$receipt_html .= '<span>Subtotal:</span>';
$receipt_html .= '<span>₱' . number_format($subtotal, 2) . '</span>';
$receipt_html .= '</div>';
$receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 14px;">';
$receipt_html .= '<span>Shipping:</span>';
$receipt_html .= '<span>₱' . number_format($shipping_fee, 2) . '</span>';
$receipt_html .= '</div>';
$receipt_html .= '<div style="display: flex; justify-content: space-between; font-size: 14px;">';
$receipt_html .= '<span>Handling (5%):</span>';
$receipt_html .= '<span>₱' . number_format($handling_fee, 2) . '</span>';
$receipt_html .= '</div>';
$receipt_html .= '</div>';

// Total
$receipt_html .= '<div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 16px; margin-bottom: 15px;">';
$receipt_html .= '<span>Total:</span>';
$receipt_html .= '<span style="color: #28a745;">₱' . number_format($total_amount, 2) . '</span>';
$receipt_html .= '</div>';

// Payment Method
if ($current_payment_method === 'ecocoins') {
    $ecocoins_used = round($total_amount, 2);
    $receipt_html .= '<div style="background: #fff9e6; padding: 10px; border-radius: 5px; border-left: 4px solid #FFD700;">';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 5px;">';
    $receipt_html .= '<span style="font-weight: 500;"><i class="fas fa-coins" style="color: #FFD700;"></i> Payment Method:</span>';
    $receipt_html .= '<span style="font-weight: bold;">EcoCoins</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
    $receipt_html .= '<span>EcoCoins Used:</span>';
    $receipt_html .= '<span style="color: #FFD700; font-weight: bold;">' . number_format($ecocoins_used, 2) . '</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #ffd700;">';
    $receipt_html .= '<span style="font-weight: 500;">Remaining Balance:</span>';
    $receipt_html .= '<span style="color: #28a745; font-weight: bold;">' . number_format($buyer_ecocoins_balance, 2) . ' coins</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '</div>';
} elseif ($current_payment_method === 'gcash') {
    $ecocoins_earned = round($total_amount / 100, 2);
    $receipt_html .= '<div style="background: #e3f2fd; padding: 10px; border-radius: 5px; border-left: 4px solid #1976d2;">';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
    $receipt_html .= '<span style="font-weight: 500;">Payment Method:</span>';
    $receipt_html .= '<span style="font-weight: bold;">GCash</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #1976d2;">';
    $receipt_html .= '<span style="font-weight: 500;"><i class="fas fa-coins me-1" style="color: #FFD700;"></i>EcoCoins Earned:</span>';
    $receipt_html .= '<span style="color: #FFD700; font-weight: bold;">' . number_format($ecocoins_earned, 2) . '</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '</div>';
} else {
    $ecocoins_earned = round($total_amount / 100, 2);
    $receipt_html .= '<div style="background: #fce4ec; padding: 10px; border-radius: 5px; border-left: 4px solid #c2185b;">';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
    $receipt_html .= '<span style="font-weight: 500;">Payment Method:</span>';
    $receipt_html .= '<span style="font-weight: bold;">Cash on Delivery</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '<div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px solid #c2185b;">';
    $receipt_html .= '<span style="font-weight: 500;"><i class="fas fa-coins me-1" style="color: #FFD700;"></i>EcoCoins Earned:</span>';
    $receipt_html .= '<span style="color: #FFD700; font-weight: bold;">' . number_format($ecocoins_earned, 2) . '</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '</div>';
}

$receipt_html .= '</div>';

// Shipping Address
$receipt_html .= '<div style="margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 5px; font-size: 13px;">';
$receipt_html .= '<h6 style="font-weight: bold; margin-bottom: 5px;">Shipping Address:</h6>';
$receipt_html .= '<p style="margin: 0;">' . htmlspecialchars($order['shipping_address'] ?: $order['buyer_name']) . '</p>';
$receipt_html .= '</div>';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Receipt - Ecocycle</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .swal2-popup,
        .swal2-title,
        .swal2-html-container,
        .swal2-confirm,
        .swal2-cancel {
            font-family: 'Poppins', sans-serif !important;
        }
    </style>
</head>
<body>
<script>
    Swal.fire({
        title: '<i class="fas fa-check-circle" style="color: #28a745;"></i> Payment Successful!',
        html: `<?php echo $receipt_html; ?>`,
        icon: 'success',
        confirmButtonText: 'View My Orders',
        cancelButtonText: 'Continue Shopping',
        showCancelButton: true,
        background: '#f8f9fa',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: function() {
            const popup = document.querySelector('.swal2-popup');
            popup.style.borderRadius = '20px';
            popup.style.boxShadow = '0 8px 30px rgba(0, 0, 0, 0.15)';
            popup.style.maxWidth = '550px';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'myorders.php';
        } else {
            window.location.href = 'home.php';
        }
    });
</script>
</body>
</html>