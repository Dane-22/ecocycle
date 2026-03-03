<?php
include '../config/database.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$response = array();

try {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        throw new Exception('Invalid product ID');
    }
    
    $product_id = (int)$_POST['id'];
    
    // First, get the product to find the image path
    $stmt = $pdo->prepare("SELECT image FROM bardproducts WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM bardproducts WHERE id = ?");
    $stmt->execute([$product_id]);
    
    // Delete image file if it exists
    if ($product['image'] && file_exists('../' . $product['image'])) {
        unlink('../' . $product['image']);
    }
    
    $response['success'] = true;
    $response['message'] = 'Product deleted successfully!';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?> 