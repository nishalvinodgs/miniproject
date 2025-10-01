<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'thriftin';
$username = 'root'; // Update with your DB username
$password = '';     // Update with your DB password

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get order ID from query parameter
    $order_id = $_GET['order_id'] ?? null;
    
    if (!$order_id) {
        throw new Exception('Order ID is required');
    }
    
    // Fetch order details with customer information
    $stmt = $pdo->prepare("
        SELECT 
            o.*,
            c.email as customer_email,
            c.first_name,
            c.last_name
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Fetch order items
    $stmt = $pdo->prepare("
        SELECT 
            oi.*,
            p.title as current_product_title,
            p.image as current_product_image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Add items to order data
    $order['items'] = $order_items;
    $order['items_count'] = count($order_items);
    
    // Calculate some additional info
    $order['estimated_delivery'] = date('Y-m-d', strtotime($order['created_at'] . ' +7 days'));
    
    echo json_encode([
        'success' => true,
        'order' => $order
    ]);
    
} catch (Exception $e) {
    error_log('Get order details error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>