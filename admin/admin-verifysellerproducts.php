<?php
include 'adminheader.php';
include 'adminsidebar.php';
include '../config/admin_session_check.php';
include '../config/database.php';

// Approve product
if (isset($_GET['approve'])) {
  $pid = intval($_GET['approve']);
  // Approve and auto-increase price by 10%
  $stmt = $pdo->prepare('SELECT price FROM products WHERE product_id=?');
  $stmt->execute([$pid]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row && is_numeric($row['price'])) {
    $auto_price = round($row['price'] * 1.10, 2); // add 10%
    $pdo->prepare('UPDATE products SET status="active", price=? WHERE product_id=?')->execute([$auto_price, $pid]);
  } else {
    // fallback if price not found, just approve
    $pdo->prepare('UPDATE products SET status="active" WHERE product_id=?')->execute([$pid]);
  }
}
// Reject product
if (isset($_GET['reject'])) {
    $pid = intval($_GET['reject']);
    // Optionally delete image file here
    $pdo->prepare('DELETE FROM products WHERE product_id=?')->execute([$pid]);
}

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_category = isset($_GET['filter_category']) ? trim($_GET['filter_category']) : '';
$filter_seller = isset($_GET['filter_seller']) ? trim($_GET['filter_seller']) : '';

$pending_products_for_filters = $pdo->query('SELECT c.name as category_name, s.fullname as seller_name FROM products p JOIN categories c ON p.category_id=c.category_id JOIN sellers s ON p.seller_id=s.seller_id WHERE p.status="inactive"')->fetchAll();

$base_sql = 'SELECT p.*, c.name as category_name, s.fullname as seller_name FROM products p JOIN categories c ON p.category_id=c.category_id JOIN sellers s ON p.seller_id=s.seller_id WHERE p.status="inactive"';
$params = [];
$conditions = [];

if ($search !== '') {
    $conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
    $params[':search'] = "%" . $search . "%";
}
if ($filter_category !== '') {
    $conditions[] = 'c.name = :category';
    $params[':category'] = $filter_category;
}
if ($filter_seller !== '') {
    $conditions[] = 's.fullname = :seller';
    $params[':seller'] = $filter_seller;
}

if (count($conditions) > 0) {
    $base_sql .= ' AND ' . implode(' AND ', $conditions);
}

$stmt = $pdo->prepare($base_sql);
$stmt->execute($params);
$filtered = $stmt->fetchAll();

// Group filtered products by seller
$seller_products = [];
foreach ($filtered as $prod) {
    $seller_products[$prod['seller_name']][] = $prod;
}

