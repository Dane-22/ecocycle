<?php
session_start();
// Include the correct header based on user type
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['user_type'] === 'buyer') {
    include 'homeheader.php';
} elseif ($_SESSION['user_type'] === 'seller') {
    include 'sellerheader.php';
} else {
    header('Location: login.php');
    exit();
}
include 'config/database.php';

// Unify EcoCoins balance for users with both buyer and seller accounts
$user_email = $_SESSION['email'];
$user_username = $_SESSION['username'];
$balance = 0;

// Use PDO for both queries
// Fetch buyer balance
$stmt = $pdo->prepare('SELECT ecocoins_balance FROM Buyers WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$user_email, $user_username]);
$row = $stmt->fetch();
if ($row) {
    $balance += (float)$row['ecocoins_balance'];
}
// Fetch seller balance
$stmt = $pdo->prepare('SELECT ecocoins_balance FROM Sellers WHERE email = ? OR username = ? LIMIT 1');
$stmt->execute([$user_email, $user_username]);
$row = $stmt->fetch();
if ($row) {
    $balance += (float)$row['ecocoins_balance'];
}

// Fetch products from database
$products = [];
$stmt = $pdo->query("SELECT id, name, description, ecocoins_cost, stocks, image FROM bardproducts");
if ($stmt) {
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Redeem Products - Ecocycle</title>
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
    <style>
        /* Add to style.css */
        .btn-custom-redeem {
            background-color: #006400; /* Dark green */
            color: #fff;
            border: none;
        }
        .btn-custom-redeem:hover, .btn-custom-redeem:focus {
            background-color: #198754; /* Even darker green on hover */
            color: #fff;
        }
        .btn-custom-confirm {
            background-color: #198754; /* Bootstrap success green */
            color: #fff;
            border: none;
        }
        .btn-custom-confirm:hover, .btn-custom-confirm:focus {
            background-color: #157347; /* Even darker green on hover */
            color: #fff;
        }
        .btn-view-green {
            background-color: #198754 !important; /* Green */
            color: #fff !important;
            border: none !important;
            opacity: 1 !important;
            transition: background-color 0.2s;
        }
        .btn-view-green:hover, .btn-view-green:focus {
            background-color: #157347 !important; /* Darker green on hover */
            color: #fff !important;
            opacity: 1 !important;
        }
    </style>
    <!-- Add this in your <head> section, after Bootstrap CSS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
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
    <div class="container-fluid">
        <div class="row">
            <div class="main-content">
                <div class="container-lg mt-5">
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="home.php" class="text-decoration-none">Home</a></li>
                            <li class="breadcrumb-item"><a href="ecocoins.php" class="text-decoration-none">EcoCoins</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Redeem Products</li>
                        </ol>
                    </nav>



                    <!-- Balance and Filter Section -->
                    <div class="row mb-5">
                        <div class="col-lg-4 mx-auto d-flex justify-content-center">
                            <div class="card shadow-sm border-0" style="min-width: 1000px;">
                                <div class="card-body text-center p-4">
                                    <h5 class="text-success mb-2">Your Balance</h5>
                                    <h3 class="fw-bold text-success"><?php echo number_format($balance, 2); ?> EcoCoins</h3>
                                    <p class="text-muted">₱<?php echo number_format($balance / 100, 4); ?></p>
                                </div>
                            </div>
                        </div>
                        <div style="height: 32px;"></div>
                    <!-- Products Grid -->
                    <div class="row" id="productsGrid">
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                    <div class="card shadow-sm border-0 h-100 product-card" style="cursor:default;">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                                            <div class="position-absolute top-0 end-0 m-2">
                                                <span class="badge bg-success"><?php echo number_format($product['ecocoins_cost']); ?> EcoCoins</span>
                                            </div>
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
                                            <p class="card-text text-muted small">
                                                <?php echo htmlspecialchars(explode("\n", $product['description'])[0]); ?>
                                            </p>
                                            <div class="mt-auto">
                                                <!-- Removed product details modal -->
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted small">Stocks: <?php echo (int)$product['stocks']; ?></span>
                                                    <span class="text-success fw-bold"><?php echo number_format($product['ecocoins_cost']); ?> EcoCoins</span>
                                                </div>
                                                <button class="btn btn-custom-redeem w-100" onclick="redeemProduct(<?php echo (int)$product['id']; ?>, <?php echo (int)$product['ecocoins_cost']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                                    <i class="fas fa-gift me-2"></i>Redeem Now
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                                <h4 class="text-muted">No products found</h4>
                                <p class="text-muted">Try adjusting your filters to see more products.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- End Products Grid -->

                    <!-- Redeemed History Section -->
                    <div class="mt-5">
                        <h4 class="mb-3 text-success"><i class="fas fa-history me-2"></i>Redeemed History</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-success">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product Name</th>
                                        <th>EcoCoins Spent</th>
                                        <th>Date Redeemed</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Fetch redeemed history for the logged-in user
                                    $user_id = $_SESSION['user_id'];
                                    $user_type = $_SESSION['user_type'];
                                    $history = [];
                                    $stmt = $pdo->prepare("SELECT br.order_id, bp.name, br.ecocoins_spent, br.status, br.redeemed_at, bp.image FROM bardproductsredeem br JOIN bardproducts bp ON br.product_id = bp.id WHERE br.user_id = ? AND br.user_type = ? AND br.status = 'approved' ORDER BY br.redeemed_at DESC");
                                    $stmt->execute([$user_id, $user_type]);
                                    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    if (count($history) > 0):
                                        foreach ($history as $row):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo number_format($row['ecocoins_spent']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($row['redeemed_at'])); ?></td>
                                        <td><?php echo ucfirst($row['status']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-view-green" onclick='showRedeemDetails(<?php echo json_encode($row); ?>)'>View</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No redeemed products found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Redeem Details Modal -->
                    <div class="modal fade" id="redeemDetailsModal" tabindex="-1" aria-labelledby="redeemDetailsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title" id="redeemDetailsModalLabel">
                                        <i class="fas fa-info-circle me-2"></i>Redeem Product Details
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body" id="redeemDetailsBody">
                                    <!-- Details will be injected by JS -->
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Products Found Message -->
                    <div id="noProductsMessage" class="text-center py-5" style="display: none;">
                        <i class="fas fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                        <h4 class="text-muted">No products found</h4>
                        <p class="text-muted">Try adjusting your filters to see more products.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Redeem Confirmation Modal -->
    <div class="modal fade" id="redeemModal" tabindex="-1" aria-labelledby="redeemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="redeemModalLabel">
                        <i class="fas fa-gift me-2"></i>Confirm Redemption
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-center mb-3">Are you sure you want to redeem this product?</h6>
                    <div class="alert alert-info">
                        <strong>Product:</strong> <span id="modalProductName"></span><br>
                        <strong>Cost:</strong> <span id="modalProductCost"></span> EcoCoins<br>
                        <strong>Your Balance:</strong> <?php echo number_format($balance, 2); ?> EcoCoins<br>
                        <strong>Remaining Balance:</strong> <span id="modalRemainingBalance"></span> EcoCoins
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <!-- Confirm Redemption Button in Modal -->
                    <button type="button" class="btn btn-custom-confirm" onclick="confirmRedeem()">
                        <i class="fas fa-check me-2"></i>Confirm Redemption
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="successModalLabel">
                        <i class="fas fa-check-circle me-2"></i>Redemption Successful!
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-gift text-success mb-3" style="font-size: 3rem;"></i>
                    <h6 class="mb-3">Your product has been successfully redeemed!</h6>
                    <p class="text-muted">Please wait for Bard admin approval. You will receive a confirmation email once your redemption is approved.</p>
                    <div class="alert alert-success">
                        <strong>Order ID:</strong> ECO-REDEEM-<span id="orderId"></span><br>
                        <strong>Pickup Location:</strong> Ecocycle Collection Center<br>
                        <strong>Pickup Time:</strong> Within 24 hours
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Continue Shopping</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let userBalance = <?php echo (int)$balance; ?>;
        let currentRedeemData = {};

        // Filter functionality
        document.getElementById('categoryFilter').addEventListener('change', filterProducts);
        document.getElementById('priceFilter').addEventListener('change', filterProducts);

        function filterProducts() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const priceFilter = document.getElementById('priceFilter').value;
            const products = document.querySelectorAll('.product-card');
            let visibleCount = 0;

            products.forEach(product => {
                const productElement = product.closest('.col-lg-3');
                const category = productElement.dataset.category;
                const price = parseInt(productElement.dataset.price);
                
                let showProduct = true;

                // Category filter
                if (categoryFilter && category !== categoryFilter) {
                    showProduct = false;
                }

                // Price filter
                if (priceFilter) {
                    const [min, max] = priceFilter.split('-').map(p => p === '+' ? Infinity : parseInt(p));
                    if (price < min || (max !== Infinity && price > max)) {
                        showProduct = false;
                    }
                }

                if (showProduct) {
                    productElement.style.display = 'block';
                    visibleCount++;
                } else {
                    productElement.style.display = 'none';
                }
            });

            // Show/hide no products message
            const noProductsMessage = document.getElementById('noProductsMessage');
            if (visibleCount === 0) {
                noProductsMessage.style.display = 'block';
            } else {
                noProductsMessage.style.display = 'none';
            }
        }

        function redeemProduct(productId, cost, productName) {
            
            if (cost > userBalance) {
                alert('Insufficient EcoCoins balance!');
                return;
            }

            currentRedeemData = {
                productId: productId,
                cost: cost,
                productName: productName
            };

            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalProductCost').textContent = cost;
            document.getElementById('modalRemainingBalance').textContent = userBalance - cost;

            const redeemModal = new bootstrap.Modal(document.getElementById('redeemModal'));
            redeemModal.show();
        }

        function confirmRedeem() {
            // Close the confirmation modal
            const redeemModal = bootstrap.Modal.getInstance(document.getElementById('redeemModal'));
            redeemModal.hide();

            // Generate random order ID
            const orderId = Math.random().toString(36).substr(2, 8).toUpperCase();

            // AJAX request to save redemption
            fetch('redeem_product.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    productId: currentRedeemData.productId,
                    cost: currentRedeemData.cost,
                    orderId: orderId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Redemption Successful!',
                        html: `
                            <i class="fas fa-gift text-success mb-3" style="font-size: 3rem;"></i>
                            <h6 class="mb-3">Your product has been successfully redeemed!</h6>
                            <p class="text-muted">Please wait for Bard admin approval. You will receive a confirmation email once your redemption is approved.</p>
                            <div class="alert alert-success">
                                <strong>Order ID:</strong> ECO-REDEEM-${orderId}<br>
                                <strong>Pickup Location:</strong> Ecocycle Collection Center<br>
                                <strong>Pickup Time:</strong> Within 24 hours
                            </div>
                        `,
                        confirmButtonText: 'Continue Shopping',
                        confirmButtonColor: '#198754'
                    });
                } else {
                    Swal.fire('Error', data.message || 'Redemption failed.', 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'Could not connect to server.', 'error');
            });
        }
        // Show redeem details modal
        function showRedeemDetails(row) {
            let html = '';
            if (row.image) {
                html += `<div class='text-center mb-3'><img src='${row.image}' alt='${row.name}' style='max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px;'></div>`;
            }
            html += `<ul class='list-group'>`;
            html += `<li class='list-group-item'><strong>Order ID:</strong> ${row.order_id}</li>`;
            html += `<li class='list-group-item'><strong>Product Name:</strong> ${row.name}</li>`;
            html += `<li class='list-group-item'><strong>EcoCoins Spent:</strong> ${row.ecocoins_spent}</li>`;
            html += `<li class='list-group-item'><strong>Date Redeemed:</strong> ${row.redeemed_at}</li>`;
            html += `<li class='list-group-item'><strong>Status:</strong> ${row.status}</li>`;
            html += `</ul>`;
            document.getElementById('redeemDetailsBody').innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('redeemDetailsModal'));
            modal.show();
        }
    </script>
</body>
</html>