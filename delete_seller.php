<?php
// delete_seller.php
$conn = new mysqli("localhost", "root", "", "thriftin");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$id = intval($_POST['id']);
$conn->query("DELETE FROM sellers WHERE id=$id");

echo json_encode(["success" => true]);
$conn->close();
?>
