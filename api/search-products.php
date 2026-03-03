<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Return empty if query is too short
if (strlen($query) < 2) {
    echo json_encode(['success' => false, 'products' => []]);
    exit;
}

try {
    // Search products by name, description, category, seller name, or seller location
    $searchTerm = "%{$query}%";
    
    $stmt = $pdo->prepare("
        SELECT 
            p.product_id,
            p.name,
            p.description,
            p.price,
            p.image_url,
            p.stock_quantity,
            c.name as category,
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
            p.name ASC
        LIMIT 10
    ");
    
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

    // Add ecocoins price to each product (1 PHP = 1 EcoCoin)
    foreach ($products as &$p) {
        $p['ecocoins_price'] = isset($p['price']) ? round($p['price'], 2) : 0;
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'count' => count($products)
    ]);
    
} catch (PDOException $e) {
    error_log("Search API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'products' => []
    ]);
} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while searching',
        'products' => []
    ]);
}
