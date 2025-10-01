<?php
$conn = new mysqli("localhost", "root", "", "thriftin");
$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$map = ['approve' => 'approved', 'reject' => 'rejected'];
if (!$id || !isset($map[$action])) { header('Location: admin_dashboard.php'); exit; }
$status = $map[$action];
$stmt = $conn->prepare("UPDATE products SET approval_status=?, status=? WHERE id=?");
$stmt->bind_param("ssi", $status, $status, $id);
$stmt->execute();
header("Location: admin_dashboard.php");
?>