// Get unique categories and sellers for filter dropdowns
$categories = array_unique(array_column($pending_products_for_filters, 'category_name'));
sort($categories);
$sellers = array_unique(array_column($pending_products_for_filters, 'seller_name'));
sort($sellers);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Seller products</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<style>
  @media (min-width: 768px) {
    .main-content {
      margin-left: 280px; /* width of .admin-sidebar */
      padding: 32px 24px 24px 24px;
    }
  }
  @media (max-width: 767px) {
    .main-content {
      margin-left: 0;
      padding: 16px 8px;
    }
  }
  .users-fixed-panel {
    position: fixed;
    left: 280px;
    top: 60px;
    width: 1200px;
    max-width: 99vw;
    z-index: 200;
    background: #f8f9fa;
    overflow-y: auto;
    height: calc(100vh - 80px);
    bottom: 0;
    border-radius: 12px;
  }
  @media (max-width: 992px) {
    .users-fixed-panel {
      left: 0;
      width: 98vw;
      max-width: 98vw;
    }
  }
  
  /* Enhanced Product Image Styling */
  .product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
  }
  .product-image:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }
  
  /* Enhanced Button Styling */
  .btn-enhanced {
    border-radius: 6px;
    font-weight: 600;
    letter-spacing: 0.2px;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    text-transform: uppercase;
    font-size: 0.75rem;
    padding: 0.35rem 0.75rem;
    min-width: 85px;
    max-width: 85px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-enhanced::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
  }
  
  .btn-enhanced:hover::before {
    left: 100%;
  }
  
  .btn-view {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
  }
  
  .btn-view:hover {
    background: linear-gradient(135deg, #138496, #117a8b);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
  }
  
  .btn-approve {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
  }
  
  .btn-approve:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }
  
  .btn-reject {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
  }
  
  .btn-reject:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
  }
  
  .btn-search {
    background: #28a745;
    color: white;
    border: none;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
    height: 38px;
    width: 30px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
  }
  
  .btn-search:hover {
    background: #218838;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
  }
  
  .btn-group-enhanced {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    align-items: center;
  }
  
  .btn-group-enhanced .btn {
    margin: 0;
    width: 85px;
    height: 28px;
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
  }
  
  /* Modal Button Enhancements */
  .modal-btn {
    border-radius: 8px;
    font-weight: 600;
    letter-spacing: 0.3px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
    min-width: 120px;
    max-width: 120px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .modal-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
  }
  
  .modal-btn:hover::before {
    left: 100%;
  }
  
  .modal-btn-approve {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
  }
  
  .modal-btn-approve:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(40, 167, 69, 0.5);
  }
  
  .modal-btn-reject {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    color: white;
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
  }
  
  .modal-btn-reject:hover {
    background: linear-gradient(135deg, #c82333, #bd2130);
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(220, 53, 69, 0.5);
  }
  
  /* Responsive button adjustments */
  @media (max-width: 768px) {
    .btn-group-enhanced {
      flex-direction: column;
      gap: 0.25rem;
    }
    
    .btn-group-enhanced .btn {
      margin-bottom: 0;
      width: 75px;
      height: 26px;
      font-size: 0.65rem;
      padding: 0.2rem 0.4rem;
    }
    
    .modal-btn {
      min-width: 100px;
      max-width: 100px;
      height: 36px;
      padding: 0.4rem 0.8rem;
      font-size: 0.75rem;
    }
  }
  
  /* Product Details Modal Styling */
  .product-details-modal .modal-content {
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    border: none;
  }
  .product-details-modal .modal-header {
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 16px 16px 0 0;
  }
  .product-details-modal .modal-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #198754;
  }
  .product-details-modal .modal-body {
    padding: 1.5rem;
  }
  .modal-product-image {
    max-width: 300px;
    max-height: 300px;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
    margin: 0 auto 1.5rem auto;
    display: block;
    object-fit: cover;
  }
  .product-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }
  .product-info-item {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 3px solid #198754;
  }
  .product-info-label {
    font-size: 0.75rem;
    color: #6c757d;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-bottom: 0.2rem;
  }
  .product-info-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #212529;
  }
  .product-description {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }
  .product-description h6 {
    color: #198754;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  .product-description p {
    color: #6c757d;
    line-height: 1.6;
    margin-bottom: 0;
  }
  
  @media (max-width: 768px) {
    .product-info-grid {
      grid-template-columns: 1fr;
    }
    .modal-product-image {
      max-width: 250px;
      max-height: 250px;
    }
  }
