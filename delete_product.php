<?php
session_start();
header('Content-Type: application/json');

// Check if user is admin (you may want to add proper admin authentication here)
// For now, we'll proceed with the deletion

include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['id']) || empty($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

$productId = intval($_POST['id']);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

try {
    // First, check if the product exists
    $checkStmt = $conn->prepare("SELECT id, title FROM products WHERE id = ?");
    $checkStmt->bind_param("i", $productId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    $checkStmt->close();
    
    // Delete the product from the database
    $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $deleteStmt->bind_param("i", $productId);
    
    if ($deleteStmt->execute()) {
        // Also delete any related order items (optional - you might want to keep order history)
        $deleteOrderItemsStmt = $conn->prepare("DELETE FROM order_items WHERE product_id = ?");
        $deleteOrderItemsStmt->bind_param("i", $productId);
        $deleteOrderItemsStmt->execute();
        $deleteOrderItemsStmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => "Product '{$product['title']}' deleted successfully"
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete product from database']);
    }
    
    $deleteStmt->close();
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}

$conn->close();
?>