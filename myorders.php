<?php
include 'homeheader.php';
require_once 'config/database.php';
require_once 'config/session_check.php';

// Fetch orders for the current buyer
$buyer_id = $_SESSION['user_id'];
$orders = [];

try {
    $query = "
      SELECT 
        o.order_id,
        o.created_at,
        o.total_amount,
        oi.status,
        o.shipping_address,
        o.payment_method,
        oi.tracking_number,
        p.name as product_name,
        p.image_url,
        oi.quantity,
        oi.price as item_price,
        oi.product_id
      FROM orders o
      JOIN order_items oi ON o.order_id = oi.order_id
      JOIN products p ON oi.product_id = p.product_id
      WHERE o.buyer_id = ? AND oi.status != 'cancelled'
      ORDER BY o.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$buyer_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>My Orders - Ecocycle Nluc</title>
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

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="main-content">
          <div class="container-lg mt-5">
            <h3 class="fw-bold mb-4 text-start">My Orders</h3>
            <div class="search-filter-row">
              <div class="input-group">
                <input type="text" id="orderSearch" class="form-control" placeholder="Search orders...">
                <button id="searchBtn" class="btn" type="button" style="background-color: #2c786c; border-color: #2c786c;">
                  <i class="fas fa-search" style="color: #fff;"></i>
                </button>
              </div>
              <select id="statusFilter" class="form-select">
                <option value="">All Types</option>
                <option value="delivered">Delivered</option>
                <option value="pending">Pending</option>
                <option value="shipped">Shipped</option>
                <option value="confirmed">Confirmed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle">
                <thead class="table-success">
                  <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th>LBC Track Number</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($orders)): ?>
                    <tr>
                      <td colspan="8" class="text-center">No orders found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                          <div class="d-flex align-items-center">
                            <?php if ($order['image_url']): ?>
                              <img src="<?php echo htmlspecialchars($order['image_url']); ?>" 
                                   alt="<?php echo htmlspecialchars($order['product_name']); ?>" 
                                   class="me-2" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($order['product_name']); ?></span>
                          </div>
                        </td>
                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                        <td>₱<?php echo number_format($order['item_price'] * $order['quantity'], 2); ?></td>
                        <td>
                          <span class="badge <?php 
                            switch($order['status']) {
                              case 'delivered': echo 'bg-success'; break;
                              case 'shipped': echo 'bg-info'; break;
                              case 'confirmed': echo 'bg-warning'; break;
                              case 'pending': echo 'bg-secondary'; break;
                              case 'cancelled': echo 'bg-danger'; break;
                              default: echo 'bg-secondary';
                            }
                          ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                          </span>
                        </td>
                        <td>
                          <?php if (!empty($order['tracking_number'])): ?>
                            <span class="text-muted"><?php echo htmlspecialchars($order['tracking_number']); ?></span>
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <button 
                            class="btn btn-sm btn-outline-primary view-order-btn"
                            data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>"
                            data-order-date="<?php echo date('M d, Y', strtotime($order['created_at'])); ?>"
                            data-product-name="<?php echo htmlspecialchars($order['product_name']); ?>"
                            data-quantity="<?php echo htmlspecialchars($order['quantity']); ?>"
                            data-total-price="<?php echo number_format($order['item_price'] * $order['quantity'], 2); ?>"
                            data-status="<?php echo ucfirst(htmlspecialchars($order['status'])); ?>"
                            data-tracking-number="<?php echo htmlspecialchars($order['tracking_number']); ?>"
                            data-shipping-address="<?php echo htmlspecialchars($order['shipping_address']); ?>"
                            data-payment-method="<?php echo htmlspecialchars($order['payment_method']); ?>"
                            data-image-url="<?php echo htmlspecialchars($order['image_url']); ?>"
                          >
                            <i class="fas fa-eye"></i> View
                          </button>
                            <?php if ($order['status'] === 'shipped' || $order['status'] === 'confirmed'): ?>
                            <button 
                              class="btn btn-sm btn-outline-primary order-received-btn ms-2"
                              data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>"
                              data-product-id="<?php echo htmlspecialchars($order['product_id'] ?? ''); ?>"
                            >
                              <i class="fas fa-check"></i> Order Received
                            </button>
                            <?php endif; ?>
                            <?php if ($order['status'] === 'pending'): ?>
                            <button 
                              class="btn btn-sm btn-outline-danger cancel-order-btn ms-2"
                              data-order-id="<?php echo htmlspecialchars($order['order_id']); ?>"
                              data-product-id="<?php echo htmlspecialchars($order['product_id'] ?? ''); ?>"
                            >
                              <i class="fas fa-times"></i> Cancel
                            </button>
                            <?php endif; ?>
                        </td>
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
      .search-filter-row .form-select {
        width: 200px;
        min-width: 120px;
      }
    </style>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('orderSearch');
        const statusFilter = document.getElementById('statusFilter');
        const searchBtn = document.getElementById('searchBtn');
        const table = document.querySelector('table');
        const rows = table.querySelectorAll('tbody tr');
        function filterTable() {
          const searchValue = searchInput.value.toLowerCase();
          const statusValue = statusFilter.value;
          rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length === 1) return; // Skip "No orders found" row
            
            const orderText = row.textContent.toLowerCase();
            const statusCell = cells[5];
            const statusText = statusCell.textContent.trim().toLowerCase();
            const matchesSearch = orderText.includes(searchValue);
            const matchesStatus = !statusValue || statusText === statusValue;
            if (matchesSearch && matchesStatus) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        }

        searchBtn.addEventListener('click', filterTable);
        statusFilter.addEventListener('change', filterTable);

        // Modal logic
        const orderModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
        document.querySelectorAll('.view-order-btn').forEach(btn => {
          btn.addEventListener('click', function() {
            document.getElementById('modalOrderId').textContent = this.dataset.orderId;
            document.getElementById('modalOrderDate').textContent = this.dataset.orderDate;
            document.getElementById('modalProductName').textContent = this.dataset.productName;
            document.getElementById('modalQuantity').textContent = this.dataset.quantity;
            document.getElementById('modalTotalPrice').textContent = this.dataset.totalPrice;
            document.getElementById('modalStatus').textContent = this.dataset.status;
            document.getElementById('modalTrackingNumber').textContent = this.dataset.trackingNumber || '-';
            document.getElementById('modalShippingAddress').textContent = this.dataset.shippingAddress;
            document.getElementById('modalPaymentMethod').textContent = this.dataset.paymentMethod;
            const img = document.getElementById('modalProductImage');
            if (this.dataset.imageUrl) {
              img.src = this.dataset.imageUrl;
              img.style.display = '';
            } else {
              img.style.display = 'none';
            }
            orderModal.show();
          });
        });

          // Order Received button logic with SweetAlert2
          document.querySelectorAll('.order-received-btn').forEach(btn => {
            btn.addEventListener('click', function() {
              const orderId = this.dataset.orderId;
              const productId = this.dataset.productId;
              Swal.fire({
                title: 'Order Received?',
                text: 'Are you sure you want to mark this order as delivered?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28bf4b',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, mark as delivered'
              }).then((result) => {
                if (result.isConfirmed) {
                  fetch('api/order-received.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&product_id=${productId}`
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.success) {
                      Swal.fire({
                        title: 'Success!',
                        text: 'Order marked as delivered!',
                        icon: 'success',
                        confirmButtonColor: '#28bf4b'
                      }).then(() => {
                        location.reload();
                      });
                    } else {
                      Swal.fire('Error', 'Failed to update order: ' + (data.error || 'Unknown error'), 'error');
                    }
                  })
                  .catch(() => Swal.fire('Error', 'Network error. Please try again.', 'error'));
                }
              });
            });
          });

          // Cancel Order button logic with SweetAlert2
          document.querySelectorAll('.cancel-order-btn').forEach(btn => {
            btn.addEventListener('click', function() {
              const orderId = this.dataset.orderId;
              const productId = this.dataset.productId;
              Swal.fire({
                title: 'Cancel Order?',
                text: 'Are you sure you want to cancel this order? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, cancel order'
              }).then((result) => {
                if (result.isConfirmed) {
                  fetch('api/cancel-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `order_id=${orderId}&product_id=${productId}`
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.success) {
                      Swal.fire({
                        title: 'Cancelled!',
                        text: 'Your order has been cancelled successfully.',
                        icon: 'success',
                        confirmButtonColor: '#28bf4b'
                      }).then(() => {
                        location.reload();
                      });
                    } else {
                      Swal.fire('Error', 'Failed to cancel order: ' + (data.error || 'Unknown error'), 'error');
                    }
                  })
                  .catch(() => Swal.fire('Error', 'Network error. Please try again.', 'error'));
                }
              });
            });
          });
      });
    </script>
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderDetailsLabel">Order Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p><strong>Order #:</strong> <span id="modalOrderId"></span></p>
            <p><strong>Date:</strong> <span id="modalOrderDate"></span></p>
            <p><strong>Product:</strong> <span id="modalProductName"></span></p>
            <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
            <p><strong>Total Price:</strong> ₱<span id="modalTotalPrice"></span></p>
            <p><strong>Status:</strong> <span id="modalStatus"></span></p>
            <p><strong>LBC Track Number:</strong> <span id="modalTrackingNumber"></span></p>
            <p><strong>Delivery Address:</strong> <span id="modalShippingAddress"></span></p>
            <p><strong>Payment Method:</strong> <span id="modalPaymentMethod"></span></p>
            <img id="modalProductImage" src="" alt="Product Image" style="width:80px; height:80px; object-fit:cover; border-radius:5px; display:none;">
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