</style>
<div class="main-content">
    <h2>Pending Seller products</h2>
    <div class="container users-fixed-panel" style="padding-top:40px;">
      <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
          <form class="d-flex flex-wrap gap-2" style="max-width: 600px;" method="get">
            <div class="input-group flex-grow-1">
              <input class="form-control" type="search" placeholder="Search products..." aria-label="Search" name="search" value="<?= htmlspecialchars($search) ?>">
              <button class="btn btn-search btn-enhanced" type="submit"><i class="fas fa-search"></i></button>
            </div>
            <select class="form-select" style="max-width: 180px;" name="filter_category" onchange="this.form.submit()">
              <option value="">All categories</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"<?= $filter_category === $cat ? ' selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
              <?php endforeach; ?>
            </select>
            <select class="form-select" style="max-width: 180px;" name="filter_seller" onchange="this.form.submit()">
              <option value="">All sellers</option>
              <?php foreach ($sellers as $seller): ?>
                <option value="<?= htmlspecialchars($seller) ?>"<?= $filter_seller === $seller ? ' selected' : '' ?>><?= htmlspecialchars($seller) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
      </div>
      <div class="card p-4">
        <?php if (empty($seller_products)): ?>
          <div class="text-center">No products found.</div>
        <?php else: ?>
          <?php foreach ($seller_products as $seller_name => $products): ?>
            <h4 class="mt-4 mb-2" style="color:#198754;"><i class="fas fa-user"></i> <?= htmlspecialchars($seller_name) ?></h4>
            <div class="table-responsive mb-4">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center">No products found.</td></tr>
                  <?php else: $i=0; foreach ($products as $prod): ?>
                    <tr>
                      <td><?= ++$i ?></td>
                      <td>
                        <img src="<?= htmlspecialchars($prod['image_url'] ? '../' . $prod['image_url'] : '../images/logo.png.png') ?>" 
                             alt="<?= htmlspecialchars($prod['name']) ?>" 
                             class="product-image"
                             onclick="viewProductDetails(<?= htmlspecialchars(json_encode($prod)) ?>)">
                      </td>
                      <td><?= htmlspecialchars($prod['name']) ?></td>
                      <td><?= htmlspecialchars(substr($prod['description'], 0, 100)) . (strlen($prod['description']) > 100 ? '...' : '') ?></td>
                      <td>₱<?= number_format($prod['price'],2) ?></td>
                      <td><?= $prod['stock_quantity'] ?></td>
                      <td><?= htmlspecialchars($prod['category_name']) ?></td>
                      <td>
                        <div class="btn-group-enhanced" role="group">
                          <button type="button" class="btn btn-view btn-enhanced" onclick="viewProductDetails(<?= htmlspecialchars(json_encode($prod)) ?>)">
                            <i class="fas fa-eye me-1"></i> View
                          </button>
                          <button type="button" class="btn btn-approve btn-enhanced" onclick="approveProduct(<?= $prod['product_id'] ?>)">
                            <i class="fas fa-check me-1"></i> Approve
                          </button>
                          <button type="button" class="btn btn-reject btn-enhanced" onclick="rejectProduct(<?= $prod['product_id'] ?>, '<?= htmlspecialchars($prod['name']) ?>')">
                            <i class="fas fa-times me-1"></i> Reject
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; endif; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade product-details-modal" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-center mb-4">
          <img id="modalProductImage" src="" alt="" class="modal-product-image">
        </div>
        
        <div class="product-info-grid">
          <div class="product-info-item">
            <div class="product-info-label">Product Name</div>
            <div class="product-info-value" id="modalProductName"></div>
          </div>
          <div class="product-info-item">
            <div class="product-info-label">Category</div>
            <div class="product-info-value" id="modalProductCategory"></div>
          </div>
          <div class="product-info-item">
            <div class="product-info-label">Price</div>
            <div class="product-info-value" id="modalProductPrice"></div>
          </div>
          <div class="product-info-item">
            <div class="product-info-label">Stock Quantity</div>
            <div class="product-info-value" id="modalProductStock"></div>
          </div>
          <div class="product-info-item">
            <div class="product-info-label">Seller</div>
            <div class="product-info-value" id="modalProductSeller"></div>
          </div>
          <div class="product-info-item">
            <div class="product-info-label">Product ID</div>
            <div class="product-info-value" id="modalProductId"></div>
          </div>
        </div>
        
        <div class="product-description">
          <h6><i class="fas fa-info-circle me-2"></i>Description</h6>
          <p id="modalProductDescription"></p>
        </div>
        
        <div class="d-flex justify-content-center gap-3">
          <a id="modalApproveBtn" href="#" class="btn modal-btn modal-btn-approve">
            <i class="fas fa-check me-2"></i>Approve Product
          </a>
          <a id="modalRejectBtn" href="#" class="btn modal-btn modal-btn-reject">
            <i class="fas fa-times me-2"></i>Reject Product
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
function viewProductDetails(product) {
  // Set modal content
  document.getElementById('productDetailsModalLabel').textContent = product.name;
  document.getElementById('modalProductImage').src = product.image_url ? '../' + product.image_url : '../images/logo.png.png';
  document.getElementById('modalProductImage').alt = product.name;
  document.getElementById('modalProductName').textContent = product.name;
  document.getElementById('modalProductCategory').textContent = product.category_name;
  document.getElementById('modalProductPrice').textContent = '₱' + parseFloat(product.price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  document.getElementById('modalProductStock').textContent = product.stock_quantity;
  document.getElementById('modalProductSeller').textContent = product.seller_name;
  document.getElementById('modalProductId').textContent = product.product_id;
  document.getElementById('modalProductDescription').textContent = product.description;
  
  // Set action buttons
  document.getElementById('modalApproveBtn').href = '?approve=' + product.product_id;
  document.getElementById('modalRejectBtn').href = '?reject=' + product.product_id;
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('productDetailsModal'));
  modal.show();
}

// SweetAlert2 confirmation functions
function approveProduct(productId) {
  Swal.fire({
    title: "Approve Product?",
    text: "Are you sure you want to approve this product?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#28a745",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, Approve",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `?approve=${productId}`;
    }
  });
}

function rejectProduct(productId, productName) {
  Swal.fire({
    title: "Reject Product?",
    text: `Are you sure you want to reject and delete "${productName}"? You won't be able to revert this!`,
    showCancelButton: true,
    confirmButtonColor: "#dc3545",
    cancelButtonColor: "#6c757d",
    confirmButtonText: "Yes, reject it!",
    cancelButtonText: "Cancel"
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = `?reject=${productId}`;
    }
  });
}

// Add click handlers for approve/reject buttons in modal
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('modalApproveBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const productId = document.getElementById('modalProductId').textContent;
    approveProduct(productId);
  });
  
  document.getElementById('modalRejectBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const productName = document.getElementById('modalProductName').textContent;
    Swal.fire({
      title: "Reject Product?",
      text: `Are you sure you want to reject and delete "${productName}"? You won't be able to revert this!`,
      showCancelButton: true,
      confirmButtonColor: "#dc3545",
      cancelButtonColor: "#6c757d",
      confirmButtonText: "Yes, reject it!",
      cancelButtonText: "Cancel"
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = this.href;
      }
    });
  });
});
</script>
</body>
</html> 