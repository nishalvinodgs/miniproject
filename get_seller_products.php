<?php
session_start();
header('Content-Type: application/json');
include 'db_connect.php';

if (!isset($_SESSION['seller'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$email = $_SESSION['seller'];
$sellerId = null;

$stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res && ($row = $res->fetch_assoc())) {
    $sellerId = (int)$row['id'];
}
$stmt->close();

if (!$sellerId) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, title, category, price, approval_status FROM products WHERE seller_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $sellerId);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>



