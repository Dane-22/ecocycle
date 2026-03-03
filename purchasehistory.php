<?php
include 'homeheader.php';
require_once 'config/database.php';
require_once 'config/session_check.php';

// Fetch purchase history for the current buyer
$buyer_id = $_SESSION['user_id'];
$purchases = [];

try {
    $query = "
        SELECT 
            ph.purchase_id,
            ph.order_id,
            ph.created_at,
            ph.status,
            ph.quantity,
            ph.price,
            p.name as product_name,
            p.image_url,
            s.fullname as seller_name
        FROM purchase_history ph
        JOIN products p ON ph.product_id = p.product_id
        JOIN sellers s ON ph.seller_id = s.seller_id
        WHERE ph.buyer_id = ?
        ORDER BY ph.created_at DESC
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$buyer_id]);
    $purchases = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching purchase history: " . $e->getMessage());
    $purchases = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Purchases - Ecocycle Nluc</title>
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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="main-content">
          <div class="container-lg mt-5">
            <h3 class="fw-bold mb-4 text-start">My Purchases</h3>
            <div class="search-filter-row">
              <div class="input-group">
                <input type="text" id="purchaseSearch" class="form-control" placeholder="Search order#, product, seller...">
                <button id="purchaseSearchBtn" class="btn" type="button" style="background-color: #2c786c; border-color: #2c786c;">
                  <i class="fas fa-search" style="color: #fff;"></i>
                </button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-success">
                  <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Seller</th>
                    <th>Quantity</th>
                    <th>Price</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($purchases)): ?>
                    <tr>
                      <td colspan="6" class="text-center">No purchases found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($purchases as $purchase): ?>
                      <?php $rowDate = date('Y-m-d', strtotime($purchase['created_at'])); ?>
                      <tr data-date="<?php echo $rowDate; ?>">
                        <td><?php echo htmlspecialchars($purchase['order_id']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($purchase['created_at'])); ?></td>
                        <td>
                          <div class="d-flex align-items-center">
                <?php if ($purchase['image_url']): ?>
                              <img src="<?php echo htmlspecialchars($purchase['image_url']); ?>" 
                                   alt="<?php echo htmlspecialchars($purchase['product_name']); ?>" 
                                   class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($purchase['product_name']); ?></span>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($purchase['seller_name']); ?></td>
                        <td><?php echo htmlspecialchars($purchase['quantity']); ?></td>
                        <td>₱<?php echo number_format($purchase['price'] * $purchase['quantity'], 2); ?></td>
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
    <style>
      .search-filter-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: nowrap;
        align-items: center;
      }
      .search-filter-row .input-group {
        flex: 0 1 320px;
        min-width: 200px;
        max-width: 320px;
      }
    </style>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('purchaseSearch');
        const searchBtn = document.getElementById('purchaseSearchBtn');
        const table = document.querySelector('table');
        const rows = Array.from(table.querySelectorAll('tbody tr'));

        function filterRows() {
          const q = (searchInput.value || '').toLowerCase().trim();
          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 1) return; // skip 'no purchases' row

            const orderId = (cells[0].textContent || '').toLowerCase();
            const date = row.dataset.date || '';
            const product = (cells[2].textContent || '').toLowerCase();
            const seller = (cells[3].textContent || '').toLowerCase();

            let matchesQuery = true;
            if (q) {
              matchesQuery = orderId.includes(q) || product.includes(q) || seller.includes(q);
            }

            if (matchesQuery) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        }

        searchBtn.addEventListener('click', filterRows);
        searchInput.addEventListener('keypress', function(e) {
          if (e.key === 'Enter') { e.preventDefault(); filterRows(); }
        });
      });
    </script>
  </body>
</html>
