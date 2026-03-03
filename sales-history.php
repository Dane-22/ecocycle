<?php
session_start();
include 'config/database.php';

// Check if user is logged in as a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'seller') {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['user_id'];

// Fetch sales history for the current seller
try {
    $query = "
        SELECT 
            o.order_id,
            o.created_at,
            oi.status as item_status,
            o.total_amount,
            o.shipping_address,
            o.payment_method,
            oi.tracking_number,
            b.fullname as buyer_name,
            b.email as buyer_email,
            b.phone_number as buyer_phone,
            p.name as product_name,
            p.image_url,
            oi.quantity,
            oi.price as item_price,
            c.name as category_name
        FROM Orders o
        JOIN Order_Items oi ON o.order_id = oi.order_id
        JOIN Products p ON oi.product_id = p.product_id
        JOIN Buyers b ON o.buyer_id = b.buyer_id
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.seller_id = ?
        ORDER BY o.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$seller_id]);
    $sales_history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching sales history: " . $e->getMessage());
    $sales_history = [];
}

include 'sellerheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Sales History - Ecocycle</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
        .sales-history-container {
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #000;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .search-filter-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: nowrap;
            align-items: center;
            justify-content: flex-start;
        }
        .search-filter-row .input-group {
            flex: 0 0 220px;
            min-width: 0;
            max-width: 220px;
        }
        .search-filter-row .form-select {
            width: 200px;
            min-width: 120px;
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #666;
        }
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
    </style>
</head>
<body>
    <div class="container sales-history-container">
        <div class="section-title">Sales History</div>
        
        <div class="search-filter-row">
            <div class="input-group">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by customer or product..." style="font-size: 0.95rem; padding: 0.3rem 0.6rem;">
                <button id="searchBtn" class="btn" type="button" style="background-color: #2c786c; border-color: #2c786c; padding: 0.3rem 0.8rem; font-size: 0.95rem;">
                    <i class="fas fa-search" style="color: #fff;"></i>
                </button>
            </div>
            <select id="statusFilter" class="form-select">
                <option value="all">All Statuses</option>
                <option value="delivered">Completed</option>
                <option value="shipped">Shipped</option>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales_history)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-chart-line"></i>
                                <h5>No Sales History Yet</h5>
                                <p>Start selling products to see your sales history here</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales_history as $sale): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($sale['order_id']); ?></strong></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($sale['buyer_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($sale['buyer_email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($sale['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($sale['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($sale['product_name']); ?>" 
                                                 class="product-image me-2">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($sale['product_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($sale['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $sale['quantity']; ?></td>
                                <td><strong>₱<?php echo number_format($sale['item_price'] * $sale['quantity'], 2); ?></strong></td>
                                <td>
                                    <span class="badge <?php 
                                        switch($sale['item_status']) {
                                        case 'delivered': echo 'bg-success'; break;
                                        case 'shipped': echo 'bg-info text-dark'; break;
                                        case 'confirmed': echo 'bg-warning text-dark'; break;
                                        case 'pending': echo 'bg-secondary'; break;
                                        case 'cancelled': echo 'bg-danger'; break;
                                        default: echo 'bg-secondary';
                                        }
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($sale['item_status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($sale['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-success"><?php echo ucfirst($sale['payment_method'] ?? 'N/A'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
    <script>
    // Search and filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const searchBtn = document.getElementById('searchBtn');
        const table = document.querySelector('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        function filterTable() {
            const searchValue = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value;
            
            rows.forEach(row => {
                // Skip empty state row
                if (row.querySelector('.empty-state')) {
                    return;
                }
                
                const customer = row.children[1].textContent.toLowerCase();
                const product = row.children[2].textContent.toLowerCase();
                const statusSpan = row.querySelector('.badge');
                let status = '';
                
                if (statusSpan) {
                    status = statusSpan.textContent.trim().toLowerCase();
                }
                
                const matchesSearch = customer.includes(searchValue) || product.includes(searchValue);
                const matchesStatus = (statusValue === 'all') || (status === statusValue);
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Only trigger search on button click
        searchBtn.addEventListener('click', filterTable);
        // Status filter still triggers on change
        statusFilter.addEventListener('change', filterTable);
        
        // Also trigger search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterTable();
            }
        });
    });
    </script>
</body>
</html>
