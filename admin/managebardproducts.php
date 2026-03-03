<?php
include '../config/database.php';

// Handle stock and ecocoins update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['new_stock'];
    $new_ecocoins = (int)$_POST['new_ecocoins'];
    try {
        $stmt = $pdo->prepare("UPDATE bardproducts SET stocks = ?, ecocoins_cost = ? WHERE id = ?");
        $stmt->execute([$new_stock, $new_ecocoins, $product_id]);
        header("Location: managebardproducts.php?success=1");
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        header("Location: managebardproducts.php?error=" . urlencode($error_message));
        exit;
    }
}

// Fetch all bard products
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM bardproducts ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Now include header and output HTML
include 'bardheader.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bard Manage Products - Admin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="../css/vendor.css">
    <link rel="stylesheet" type="text/css" href="../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400;1,600;1,700&display=swap" rel="stylesheet">
    <style>
        html, body { overflow-x: hidden; }
        body { background-color: #f8f9fa; font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 280px; padding: 20px; min-height: 100vh; background-color: #f8f9fa; width: 100%; }
        .container-fluid { max-width: 1200px; margin-left: 25px; margin-right: auto; padding: 0 20px; margin-top: 1rem; box-sizing: border-box; }
        @media (max-width: 992px) { .main-content { margin-left: 250px; } }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding: 15px; } .container-fluid { padding: 0 8px; } }
    .products-card { background: transparent; border-radius: 0; box-shadow: none; border: none; overflow: visible; }
        .card-header { background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-bottom: 1px solid #e9ecef; padding: 20px 25px; border-radius: 15px 15px 0 0; }
        .card-title { font-size: 1.2rem; font-weight: 600; color: #2c3e50; margin: 0; }
        .product-img-preview { height: 80px; object-fit: cover; width: 80px; border-radius: 10px; border: 2px solid #e9ecef; }
        .empty-state { text-align: center; padding: 60px 20px; color: #6c757d; }
        .empty-state i { font-size: 4rem; margin-bottom: 20px; opacity: 0.5; }
        .empty-state h4 { font-size: 1.3rem; margin-bottom: 10px; color: #495057; }
        .empty-state p { font-size: 0.95rem; margin-bottom: 0; }
        .btn-action {
            padding: 8px 15px;
            font-size: 0.8rem;
            border-radius: 8px;
            min-width: 90px; /* Ensures equal width */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .btn-delete.btn-action {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            border: none;
        }
        .btn-delete.btn-action:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
        }
        .btn-edit.btn-action {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: #fff;
            border: none;
        }
        .btn-edit.btn-action:hover {
            background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
        }
        .stock-form { display: flex; gap: 8px; align-items: center; }
        .stock-input { width: 80px; }
        .table-responsive { margin-top: 20px; }
        td, th { vertical-align: middle !important; }
        /* Hide number input spinners for all browsers */
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; 
            margin: 0; 
        }
        input[type=number] {
            -moz-appearance: textfield; /* Firefox */
        }
    </style>
</head>
<body>
<?php include 'bardsidebar.php'; ?>
<div class="main-content">
    <div class="container-fluid">
    <div class="products-card" style="background:transparent;box-shadow:none;border:none;">
            <div class="card-header">
                <h5 class="card-title">Manage Bard Products</h5>
            </div>
            <div class="card-body">
                <?php /* Alerts are now handled by SweetAlert below */ ?>
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open"></i>
                        <h4>No Products Found</h4>
                        <p>No bard products have been added yet.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>EcoCoins Cost</th>
                                <th>Stocks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><img src="../<?php echo htmlspecialchars($product['image']); ?>" class="product-img-preview" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><span class="badge bg-success"><?php echo number_format($product['ecocoins_cost']); ?> EcoCoins</span></td>
                                <td><span class="badge bg-secondary"><?php echo $product['stocks']; ?></span></td>
                                <td>
                                    <button class="btn btn-edit btn-action btn-sm me-1" onclick="openEditStockModal(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>', <?php echo $product['stocks']; ?>, <?php echo $product['ecocoins_cost']; ?>)"><i class="fas fa-edit me-1"></i>Edit</button>
                                    <button class="btn btn-delete btn-action btn-sm" onclick="deleteProduct(<?php echo $product['id']; ?>)"><i class="fas fa-trash me-1"></i>Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Stock Modal -->
<div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="editStockForm">
      <input type="hidden" name="product_id" id="editStockProductId">
      <input type="hidden" name="update_stock" value="1">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editStockModalLabel">Edit Stock</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="editStockProductName" class="form-label">Product</label>
            <input type="text" class="form-control" id="editStockProductName" readonly>
          </div>
          <div class="mb-3">
            <label for="editStockInput" class="form-label">Stock</label>
            <input type="number" class="form-control" id="editStockInput" name="new_stock" min="0" required>
          </div>
          <div class="mb-3">
            <label for="editEcoCoinInput" class="form-label">EcoCoins Cost</label>
            <input type="number" class="form-control" id="editEcoCoinInput" name="new_ecocoins" min="0" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Save</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// SweetAlert for PHP alerts (success/error)
<?php if (isset($_GET['success'])): ?>
Swal.fire({
    icon: 'success',
    title: 'Success',
    text: 'Stock updated successfully!',
    confirmButtonColor: '#28bf4b',
    timer: 1800,
    timerProgressBar: true
});
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?php echo json_encode($_GET['error']); ?>,
    confirmButtonColor: '#dc3545',
    timer: 2500,
    timerProgressBar: true
});
<?php endif; ?>

// Delete product with SweetAlert confirmation
function deleteProduct(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id', productId);
            fetch('delete_bard_product.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: data.message,
                        confirmButtonColor: '#28bf4b',
                        timer: 2000,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to delete product.',
                        confirmButtonColor: '#dc3545'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'An error occurred while deleting the product.',
                    confirmButtonColor: '#dc3545'
                });
            });
        }
    });
}

// Open Edit Stock Modal
function openEditStockModal(id, name, stock, ecocoins) {
    document.getElementById('editStockProductId').value = id;
    document.getElementById('editStockProductName').value = name;
    document.getElementById('editStockInput').value = stock;
    document.getElementById('editEcoCoinInput').value = ecocoins;
    var modal = new bootstrap.Modal(document.getElementById('editStockModal'));
    modal.show();
}

// SweetAlert confirmation on modal submit
document.getElementById('editStockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Update Stock?',
        text: 'Are you sure you want to update the stock for this product?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28bf4b',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, update it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});
</script>
</body>
</html>
