<?php
require_once 'config/session_check.php';
require_once 'config/database.php';

// Handle remove cart item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cart_id'])) {
    $cart_id = intval($_POST['remove_cart_id']);
    $buyer_id = getCurrentUserId();
    try {
        $stmt = $pdo->prepare('DELETE FROM Cart WHERE cart_id = ? AND buyer_id = ?');
        $stmt->execute([$cart_id, $buyer_id]);
    } catch (PDOException $e) {
        // Optionally handle error
    }
}

// Handle remove all cart items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_all_cart'])) {
    $buyer_id = getCurrentUserId();
    try {
        $stmt = $pdo->prepare('DELETE FROM Cart WHERE buyer_id = ?');
        $stmt->execute([$buyer_id]);
    } catch (PDOException $e) {
        // Optionally handle error
    }
}

// Handle update cart quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart_id'], $_POST['update_quantity'])) {
    $cart_id = intval($_POST['update_cart_id']);
    $quantity = max(1, intval($_POST['update_quantity']));
    $buyer_id = getCurrentUserId();
    try {
        $stmt = $pdo->prepare('UPDATE Cart SET quantity = ? WHERE cart_id = ? AND buyer_id = ?');
        $stmt->execute([$quantity, $cart_id, $buyer_id]);
    } catch (PDOException $e) {
        // Optionally handle error
    }
}

include 'homeheader.php';

