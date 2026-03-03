<?php
include 'homeheader.php';
include 'config/database.php';

// Support both parameter names for backward compatibility
$amount = 0;
if (isset($_GET['amount'])) {
    $amount = floatval($_GET['amount']);
} elseif (isset($_GET['total'])) {
    $amount = floatval($_GET['total']);
}

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : 'ECO-' . date('YmdHis');

// Get additional parameters for direct product purchase
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : 'ecocoins';

// Validate amount
if ($amount <= 0) {
    header('Location: home.php?error=invalid_amount');
    exit();
}

// Calculate EcoCoins required (1 PHP = 1 EcoCoin)
$ecocoins_required = $amount;

// Fetch user's actual EcoCoins balance
$user_balance = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_email = $_SESSION['email'];
    $user_username = $_SESSION['username'];
    
    // Fetch buyer balance
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Buyers WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$user_email, $user_username]);
    $row = $stmt->fetch();
    if ($row) {
        $user_balance += (float)$row['ecocoins_balance'];
    }
    
    // Fetch seller balance
    $stmt = $pdo->prepare('SELECT ecocoins_balance FROM Sellers WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$user_email, $user_username]);
    $row = $stmt->fetch();
    if ($row) {
        $user_balance += (float)$row['ecocoins_balance'];
    }
}

// Calculate remaining balance
$remaining_balance = $user_balance - $ecocoins_required;
$can_pay = $user_balance >= $ecocoins_required;

// Fetch user's address from database
$user_address = '';
if (isset($_GET['shipping_address'])) {
    // Use shipping address from parameter if provided
    $user_address = $_GET['shipping_address'];
} elseif (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $user_email = $_SESSION['email'];
    $user_username = $_SESSION['username'];
    
    // Fetch buyer address
    $stmt = $pdo->prepare('SELECT address FROM Buyers WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$user_email, $user_username]);
    $row = $stmt->fetch();
    if ($row && $row['address']) {
        $user_address = $row['address'];
    }
    
    // If no buyer address, try seller address
    if (empty($user_address)) {
        $stmt = $pdo->prepare('SELECT address FROM Sellers WHERE email = ? OR username = ? LIMIT 1');
        $stmt->execute([$user_email, $user_username]);
        $row = $stmt->fetch();
        if ($row && $row['address']) {
            $user_address = $row['address'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>EcoCoins Payment - Ecocycle</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="main-content">
                <div class="container-lg mt-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-6">
                            <!-- Payment Details Card -->
                            <div class="card shadow-sm border-0 mb-4">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <span class="text-muted">Order ID:</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <strong><?php echo htmlspecialchars($order_id); ?></strong>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <span class="text-muted">Amount (Peso):</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <strong class="fs-5">₱<?php echo number_format($amount, 2); ?></strong>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <span class="text-muted">EcoCoins Required:</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <strong class="fs-5 text-success"><?php echo number_format($ecocoins_required, 2); ?> coins</strong>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <span class="text-muted">Your Balance:</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="badge bg-info"><?php echo number_format($user_balance, 2); ?> coins</span>
                                        </div>
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-6">
                                            <span class="text-muted">Remaining Balance:</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <span class="badge <?php echo $remaining_balance >= 0 ? 'bg-warning' : 'bg-danger'; ?>">
                                                <?php echo number_format($remaining_balance, 2); ?> coins
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$can_pay): ?>
                                    <div class="alert alert-danger mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        Insufficient EcoCoins balance. You need <?php echo number_format($ecocoins_required - $user_balance, 2); ?> more coins.
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_GET['error'])): ?>
                                    <div class="alert alert-danger mb-3">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php 
                                        switch($_GET['error']) {
                                            case 'insufficient_balance':
                                                echo 'Insufficient EcoCoins balance. Please add more coins to your account.';
                                                break;
                                            case 'payment_failed':
                                                echo 'Payment processing failed. Please try again.';
                                                break;
                                            default:
                                                echo 'An error occurred. Please try again.';
                                        }
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <!-- Shipping Address -->
                                    <div class="mb-3">
                                        <label for="shippingAddress" class="form-label">Shipping Address *</label>
                                        <textarea class="form-control" id="shippingAddress" name="shipping_address" rows="3" required><?php echo htmlspecialchars($user_address); ?></textarea>
                                    </div>
                                    
                                    <!-- Pay with EcoCoins Form -->
                                    <form id="paymentForm" action="<?php echo ($product_id > 0) ? 'process-buycheckout.php' : 'process-ecocoins-payment.php'; ?>" method="POST">
                                        <input type="hidden" name="amount" value="<?php echo $amount; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                                        <input type="hidden" name="shipping_address" id="shippingAddressHidden">
                                        <input type="hidden" name="payment_method" value="ecocoins">
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
                                        
                                        <div class="text-center">
                                            <button type="submit" class="btn btn-success btn-lg w-100" id="payWithEcoCoinsBtn" <?php echo !$can_pay ? 'disabled' : ''; ?>>
                                                <?php echo $can_pay ? 'Pay with EcoCoins' : 'Insufficient Balance'; ?>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Back to Home -->
                            <div class="text-center">
                                <a href="home.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-success {
            background-color: #28a745 !important;
            border-color: #28a745 !important;
            color: white !important;
        }
        
        .btn-success:hover {
            background-color: #218838 !important;
            border-color: #1e7e34 !important;
            color: white !important;
        }
        
        .btn-success:disabled {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: white !important;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const paymentForm = document.getElementById('paymentForm');
            const shippingAddress = document.getElementById('shippingAddress');
            const shippingAddressHidden = document.getElementById('shippingAddressHidden');
            
            // Update hidden field when shipping address changes
            shippingAddress.addEventListener('input', function() {
                shippingAddressHidden.value = this.value;
            });
            
            // Set initial value
            shippingAddressHidden.value = shippingAddress.value;
            
            // Handle form submission
            paymentForm.addEventListener('submit', function(e) {
                if (!<?php echo $can_pay ? 'true' : 'false'; ?>) {
                    e.preventDefault();
                    alert('Insufficient EcoCoins balance. Please add more coins to your account.');
                    return false;
                }
                
                // Validate shipping address
                if (!shippingAddress.value.trim()) {
                    e.preventDefault();
                    alert('Please enter your shipping address.');
                    shippingAddress.focus();
                    return false;
                }
                
                // Ensure hidden field has the latest shipping address value
                shippingAddressHidden.value = shippingAddress.value.trim();
                
                // Show loading state
                const submitBtn = document.getElementById('payWithEcoCoinsBtn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Payment...';
                
                // Form will submit normally
            });
        });
    </script>
</body>
</html> 