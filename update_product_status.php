<?php
include 'db_connect.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = $_POST['status'] ?? '';

// Allow only known statuses
$allowed = ['pending', 'approved', 'rejected'];
if (!$id || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(["ok" => false, "error" => "Invalid request"]);
    exit;
}

// Update both approval_status and the legacy status column for consistency with existing schema
$stmt = $conn->prepare("UPDATE products SET approval_status = ?, status = ? WHERE id = ?");
$stmt->bind_param("ssi", $status, $status, $id);

if ($stmt->execute()) {
    echo json_encode(["ok" => true]);
} else {
    http_response_code(500);
    echo json_encode(["ok" => false, "error" => $conn->error]);
}
?>