// Fetch cart items for the current user
$cart_items = [];
$total = 0;
try {
    $buyer_id = getCurrentUserId();
    $stmt = $pdo->prepare('
        SELECT c.cart_id, c.quantity, p.product_id, p.name, p.price, p.image_url, p.stock_quantity
        FROM Cart c
        JOIN Products p ON c.product_id = p.product_id
        WHERE c.buyer_id = ?
    ');
    $stmt->execute([$buyer_id]);
    $cart_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $cart_items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Cart - Ecocycle Nluc</title>
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
            <h3 class="fw-bold mb-4 text-start">My Cart</h3>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="search-filter-row">
                <div class="input-group">
                  <input type="text" id="cartSearch" class="form-control" placeholder="Search cart...">
                  <button id="cartSearchBtn" class="btn" type="button" style="background-color: #2c786c; border-color: #2c786c;">
                    <i class="fas fa-search" style="color: #fff;"></i>
                  </button>
                </div>
              </div>
              <?php if (!empty($cart_items)): ?>
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="remove_all_cart" value="1">
                  <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete All</button>
                </form>
              <?php endif; ?>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-success">
                  <tr>
                    <th><input type="checkbox" id="selectAll" checked></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($cart_items)): ?>
                    <tr><td colspan="6" class="text-center">Your cart is empty.</td></tr>
                  <?php else: ?>
                    <?php foreach ($cart_items as $item): ?>
                      <?php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; ?>
                      <tr data-price="<?php echo htmlspecialchars($item['price']); ?>" data-stock="<?php echo (int)$item['stock_quantity']; ?>">
                        <td><input type="checkbox" class="product-checkbox" checked></td>
                        <td>
                          <div class="d-flex align-items-center">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'images/logo.png.png'); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-right: 10px;">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                          </div>
                        </td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                          <form method="POST" class="update-qty-form d-flex align-items-center" style="display:inline;">
                              <input type="hidden" name="update_cart_id" value="<?php echo $item['cart_id']; ?>">
                              <button type="button" class="btn btn-outline-secondary btn-qty-minus" tabindex="-1">-</button>
                              <input type="number" name="update_quantity" class="form-control form-control-sm qty-input text-center" value="<?php echo $item['quantity']; ?>" min="1" style="width: 50px;">
                              <button type="button" class="btn btn-outline-secondary btn-qty-plus" tabindex="-1">+</button>
                          </form>
                        </td>
                        <td class="subtotal">₱<?php echo number_format($subtotal, 2); ?></td>
                        <td>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="remove_cart_id" value="<?php echo $item['cart_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Remove</button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <tfoot>
                </tfoot>
              </table>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-3">
              <div class="d-flex align-items-center">
                <a href="home.php" id="continueShopping" class="btn btn-secondary btn-lg me-3">Continue Shopping <i class="fas fa-store"></i></a>
                <div class="fw-bold fs-4" id="cartTotal">Total: ₱<?php echo number_format($total, 2); ?></div>
              </div>
              <a href="checkout.php" id="proceedCheckout" class="btn btn-primary btn-lg<?php echo empty($cart_items) ? ' disabled' : ''; ?>">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Insufficient stock modal -->
    <div class="modal fade" id="insufficientStockModal" tabindex="-1" aria-labelledby="insufficientStockLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="insufficientStockLabel">Unable to Checkout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Unable to checkout due to insufficient stocks.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
          </div>
        </div>
      </div>
    </div>

    <!-- include bootstrap bundle (needed for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const proceed = document.getElementById('proceedCheckout');
        if (!proceed) return;

        proceed.addEventListener('click', function(e) {
          // If button is visually disabled, prevent navigation
          if (proceed.classList.contains('disabled')) {
            e.preventDefault();
            return;
          }

          // Check selected rows for stock availability
          const rows = document.querySelectorAll('tbody tr');
          let insufficient = false;
          rows.forEach(row => {
            const cb = row.querySelector('.product-checkbox');
            if (!cb || !cb.checked) return;
            const stock = parseInt(row.dataset.stock, 10) || 0;
            const qtyInput = row.querySelector('.qty-input');
            const qty = qtyInput ? parseInt(qtyInput.value, 10) || 0 : 0;
            if (qty > stock) {
              insufficient = true;
            }
          });

          if (insufficient) {
            e.preventDefault();
            const modalEl = document.getElementById('insufficientStockModal');
            if (modalEl) {
              const bsModal = new bootstrap.Modal(modalEl);
              bsModal.show();
            } else {
              alert('Unable to checkout due to insufficient stocks.');
            }
          }
        });
      });
    </script>
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
      .search-filter-row .form-select {
        width: 200px;
        min-width: 120px;
      }
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
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('cartSearch');
        const searchBtn = document.getElementById('cartSearchBtn');
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');

        function filterTable() {
          const searchValue = searchInput.value.toLowerCase();
          rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            if (rowText.includes(searchValue)) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        }

        searchBtn.addEventListener('click', filterTable);

        // Remove the following block to prevent double increment/decrement:
        // document.querySelectorAll('.update-qty-form').forEach(function(form) {
        //   const minusBtn = form.querySelector('.btn-qty-minus');
        //   const plusBtn = form.querySelector('.btn-qty-plus');
        //   const input = form.querySelector('.qty-input');

        //   minusBtn.addEventListener('click', function() {
        //     let value = parseInt(input.value, 10);
        //     if (value > 1) {
        //       input.value = value - 1;
        //       updateRowSubtotal(form.closest('tr'));
        //       updateTotal();
        //     }
        //   });

        //   plusBtn.addEventListener('click', function() {
        //     let value = parseInt(input.value, 10);
        //     input.value = value + 1;
        //     updateRowSubtotal(form.closest('tr'));
        //     updateTotal();
        //   });

        //   input.addEventListener('change', function() {
        //     if (input.value < 1) input.value = 1;
        //     updateRowSubtotal(form.closest('tr'));
        //     updateTotal();
        //   });
        // });

        function updateRowSubtotal(row) {
          const price = parseFloat(row.getAttribute('data-price'));
          const qty = parseInt(row.querySelector('.qty-input').value, 10);
          const subtotalCell = row.querySelector('.subtotal');
          const subtotal = price * qty;
          subtotalCell.textContent = `₱${subtotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        }

        function updateTotal() {
          let total = 0;
          document.querySelectorAll('tbody tr').forEach(row => {
            const checkbox = row.querySelector('.product-checkbox');
            if (checkbox.checked) {
              const price = parseFloat(row.getAttribute('data-price'));
              const qty = parseInt(row.querySelector('.qty-input').value, 10);
              total += price * qty;
            }
          });
          document.getElementById('cartTotal').textContent = `Total: ₱${total.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
        }

        // Select All functionality
        const selectAll = document.getElementById('selectAll');
        const productCheckboxes = document.querySelectorAll('.product-checkbox');
        if (selectAll) {
          selectAll.addEventListener('change', function() {
            productCheckboxes.forEach(cb => cb.checked = selectAll.checked);
            updateTotal();
          });
          productCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
              if (!cb.checked) {
                selectAll.checked = false;
              } else if ([...productCheckboxes].every(c => c.checked)) {
                selectAll.checked = true;
              }
              updateTotal();
            });
          });
        }

        // Initial total calculation
        updateTotal();
      });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.update-qty-form').forEach(function(form) {
    const minusBtn = form.querySelector('.btn-qty-minus');
    const plusBtn = form.querySelector('.btn-qty-plus');
    const qtyInput = form.querySelector('.qty-input');

    minusBtn.addEventListener('click', function() {
      let value = parseInt(qtyInput.value, 10);
      if (value > 1) {
        qtyInput.value = value - 1;
        form.submit();
      }
    });

    plusBtn.addEventListener('click', function() {
      let value = parseInt(qtyInput.value, 10);
      qtyInput.value = value + 1;
      form.submit();
    });
  });
});
</script>
  </body>
</html>
