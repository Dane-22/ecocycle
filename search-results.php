<?php
include 'homeheader.php';

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch search results from database
// Note: database connection is already included via homeheader.php
$products = [];
$search_error = '';

if (!empty($search_query)) {
    try {
        // Database connection is already available from homeheader.php
        // No need to require it again
        
        $searchTerm = "%{$search_query}%";
        
        $stmt = $pdo->prepare(
            "SELECT 
                p.product_id,
                p.name,
                p.description,
                p.price,
                p.image_url,
                p.stock_quantity,
                c.name AS category,
                s.fullname as seller_name,
                s.address as seller_location
            FROM Products p
            LEFT JOIN Categories c ON p.category_id = c.category_id
            LEFT JOIN Sellers s ON p.seller_id = s.seller_id
            WHERE p.status = 'active'
                AND (
                    p.name LIKE :search1 
                    OR p.description LIKE :search2 
                    OR c.name LIKE :search3
                    OR s.fullname LIKE :search4
                    OR s.address LIKE :search5
                )
            ORDER BY 
                CASE 
                    WHEN p.name LIKE :search6 THEN 1
                    WHEN s.fullname LIKE :search7 THEN 2
                    WHEN c.name LIKE :search8 THEN 3
                    WHEN s.address LIKE :search9 THEN 4
                    ELSE 5
                END,
                p.name ASC"
        );

        $stmt->execute([
            ':search1' => $searchTerm,
            ':search2' => $searchTerm,
            ':search3' => $searchTerm,
            ':search4' => $searchTerm,
            ':search5' => $searchTerm,
            ':search6' => $searchTerm,
            ':search7' => $searchTerm,
            ':search8' => $searchTerm,
            ':search9' => $searchTerm
        ]);
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $search_error = "Database error: " . $e->getMessage();
        error_log("Search error: " . $e->getMessage());
        $products = [];
    } catch (Exception $e) {
        $search_error = "An error occurred while searching.";
        error_log("Search error: " . $e->getMessage());
        $products = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Ecocycle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .search-header {
            background: linear-gradient(135deg, #28bf4b 0%, #1e8e3e 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transform: translateY(-5px);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }
        
        .product-body {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #28bf4b;
            margin-top: auto;
        }
        
        .product-meta {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 0.5rem;
        }
        
        .stock-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        
        .no-results {
            text-align: center;
            padding: 3rem 1rem;
        }
        
        .no-results i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .btn-view-product {
            background-color: #28bf4b;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-view-product:hover {
            background-color: #1e8e3e;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="main-content">
                <!-- Search Header -->
                <div class="search-header">
                    <div class="container">
                        <h2 class="mb-2">
                            <i class="fas fa-search me-2"></i>Search Results
                        </h2>
                        <?php if (!empty($search_query)): ?>
                            <p class="mb-0">
                                Showing results for: <strong>"<?php echo htmlspecialchars($search_query); ?>"</strong>
                                <span class="badge bg-light text-dark ms-2"><?php echo count($products); ?> product<?php echo count($products) != 1 ? 's' : ''; ?> found</span>
                            </p>
                        <?php else: ?>
                            <p class="mb-0 text-warning">
                                <i class="fas fa-info-circle me-2"></i>No search query provided. Please use the search bar above.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="container">
                    <?php if (!empty($search_error)): ?>
                        <!-- Search Error -->
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($search_error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($search_query)): ?>
                        <!-- No search query -->
                        <div class="no-results">
                            <i class="fas fa-search"></i>
                            <h4>Please enter a search term</h4>
                            <p class="text-muted">Use the search bar above to find products</p>
                        </div>
                    <?php elseif (empty($products)): ?>
                        <!-- No results found -->
                        <div class="no-results">
                            <i class="fas fa-inbox"></i>
                            <h4>No products found</h4>
                            <p class="text-muted">Try different keywords or browse our categories</p>
                            <a href="home.php" class="btn btn-success mt-3">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Search Results -->
                                                <div class="row g-4 mb-5">
                                                        <?php foreach ($products as $product): ?>
                                                                <div class="col-6 col-md-4 col-lg-3 mb-4">
                                                                    <div class="card h-100 border shadow-sm product-card"
                                                                             data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                                                             data-image="<?php echo htmlspecialchars($product['image_url'] ?: 'images/logo.png.png'); ?>"
                                                                             data-stock="<?php echo $product['stock_quantity']; ?>"
                                                                             data-price="₱<?php echo number_format($product['price'], 2); ?>"
                                                                             data-description="<?php echo htmlspecialchars($product['description']); ?>"
                                                                             data-product-id="<?php echo $product['product_id']; ?>">
                                                                        <div class="position-relative">
                                                                              <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/logo.png.png'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height: 140px; object-fit: contain;">
                                                                            <?php 
                                                                            $category_name = strtolower($product['category'] ?? '');
                                                                            if ($category_name === 'best seller'): ?>
                                                                                <span class="badge bg-success position-absolute top-0 end-0 m-2">Best Seller</span>
                                                                            <?php elseif ($category_name === 'greenchoice' || $category_name === 'green choice'): ?>
                                                                                <?php
                                                                                $greenChoicePath = 'images/green choice.png';
                                                                                $greenChoiceSrc = $greenChoicePath;
                                                                                if (file_exists($greenChoicePath)) {
                                                                                        $greenChoiceSrc .= '?v=' . filemtime($greenChoicePath);
                                                                                }
                                                                                ?>
                                                                                <img src="<?php echo $greenChoiceSrc; ?>" alt="Green Choice" style="position:absolute; top:8px; right:8px; height: 56px; width: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.12); pointer-events:none;">
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <div class="card-body d-flex flex-column">
                                                                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                                                            <p class="card-text small"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . (strlen($product['description']) > 80 ? '...' : ''); ?></p>
                                                                            <div class="mt-auto">
                                                                                <div class="mb-1">
                                                                                    <?php if ($product['stock_quantity'] > 0): ?>
                                                                                        <span class="badge stock-label">Stocks: <?php echo $product['stock_quantity']; ?></span>
                                                                                    <?php else: ?>
                                                                                        <span class="badge stock-label out">Out of Stock</span>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                                <p class="fw-bold text-success fs-5 mb-2">₱<?php echo number_format($product['price'], 2); ?></p>
                                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                    <span class="badge bg-light text-dark">EcoCoins: <?php echo number_format($product['price'], 2); ?></span>
                                                                                    <?php if ($product['stock_quantity'] > 0): ?>
                                                                                    <form method="POST" action="/Ecocycle/home.php" style="display: inline;">
                                                                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                                                            <input type="hidden" name="quantity" value="1">
                                                                                            <input type="hidden" name="add_to_cart" value="1">
                                                                                            <button type="submit" class="btn btn-outline-success btn-sm">Add to Cart</button>
                                                                                        </form>
                                                                                    <?php else: ?>
                                                                                        <button class="btn btn-secondary btn-sm" disabled>Out of Stock</button>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                        <?php endforeach; ?>
                                                </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

            <!-- Product Details Modal (same as Home) -->
            <style>
                /* Compact modal styling */
                #productDetailsModal .modal-content { border-radius: 12px; }
                #productDetailsModal .modal-title { font-size: 1rem; }
                #productDetailsModal .modal-body { font-size: 0.95rem; }
                #productDetailsModal .add-to-cart-btn { font-size: 0.95rem; padding: 0.4rem 0.6rem; }
            </style>
        <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productDetailsModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                                <div class="modal-body d-flex flex-column align-items-center" style="padding:0.8rem;">
                                    <img id="modalProductImage" src="" alt="" class="img-fluid rounded" style="max-width:120px; max-height:120px; object-fit:cover;">
                        <div id="modalProductStockLabel" class="fw-bold mb-1"></div>
                        <div id="modalProductStock"></div>
                        <div id="modalProductPrice"></div>
                        <div id="modalProductEcocoinsPrice"></div>
                        <div id="modalProductDescription"></div>
                        <button class="btn btn-outline-success add-to-cart-btn w-100 mb-2">Add to Cart</button>
                                </div>
                </div>
            </div>
        </div>

        <script>
        // Product details modal functionality (copied from home.php)
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    // Prevent click on Add to Cart button from triggering modal
                    if (e.target.classList.contains('add-to-cart-btn') || e.target.closest('form')) return;

                    // Get product details from data attributes
                    const name = this.getAttribute('data-name');
                    const image = this.getAttribute('data-image');
                    const stock = this.getAttribute('data-stock');
                    const price = this.getAttribute('data-price');
                    const description = this.getAttribute('data-description');
                    const productId = this.getAttribute('data-product-id');

                    // Calculate EcoCoins price (1 PHP = 1 EcoCoin)
                    let ecocoinsPrice = '';
                    const pesoValue = parseFloat((price || '').replace(/[^0-9.]/g, '')) || 0;
                    const ecocoinsValue = pesoValue; // Convert to EcoCoins (1 per peso)
                    ecocoinsPrice = ecocoinsValue > 0 ? `${ecocoinsValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} EcoCoins` : '0.00 EcoCoins';

                    // Set modal content
                    document.getElementById('productDetailsModalLabel').textContent = name;
                    document.getElementById('modalProductImage').src = image;
                    document.getElementById('modalProductImage').alt = name;
                    // Add label for stock
                    const stockLabel = parseInt(stock) > 0 ? 'In Stock:' : 'Out of Stock:';
                    document.getElementById('modalProductStockLabel').textContent = stockLabel;
                    document.getElementById('modalProductStock').textContent = stock;
                    document.getElementById('modalProductPrice').textContent = price;
                    document.getElementById('modalProductEcocoinsPrice').textContent = ecocoinsPrice;
                    document.getElementById('modalProductDescription').textContent = description;

                    // Update the Add to Cart button in modal to include product ID
                    const modalAddToCartBtn = document.querySelector('#productDetailsModal .add-to-cart-btn');
                    if (parseInt(stock) > 0) {
                        modalAddToCartBtn.disabled = false;
                        modalAddToCartBtn.textContent = 'Add to Cart';
                        modalAddToCartBtn.classList.remove('btn-secondary');
                        modalAddToCartBtn.classList.add('btn-outline-success');
                        modalAddToCartBtn.onclick = function() {
                            // Create a form and submit it to home.php where cart handling exists
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = '/Ecocycle/home.php';
                            form.innerHTML = `\n                <input type="hidden" name="product_id" value="${productId}">\n                <input type="hidden" name="quantity" value="1">\n                <input type="hidden" name="add_to_cart" value="1">\n              `;
                            document.body.appendChild(form);
                            form.submit();
                        };
                    } else {
                        modalAddToCartBtn.disabled = true;
                        modalAddToCartBtn.textContent = 'Out of Stock';
                        modalAddToCartBtn.classList.remove('btn-outline-success');
                        modalAddToCartBtn.classList.add('btn-secondary');
                        modalAddToCartBtn.onclick = null;
                    }

                    // Show modal
                    const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
                    productDetailsModal.show();
                });
            });
        });
        </script>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
