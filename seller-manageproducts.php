<?php 
include 'sellerheader.php';
require_once 'config/database.php';
require_once 'config/session_check.php';

// Fetch seller's products from the database (show all statuses for management)
try {
    $stmt = $pdo->prepare('
        SELECT p.*, c.name as category_name 
        FROM Products p 
        JOIN Categories c ON p.category_id = c.category_id 
        WHERE p.seller_id = ?
        ORDER BY p.created_at DESC
    ');
    $stmt->execute([getCurrentUserId()]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
}

// Fetch categories for the select dropdown
try {
    $stmt = $pdo->prepare("SELECT * FROM Categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Fetch seller info
try {
    $stmt = $pdo->prepare("SELECT * FROM Sellers WHERE seller_id = ?");
    $stmt->execute([getCurrentUserId()]);
    $seller = $stmt->fetch();
} catch (PDOException $e) {
    $seller = null;
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productName'])) {
    $name = $_POST['productName'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stocks = $_POST['stocks'];
    $ecocoins_price = intval(round($price)); // Calculate ecocoins price (1 PHP = 1 EcoCoin)
    $status = 'pending'; // New products start as pending for admin verification

    // Handle image upload (simplified)
    $image_url = null;
    if (isset($_FILES['productImage']) && $_FILES['productImage']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "images/";
        $target_file = $target_dir . basename($_FILES["productImage"]["name"]);
        if (move_uploaded_file($_FILES["productImage"]["tmp_name"], $target_file)) {
            $image_url = $target_file;
        }
    }

    // Get category_id
    $stmt = $pdo->prepare("SELECT category_id FROM Categories WHERE name = ?");
    $stmt->execute([$category]);
    $cat = $stmt->fetch();
    $category_id = $cat ? $cat['category_id'] : null;

    if ($category_id) {
        $stmt = $pdo->prepare("INSERT INTO Products (seller_id, category_id, name, description, price, ecocoins_price, stock_quantity, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([getCurrentUserId(), $category_id, $name, $description, $price, $ecocoins_price, $stocks, $image_url, $status]);
        $success_message = "Product submitted successfully! It is now pending admin verification.";
    } else {
        $error_message = "Invalid category selected.";
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateProduct'])) {
    $product_id = $_POST['product_id'];
    $name = $_POST['editProductName'];
    $category = $_POST['editCategory'];
    $description = $_POST['editDescription'];
    $price = $_POST['editPrice'];
    $stocks = $_POST['editStocks'];
    $ecocoins_price = intval(round($price)); // Calculate ecocoins price (1 PHP = 1 EcoCoin)

    // Get category_id
    $stmt = $pdo->prepare("SELECT category_id FROM Categories WHERE name = ?");
    $stmt->execute([$category]);
    $cat = $stmt->fetch();
    $category_id = $cat ? $cat['category_id'] : null;

    if ($category_id) {
        // Fetch current product data
        $stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id = ? AND seller_id = ?");
        $stmt->execute([$product_id, getCurrentUserId()]);
        $current = $stmt->fetch();

        $image_update = "";
        $params = [$category_id, $name, $description, $price, $stocks, $ecocoins_price];
        $image_changed = false;
        if (isset($_POST['removeImage']) && $_POST['removeImage'] == 'on') {
            $image_update = ", image_url = NULL";
            $image_changed = true;
        } elseif (isset($_FILES['editProductImage']) && $_FILES['editProductImage']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "images/";
            $target_file = $target_dir . basename($_FILES["editProductImage"]["name"]);
            if (move_uploaded_file($_FILES["editProductImage"]["tmp_name"], $target_file)) {
                $image_update = ", image_url = ?";
                $params[] = $target_file;
                $image_changed = true;
            }
        }

        // Determine if status should be set to 'pending'
        $set_pending = false;
        if (
            $current['name'] !== $name ||
            $current['category_id'] != $category_id ||
            $current['description'] !== $description ||
            $image_changed
        ) {
            $set_pending = true;
        }

        $params[] = $product_id;
        $params[] = getCurrentUserId();

        if ($set_pending) {
            $stmt = $pdo->prepare("UPDATE Products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, ecocoins_price = ?$image_update, status = 'pending' WHERE product_id = ? AND seller_id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE Products SET category_id = ?, name = ?, description = ?, price = ?, stock_quantity = ?, ecocoins_price = ?$image_update WHERE product_id = ? AND seller_id = ?");
        }
        $stmt->execute($params);
        $success_message = "Product updated successfully!" . ($set_pending ? " It is now pending admin re-verification." : "");
    } else {
        $error_message = "Invalid category selected.";
    }
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteProduct'])) {
    $product_id = $_POST['product_id'];
    
    // Verify the product belongs to the current seller
    $stmt = $pdo->prepare("DELETE FROM Products WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$product_id, getCurrentUserId()]);
    $success_message = "Product deleted successfully!";
}

// Handle quick price/stocks update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quickUpdate'])) {
    $product_id = $_POST['product_id'];
    $price = $_POST['quickPrice'];
    $stocks = $_POST['quickStocks'];
    $ecocoins_price = intval(round($price)); // Calculate ecocoins price (1 PHP = 1 EcoCoin)

    // Only update price and stocks, do not change status
    $stmt = $pdo->prepare("UPDATE Products SET price = ?, stock_quantity = ?, ecocoins_price = ? WHERE product_id = ? AND seller_id = ?");
    $stmt->execute([$price, $stocks, $ecocoins_price, $product_id, getCurrentUserId()]);
    $success_message = "Product price and stocks updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Ecocycle Nluc</title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <style>
      .status-tooltip {
        cursor: help;
        border-bottom: 1px dotted;
      }
    </style>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar (optional, can be toggled) -->
        <!-- Main Content Column -->
        <div class="col-12 main-content">
          <div class="container-lg mt-3">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h3 class="fw-bold mb-0">My Products</h3>
            </div>
            

            
            <!-- Success/Error Messages will be handled by SweetAlert -->
            <!-- Search and Filters (Sales History style) -->
            <div class="search-filter-row mb-3" style="display: flex; gap: 1rem; flex-wrap: wrap;">
              <div class="input-group" style="max-width: 250px;">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by product...">
                <button id="searchBtn" class="btn" type="button" style="background-color: #198754; border-color: #198754;">
                  <i class="fas fa-search" style="color: #fff;"></i>
                </button>
              </div>
              <select id="filterCategory" class="form-select" style="max-width: 200px;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
              </select>
              <select id="filterStatus" class="form-select" style="max-width: 200px;">
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
                <option value="Pending">Pending</option>
                <option value="Rejected">Rejected</option>
              </select>
              <button type="reset" class="btn btn-outline-secondary" id="resetBtn">Reset</button>
            </div>
            <!-- Seller's Product Listings Table -->
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead class="table-success">
                  <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stocks</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($products)): ?>
                    <tr><td colspan="7" class="text-center">No products found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($products as $product): ?>
                      <tr>
                        <td>
                          <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/logo.png.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width:60px;height:60px;object-fit:cover;" class="me-2 rounded">
                          <?php echo htmlspecialchars($product['name']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                        <form method="post" style="display:contents;">
                          <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                          <input type="hidden" name="quickUpdate" value="1">
                          <td style="vertical-align: middle;">
                            <span class="price-span" id="price-span-<?php echo $product['product_id']; ?>" onclick="enableEdit('<?php echo $product['product_id']; ?>', 'price')" style="cursor:pointer;">
                              ₱<?php echo number_format($product['price'], 2); ?>
                            </span>
                            <input type="number" name="quickPrice" id="price-input-<?php echo $product['product_id']; ?>" value="<?php echo $product['price']; ?>" min="0.01" step="0.01" class="form-control form-control-sm d-none" style="width: 90px; display:inline-block;" required oninput="showCheck('<?php echo $product['product_id']; ?>')">
                          </td>
                          <td style="vertical-align: middle;">
                            <span class="stocks-span" id="stocks-span-<?php echo $product['product_id']; ?>" onclick="enableEdit('<?php echo $product['product_id']; ?>', 'stocks')" style="cursor:pointer;">
                              <?php echo $product['stock_quantity']; ?>
                            </span>
                            <input type="number" name="quickStocks" id="stocks-input-<?php echo $product['product_id']; ?>" value="<?php echo $product['stock_quantity']; ?>" min="0" class="form-control form-control-sm d-none" style="width: 70px; display:inline-block;" required oninput="showCheck('<?php echo $product['product_id']; ?>')">
                          </td>
                          <td style="vertical-align: middle;">
                            <span class="badge bg-<?php 
                              switch($product['status']) {
                                case 'active': echo 'success'; break;
                                case 'inactive': echo 'secondary'; break;
                                case 'pending': echo 'warning'; break;
                                case 'rejected': echo 'danger'; break;
                                default: echo 'secondary';
                              }
                            ?> status-tooltip" 
                            data-bs-toggle="tooltip" 
                            title="<?php 
                              switch($product['status']) {
                                case 'active': echo 'Product is live and visible to buyers'; break;
                                case 'inactive': echo 'Product is not currently available'; break;
                                case 'pending': echo 'Waiting for admin approval'; break;
                                case 'rejected': echo 'Product was rejected - please check your email for details'; break;
                                default: echo 'Status unknown';
                              }
                            ?>">
                              <?php echo ucfirst($product['status']); ?>
                            </span>
                          </td>
                          <td style="vertical-align: middle;">
                            <div style="display: flex; gap: 0.25rem; align-items: center;">
                              <button type="submit" id="check-btn-<?php echo $product['product_id']; ?>" class="btn btn-sm btn-success d-none" title="Update"><i class="fas fa-check"></i></button>
                              <button class="btn btn-sm btn-outline-danger" type="button" onclick="deleteProduct(<?php echo $product['product_id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                <i class="fas fa-trash"></i>
                              </button>
                            </div>
                          </td>
                        </form>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="editProductForm" method="post" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title fw-bold" id="editProductModalLabel">Edit Product</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="editProductId" name="product_id">
              <input type="hidden" name="updateProduct" value="1">
              <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Any changes to your product will require admin re-verification before becoming visible to buyers.
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label for="editProductName" class="form-label">Product Name</label>
                  <input type="text" class="form-control" id="editProductName" name="editProductName" required>
                </div>
                <div class="col-md-6">
                  <label for="editCategory" class="form-label">Category</label>
                  <select class="form-select" id="editCategory" name="editCategory" required>
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo htmlspecialchars($cat['name']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label for="editDescription" class="form-label">Description</label>
                  <textarea class="form-control" id="editDescription" name="editDescription" rows="2" required></textarea>
                </div>
                <div class="col-md-4">
                  <label for="editPrice" class="form-label">Price (₱)</label>
                  <input type="number" class="form-control" id="editPrice" name="editPrice" min="0.01" step="0.01" required>
                </div>
                <div class="col-md-4">
                  <label for="editStocks" class="form-label">Stocks</label>
                  <input type="number" class="form-control" id="editStocks" name="editStocks" min="0" required>
                </div>
                <div class="col-md-4">
                  <label for="editProductImage" class="form-label">Product Image (Optional)</label>
                  <input class="form-control" type="file" id="editProductImage" name="editProductImage" accept="image/*">
                  <div class="edit-image-preview mt-2">
                    <img id="currentImage" src="" alt="Current Image" style="max-width: 120px; max-height: 120px;" class="rounded border d-none">
                    <div id="removeImageContainer" class="form-check mt-2 d-none">
                      <input class="form-check-input" type="checkbox" id="removeImage" name="removeImage">
                      <label class="form-check-label text-danger" for="removeImage">
                        Remove current image
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Update Product</button>
            </div>
          </form>
        </div>
      </div>
    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/js/all.min.js"></script>
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
    <script>
      // Initialize tooltips and handle SweetAlert messages
      document.addEventListener('DOMContentLoaded', function() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle SweetAlert messages from PHP
        <?php if (isset($success_message)): ?>
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo addslashes($success_message); ?>',
            confirmButtonColor: '#198754'
          });
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?php echo addslashes($error_message); ?>',
            confirmButtonColor: '#dc3545'
          });
        <?php endif; ?>
      });

      // Enhanced editProduct function
      function editProduct(productId, name, category, description, price, stocks, imageUrl) {
        document.getElementById('editProductId').value = productId;
        document.getElementById('editProductName').value = name;
        document.getElementById('editCategory').value = category;
        document.getElementById('editDescription').value = description;
        document.getElementById('editPrice').value = price;
        document.getElementById('editStocks').value = stocks;
        
        // Show current image if exists
        const currentImage = document.getElementById('currentImage');
        const removeImageContainer = document.getElementById('removeImageContainer');
        
        if (imageUrl && imageUrl !== 'null') {
          currentImage.src = imageUrl;
          currentImage.classList.remove('d-none');
          removeImageContainer.classList.remove('d-none');
        } else {
          currentImage.classList.add('d-none');
          removeImageContainer.classList.add('d-none');
        }
        
        // Show the modal
        const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
      }

      // Delete product function
      function deleteProduct(productId, productName) {
        Swal.fire({
          title: "Are you sure?",
          text: "you want to delete this product",
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "Yes, delete it!"
        }).then((result) => {
          if (result.isConfirmed) {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
              <input type="hidden" name="product_id" value="${productId}">
              <input type="hidden" name="deleteProduct" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
          }
        });
      }

      // Enhanced image preview for edit product image upload
      document.getElementById('editProductImage').addEventListener('change', function(event) {
        const currentImage = document.getElementById('currentImage');
        const file = event.target.files[0];
        
        if (file) {
          // Validate file type
          if (!file.type.match('image.*')) {
            alert('Please select an image file (JPEG, PNG, etc.)');
            this.value = '';
            return;
          }
          
          // Validate file size (2MB max)
          if (file.size > 2 * 1024 * 1024) {
            alert('Image must be less than 2MB');
            this.value = '';
            return;
          }
          
          const reader = new FileReader();
          reader.onload = function(e) {
            currentImage.src = e.target.result;
            currentImage.classList.remove('d-none');
          };
          reader.readAsDataURL(file);
        }
      });

      // Form validation for edit form
      document.getElementById('editProductForm').addEventListener('submit', function(e) {
        const price = parseFloat(document.getElementById('editPrice').value);
        const stocks = parseInt(document.getElementById('editStocks').value);
        
        if (price <= 0) {
          e.preventDefault();
          alert('Price must be greater than 0');
          return;
        }
        
        if (stocks < 0) {
          e.preventDefault();
          alert('Stock quantity cannot be negative');
          return;
        }
      });

      // Search and filter logic for table rows
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterCategory = document.getElementById('filterCategory');
        const filterStatus = document.getElementById('filterStatus');
        const searchBtn = document.getElementById('searchBtn');
        const resetBtn = document.getElementById('resetBtn');
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');

        function filterTable() {
          const searchValue = searchInput.value.toLowerCase();
          const category = filterCategory.value;
          const status = filterStatus.value;
          
          rows.forEach(row => {
            const product = row.cells[0].innerText.toLowerCase();
            const cat = row.cells[1].innerText;
            const stat = row.cells[5].querySelector('.badge').innerText.trim();
            let show = true;
            
            if (searchValue && !product.includes(searchValue)) show = false;
            if (category && cat !== category) show = false;
            if (status && stat !== status) show = false;
            
            row.style.display = show ? '' : 'none';
          });
        }
        
        searchBtn.addEventListener('click', filterTable);
        filterCategory.addEventListener('change', filterTable);
        filterStatus.addEventListener('change', filterTable);
        resetBtn.addEventListener('click', function() {
          searchInput.value = '';
          filterCategory.value = '';
          filterStatus.value = '';
          filterTable();
        });
      });

      function enableEdit(productId, field) {
        if (field === 'price') {
          document.getElementById('price-span-' + productId).classList.add('d-none');
          document.getElementById('price-input-' + productId).classList.remove('d-none');
          document.getElementById('price-input-' + productId).focus();
          document.getElementById('check-btn-' + productId).classList.remove('d-none');
        } else if (field === 'stocks') {
          document.getElementById('stocks-span-' + productId).classList.add('d-none');
          document.getElementById('stocks-input-' + productId).classList.remove('d-none');
          document.getElementById('stocks-input-' + productId).focus();
          document.getElementById('check-btn-' + productId).classList.remove('d-none');
        }
      }
      function showCheck(productId) {
        document.getElementById('check-btn-' + productId).classList.remove('d-none');
      }
    </script>
  </body>
</html>