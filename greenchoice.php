<?php 
// Include session check for buyers
require_once 'config/session_check.php';

// Check if user is a buyer
if (!isBuyer()) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'config/database.php';

// Fetch only Greenchoice products from database
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, s.fullname as seller_name 
        FROM Products p 
        JOIN Categories c ON p.category_id = c.category_id 
        JOIN Sellers s ON p.seller_id = s.seller_id 
        WHERE p.status = 'active' AND LOWER(c.name) = 'greenchoice'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Fetch categories for filtering (optional, can be removed if not needed)
try {
    $stmt = $pdo->prepare("SELECT * FROM Categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Handle add to cart functionality (same as home.php)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'] ?? 1;
    $buyer_id = getCurrentUserId();
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM Cart WHERE buyer_id = ? AND product_id = ?");
        $stmt->execute([$buyer_id, $product_id]);
        $existing_cart_item = $stmt->fetch();
        
        if ($existing_cart_item) {
            $new_quantity = $existing_cart_item['quantity'] + $quantity;
            $stmt = $pdo->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ?");
            $stmt->execute([$new_quantity, $existing_cart_item['cart_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO Cart (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$buyer_id, $product_id, $quantity]);
        }
        $success_message = "Product added to cart successfully!";
    } catch (PDOException $e) {
        $error_message = "Failed to add product to cart.";
    }
}

// Get cart count for the current user
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cart_count FROM Cart WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $cart_count = $stmt->fetch()['cart_count'];
} catch (PDOException $e) {
    $cart_count = 0;
}

// Get buyer's order statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders FROM Orders WHERE buyer_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $total_orders = $stmt->fetch()['total_orders'];
} catch (PDOException $e) {
    $total_orders = 0;
}

include 'homeheader.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Green Choice Products - Ecocycle NLUC</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="">
    <meta name="keywords" content="">
    <meta name="description" content="">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="css/vendor.css">
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
      .payment-options {
        padding: 10px;
      }
      .form-check-label {
        padding: 10px;
        border-radius: 8px;
        transition: background-color 0.2s;
      }
      .form-check-input:checked + .form-check-label {
        background-color: #f0f8ff;
      }
      .form-check-input {
        margin-top: 0.8rem;
      }
      .product-card {
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        border-radius: 16px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        border: none;
        position: relative;
        background: #fff;
        height: 160px;
        display: flex;
        flex-direction: column;
      }
      .product-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 6px 24px rgba(0,0,0,0.13);
      }
      .stock-label {
        color: #198754 !important;
        background: none !important;
        font-size: 0.85rem !important;
        padding: 4px 12px;
        border-radius: 12px;
        z-index: 2;
        font-weight: 700;
      }
      .stock-label.out {
        color: #dc3545 !important;
        background: none !important;
      }
      .green-choice-badge {
        position: absolute;
        left: 12px;
        top: 12px;
        background: #28a745;
        color: #fff;
        font-size: 0.9rem;
        font-weight: bold;
        padding: 4px 12px;
        border-radius: 12px;
        z-index: 2;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      }
      .product-price {
        font-size: 1.2rem;
        font-weight: bold;
        color: #198754;
        margin-bottom: 0.5rem;
      }
      .add-to-cart-btn {
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
      }
      #productDetailsModal .modal-content {
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        padding: 0;
        max-width: 440px;
        margin: auto;
      }
      #productDetailsModal .modal-header {
        border-bottom: none;
        padding-bottom: 0.3rem;
        padding-top: 0.7rem;
        background: #f8f9fa;
        border-radius: 20px 20px 0 0;
      }
      #productDetailsModal .modal-title {
        font-size: 1.2rem;
        font-weight: 700;
        color: #198754;
      }
      #productDetailsModal .modal-body {
        padding: 1rem 1rem 0.7rem 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
      }
      #modalProductImage {
        max-width: 140px;
        max-height: 140px;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.10);
        margin-bottom: 0.7rem;
        background: #fff;
        object-fit: cover;
      }
      #modalProductStock {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 0.3rem;
      }
      #modalProductPrice {
        font-size: 1.2rem;
        font-weight: 700;
        color: #198754;
        margin-bottom: 0.4rem;
      }
      #modalProductEcocoinsPrice {
        font-size: 0.95rem;
        color: #6c757d;
        margin-bottom: 0.7rem;
      }
      #modalProductDescription {
        font-size: 0.95rem;
        color: #333;
        margin-bottom: 0.7rem;
        text-align: center;
      }
      #productDetailsModal .add-to-cart-btn {
        font-size: 1rem;
        font-weight: 700;
        border-radius: 10px;
        box-shadow: none;
        background: none;
        color: #198754;
        border: 1px solid #198754;
        transition: background 0.2s, color 0.2s;
      }
      #productDetailsModal .add-to-cart-btn:hover {
        background: #198754;
        color: #fff;
      }
      @media (max-width: 600px) {
        #productDetailsModal .modal-content {
          max-width: 98vw;
          padding: 0;
        }
        #productDetailsModal .modal-body {
          padding: 1rem 0.5rem 1rem 0.5rem;
        }
      }
      html {
        scroll-behavior: smooth;
      }
      .product-card.compact-card .card-body {
        padding: 0.15rem 0.25rem 0.15rem 0.25rem;
        font-size: 0.75rem;
      }
      .product-card.compact-card .card-title {
        font-size: 1rem;
        margin-bottom: 0.1rem;
        line-height: 1.1;
      }
      .product-card.compact-card .small {
        font-size: 0.85rem;
        margin-bottom: 0.1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: block;
      }
      .product-card.compact-card .product-price,
      .product-card.compact-card .ecocoins-price {
        font-size: 1.1rem;
        margin-bottom: 0.08rem;
      }
      .product-card.compact-card .add-to-cart-btn {
        font-size: 1rem;
        padding: 0.35rem 0.6rem;
        margin-bottom: 0.08rem;
        font-weight: 700;
      }
      .product-card.compact-card .stock-label {
        font-size: 0.8rem;
        padding: 2px 6px;
        top: 4px;
        right: 4px;
      }
      .product-card.compact-card .green-choice-badge {
        font-size: 0.8rem;
        padding: 2px 6px;
        top: 4px;
        left: 4px;
      }
      .product-card.compact-card .mt-auto {
        margin-top: 0.02rem !important;
      }
      .product-card .ratio {
        overflow: hidden;
      }
      .product-card .card-img-top {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
      .product-card .card-body {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        padding-bottom: 0;
        min-height: 0;
      }
      .product-card .card-title {
        font-size: 0.95rem;
        font-weight: 700;
        margin-bottom: 0.08rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      .product-card .small {
        font-size: 0.8rem;
        margin-bottom: 0.08rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: block;
      }
      .product-card .product-price,
      .product-card .ecocoins-price {
        font-size: 1rem;
        margin-bottom: 0.05rem;
      }
      .button-container { margin-top: auto; }
    </style>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="main-content">
          <?php 
          $show_success_alert = false;
          $success_message_text = '';
          if (isset($_SESSION['success_message'])): 
            $show_success_alert = true;
            $success_message_text = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
          endif;
          if (isset($success_message)): 
            $show_success_alert = true;
            $success_message_text = $success_message;
          endif;
          if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($error_message); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          <div class="container-lg mt-3" id="recyclingProducts">
            <h3 class="fw-bold mb-4 text-success">Green Choice Products</h3>
            <div class="row">
              <?php if (empty($products)): ?>
                <div class="col-12 text-center">
                  <img src="images/logo.png.png" alt="No Products" style="width: 100px; opacity: 0.5; margin-bottom: 20px;">
                  <p class="lead">No Green Choice products available yet.</p>
                </div>
              <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="col-12 col-md-6 col-lg-3 mb-4">
                  <div class="card h-100 border shadow-sm product-card"
                       data-name="<?php echo htmlspecialchars($product['name']); ?>"
                       data-image="<?php echo htmlspecialchars($product['image_url'] ?: 'images/logo.png.png'); ?>"
                       data-stock="<?php echo $product['stock_quantity']; ?>"
                       data-price="₱<?php echo number_format($product['price'], 2); ?>"
                       data-description="<?php echo htmlspecialchars($product['description']); ?>"
                       data-product-id="<?php echo $product['product_id']; ?>">
                    <div class="position-relative">
                      <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/logo.png.png'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-height: 200px; object-fit: contain;">
                      <span class="position-absolute top-0 end-0 m-2">
                        <img src="images/green choice.png" alt="Green Choice" style="height: 32px; width: auto; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                      </span>
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
                            <form method="POST" style="display: inline;">
                              <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                              <input type="hidden" name="quantity" value="1">
                              <input type="hidden" name="add_to_cart" value="1">
                              <button type="submit" class="btn btn-outline-success btn-sm add-to-cart-btn">Add to Cart</button>
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
              <?php endif; ?>
            </div>
          </div>
          <div class="row justify-content-center mt-4">
            <div class="col-md-6 text-center">
             <p>© 2024 DMMMSU Environmental Concerns, Sustainability and Development Unit. All rights reserved.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="productDetailsModalLabel"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body d-flex flex-column align-items-center">
            <img id="modalProductImage" src="" alt="" class="img-fluid rounded">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      <?php if ($show_success_alert): ?>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          title: 'Success!',
          text: '<?php echo htmlspecialchars($success_message_text); ?>',
          icon: 'success',
          confirmButtonText: 'OK',
          confirmButtonColor: '#198754',
          timer: 3000,
          timerProgressBar: true,
          showConfirmButton: false
        });
      });
      <?php endif; ?>
      document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function(e) {
          if (e.target.classList.contains('add-to-cart-btn') || e.target.closest('form')) return;
          const name = this.getAttribute('data-name');
          const image = this.getAttribute('data-image');
          const stock = this.getAttribute('data-stock');
          const price = this.getAttribute('data-price');
          const description = this.getAttribute('data-description');
          const productId = this.getAttribute('data-product-id');
          let ecocoinsPrice = '';
          const pesoValue = parseFloat(price.replace(/[^0-9.]/g, ''));
          const ecocoinsValue = pesoValue;
          ecocoinsPrice = `${ecocoinsValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} EcoCoins`;
          document.getElementById('productDetailsModalLabel').textContent = name;
          document.getElementById('modalProductImage').src = image;
          document.getElementById('modalProductImage').alt = name;
          const stockLabel = parseInt(stock) > 0 ? 'In Stock:' : 'Out of Stock:';
          document.getElementById('modalProductStockLabel').textContent = stockLabel;
          document.getElementById('modalProductStock').textContent = stock;
          document.getElementById('modalProductPrice').textContent = price;
          document.getElementById('modalProductEcocoinsPrice').textContent = ecocoinsPrice;
          document.getElementById('modalProductDescription').textContent = description;
          const modalAddToCartBtn = document.querySelector('#productDetailsModal .add-to-cart-btn');
          if (parseInt(stock) > 0) {
            modalAddToCartBtn.disabled = false;
            modalAddToCartBtn.textContent = 'Add to Cart';
            modalAddToCartBtn.classList.remove('btn-secondary');
            modalAddToCartBtn.classList.add('btn-outline-success');
            modalAddToCartBtn.onclick = function() {
              const form = document.createElement('form');
              form.method = 'POST';
              form.innerHTML = `
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="add_to_cart" value="1">
              `;
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
          const productDetailsModal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
          productDetailsModal.show();
        });
      });
    </script>
  </body>
</html> 