<?php
// Start output buffering to prevent unwanted output
ob_start();

include 'bardheader.php';
include '../config/database.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output before JSON response
    ob_clean();
    
    // Suppress any errors that might output HTML
    error_reporting(0);
    
    $response = array();
    
    try {
        // Check if database connection exists
        if (!isset($pdo)) {
            throw new Exception('Database connection not available.');
        }
        
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $ecocoins_cost = (int)$_POST['ecocoins_cost'];
        $stocks = (int)$_POST['stocks'];
        
        // Validate inputs
        if (empty($name) || $ecocoins_cost <= 0 || $stocks < 0) {
            throw new Exception('Please fill all required fields correctly.');
        }
        
        // Handle image upload
        $image_path = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/bard_products/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    throw new Exception('Failed to create upload directory.');
                }
            }
            
            $file_info = pathinfo($_FILES['image']['name']);
            $extension = strtolower($file_info['extension']);
            
            // Validate file type
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            if (!in_array($extension, $allowed_types)) {
                throw new Exception('Only JPG, JPEG, PNG, and GIF files are allowed.');
            }
            
            // Generate unique filename
            $filename = 'bard_product_' . uniqid() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $image_path = 'uploads/bard_products/' . $filename;
            } else {
                throw new Exception('Failed to upload image.');
            }
        } else {
            $file_error = $_FILES['image']['error'] ?? 'No file uploaded';
            throw new Exception('Please select an image. Error: ' . $file_error);
        }
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO bardproducts (name, description, ecocoins_cost, stocks, image) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt->execute([$name, $description, $ecocoins_cost, $stocks, $image_path])) {
            throw new Exception('Failed to insert product into database.');
        }
        
        $response['success'] = true;
        $response['message'] = 'Product added successfully!';
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }
    
    // Ensure clean output
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($response);
    exit;
}

// Fetch existing products
$products = [];
try {
    $stmt = $pdo->query("SELECT * FROM bardproducts ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    // Handle error silently for now
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Bard Redeem Products - Admin</title>
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
        html, body {
            overflow-x: hidden;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }
        .main-content {
            margin-left: 280px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f8f9fa;
            width: 100%;
        }
        .container-fluid {
            max-width: 1200px;
            margin-left: 25px;
            margin-right: auto;
            padding: 0 20px;
            margin-top: 1rem;
            box-sizing: border-box;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 250px; }
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 15px; }
            .container-fluid { padding: 0 8px; }
        }

        .products-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-bottom: 1px solid #e9ecef;
            padding: 20px 25px;
            border-radius: 15px 15px 0 0;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #28bf4b;
            box-shadow: 0 0 0 0.2rem rgba(40, 191, 75, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .btn-success {
            background: linear-gradient(135deg, #28bf4b 0%, #20a745 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 600;
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-success:hover, .btn-success:focus {
            background: linear-gradient(135deg, #20a745 0%, #1e7e34 100%);
            color: #fff;
            box-shadow: 0 4px 15px rgba(40,191,75,0.3);
            transform: translateY(-2px);
        }

        .product-img-preview {
            height: 200px;
            object-fit: cover;
            width: 100%;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }

        .card-body {
            padding: 25px;
        }

        .product-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .badge {
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 1.3rem;
            margin-bottom: 10px;
            color: #495057;
        }

        .empty-state p {
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 0.8rem;
            color: #fff;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-1px);
        }

        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <?php include 'bardsidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid">
            
            <!-- Add Product Form -->
            <div class="products-card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Add New Bard Redeem Products</h5>
                </div>
                <div class="card-body">
                    <form id="productForm" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ecocoins_cost" class="form-label">EcoCoins Cost</label>
                                    <input type="number" class="form-control" id="ecocoins_cost" name="ecocoins_cost" min="1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="stocks" class="form-label">Available Stocks</label>
                                    <input type="number" class="form-control" id="stocks" name="stocks" min="0" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <img id="imagePreview" class="mt-3 product-img-preview d-none" alt="Preview">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-plus me-2"></i>Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>


        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const productForm = document.getElementById('productForm');
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');

        imageInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '';
                imagePreview.classList.add('d-none');
            }
        });

        productForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Product...';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                console.log('Raw server response:', text); // See actual response
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Product Added Successfully!',
                            text: data.message,
                            confirmButtonColor: '#28bf4b',
                            confirmButtonText: 'Great!',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            productForm.reset();
                            imagePreview.src = '';
                            imagePreview.classList.add('d-none');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: data.message,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Try Again'
                        });
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text:', text);
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'The server returned an invalid response. Please check console for details.',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Connection Error',
                    text: 'An error occurred while connecting to the server. Please try again.',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });

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
                    .then(response => response.text())
                    .then(text => {
                        try {
                            const data = JSON.parse(text);
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
                                    text: data.message,
                                    confirmButtonColor: '#dc3545'
                                });
                            }
                        } catch (e) {
                            console.error('Response text:', text);
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: 'The server returned an invalid response.',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
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
    </script>
</body>
</html>
