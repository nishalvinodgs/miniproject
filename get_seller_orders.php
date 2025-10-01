<?php
session_start();
include 'db_connect.php';

// Check seller session
$sellerEmail = $_SESSION['seller_email'] ?? null;

if (!$sellerEmail) {
    echo json_encode([]);
    exit;
}

// Find seller ID
$stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
$stmt->bind_param("s", $sellerEmail);
$stmt->execute();
$result = $stmt->get_result();
$seller = $result->fetch_assoc();
$sellerId = $seller['id'] ?? 0;

if (!$sellerId) {
    echo json_encode([]);
    exit;
}

// Fetch orders related to this seller
$query = "
    SELECT 
        o.order_id,
        oi.product_name,
        oi.price,
        oi.quantity,
        o.order_status,
        c.first_name,
        c.last_name,
        c.email AS buyer_email
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN customers c ON o.customer_id = c.id
    WHERE oi.seller_id = ?
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$orders = $stmt->get_result();

$data = [];
while ($row = $orders->fetch_assoc()) {
    $data[] = [
        "order_id"     => $row['order_id'],
        "product_name" => $row['product_name'],
        "price"        => $row['price'],
        "quantity"     => $row['quantity'],
        "order_status" => $row['order_status'],
        "buyer_email"  => $row['first_name'] . " " . $row['last_name'] . " (" . $row['buyer_email'] . ")"
    ];
}

echo json_encode($data);