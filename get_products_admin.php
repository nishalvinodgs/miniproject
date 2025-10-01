<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Use a safe, self-contained DB connection here to avoid accidental die() from includes
$conn = new mysqli("localhost", "root", "", "thriftin");
if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

// Prefer created_at ordering if available; fallback to id
$hasCreatedAt = false;
$colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'created_at'");
if ($colRes && $colRes->num_rows > 0) { $hasCreatedAt = true; }
$orderBy = $hasCreatedAt ? 'p.created_at DESC' : 'p.id DESC';

// Some schemas store seller name as first_name/last_name; build a name safely
$sellerNameExpr = "TRIM(CONCAT(COALESCE(s.first_name,''),' ',COALESCE(s.last_name,'')))";
$sql = "SELECT p.id, p.title, p.category, p.price, p.approval_status, ".
       "CASE WHEN $sellerNameExpr <> '' THEN $sellerNameExpr ELSE COALESCE(s.email,'Unknown') END AS seller" .
       ($hasCreatedAt ? ", p.created_at" : "") . "
        FROM products p
        LEFT JOIN sellers s ON p.seller_id = s.id
        ORDER BY $orderBy";

$result = $conn->query($sql);
if ($result === false) {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    exit;
}

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        "id" => (int)$row["id"],
        "name" => $row["title"],
        "seller" => $row["seller"],
        "category" => $row["category"],
        "price" => "₹" . $row["price"],
        "status" => $row["approval_status"] ?? 'pending'
    ];
}

echo json_encode($products);
?>
