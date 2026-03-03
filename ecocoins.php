<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}

// Include the correct header based on user type
if ($_SESSION['user_type'] === 'buyer') {
    include 'homeheader.php';
} elseif ($_SESSION['user_type'] === 'seller') {
    include 'sellerheader.php';
} else {
    // Unknown user type, redirect to login
    header('Location: login.php');
    exit();
}

include 'config/database.php';

// Unify EcoCoins balance for users with both buyer and seller accounts
$user_email = $_SESSION['email'];
$user_username = $_SESSION['username'];
$eco_coins = 0;

// Fetch buyer balance
$stmt = $pdo->prepare('SELECT ecocoins_balance FROM buyers WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$user_email, $user_username]);
$row = $stmt->fetch();
if ($row) {
    $eco_coins += (float)$row['ecocoins_balance'];
}
// Fetch seller balance
$stmt = $pdo->prepare('SELECT ecocoins_balance FROM sellers WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$user_email, $user_username]);
$row = $stmt->fetch();
if ($row) {
    $eco_coins += (float)$row['ecocoins_balance'];
}

// Calculate equivalent value
$eco_coins_value = $eco_coins / 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>EcoCoins - Ecocycle</title>
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
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php" class="text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">EcoCoins</li>
                        </ol>
                    </nav>



                    <!-- Balance Section -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Your EcoCoins Balance</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="balance-display mb-3">
                                        <h2 class="text-success fw-bold"><?php echo number_format($eco_coins, 2); ?></h2>
                                        <p class="text-muted">EcoCoins Available</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="balance-value mb-3">
                                        <h4 class="text-primary">₱<?php echo number_format($eco_coins_value, 4); ?></h4>
                                        <p class="text-muted small">Equivalent Value</p>
                                    </div>
                                </div>
                            </div>
                            <div class="balance-actions">
                                <button class="btn btn-outline-success btn-sm me-2" data-bs-toggle="modal" data-bs-target="#earnCoinsModal">
                                    <i class="fas fa-plus me-1"></i>Earn More
                                </button>
                                <button class="btn btn-outline-primary btn-sm" onclick="window.location.href='redeemed-products.php'">
                                    <i class="fas fa-gift me-1"></i>Redeem
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- How to Earn EcoCoins -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>How to Earn EcoCoins</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-recycle text-success me-3" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Recycle Materials</h6>
                                            <p class="text-muted mb-0">Bring recyclable materials to our collection centers and earn coins based on quantity and type.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-shopping-bag text-primary me-3" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Purchase Eco-Friendly products</h6>
                                            <p class="text-muted mb-0">Earn bonus coins when you buy sustainable products from our marketplace.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-users text-warning me-3" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Refer Friends</h6>
                                            <p class="text-muted mb-0">Invite friends to join Ecocycle and earn coins when they make their first purchase.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-star text-info me-3" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold">Leave Reviews</h6>
                                            <p class="text-muted mb-0">Share your experience with products and earn coins for helpful reviews.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EcoCoins Value -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>EcoCoins Value</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h4 class="text-success">100</h4>
                                        <p class="text-muted mb-0">EcoCoins = ₱1.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h4 class="text-success">500</h4>
                                        <p class="text-muted mb-0">EcoCoins = ₱5.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h4 class="text-success">1,000</h4>
                                        <p class="text-muted mb-0">1 EcoCoin = ₱1.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h4 class="text-success">10,000</h4>
                                        <p class="text-muted mb-0">100 EcoCoins = ₱100.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Earn Coins Modal -->
    <div class="modal fade" id="earnCoinsModal" tabindex="-1" aria-labelledby="earnCoinsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="earnCoinsModalLabel">
                        <i class="fas fa-plus me-2"></i>Earn More EcoCoins
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-success">
                                <div class="card-body text-center">
                                    <i class="fas fa-shopping-cart text-success mb-3" style="font-size: 3rem;"></i>
                                    <h6>Buy products</h6>
                                    <p class="text-muted small">Purchase eco-friendly products</p>
                                    <button class="btn btn-success btn-sm" onclick="window.location.href='home.php'">Shop Now</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-primary">
                                <div class="card-body text-center">
                                    <i class="fas fa-store text-primary mb-3" style="font-size: 3rem;"></i>
                                    <h6>Sell products</h6>
                                    <p class="text-muted small">List your eco-friendly products</p>
                                    <button class="btn btn-primary btn-sm" onclick="window.location.href='become_seller.php'">Start Selling</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redeem Modal -->
    <div class="modal fade" id="redeemModal" tabindex="-1" aria-labelledby="redeemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="redeemModalLabel">
                        <i class="fas fa-gift me-2"></i>Redeem EcoCoins
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Use your EcoCoins to pay for products in our marketplace. 100 EcoCoins = ₱1.00.</p>
                    <div class="alert alert-info">
                        <strong>Current Balance:</strong> <?php echo number_format($eco_coins, 2); ?> EcoCoins (₱<?php echo number_format($eco_coins_value, 4); ?>)
                    </div>
                    <a href="home.php" class="btn btn-primary w-100">Shop Now</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 