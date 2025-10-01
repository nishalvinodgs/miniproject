<?php
// edit_seller.php
$conn = new mysqli("localhost", "root", "", "thriftin");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$id = intval($_POST['id']);
$nameParts = explode(' ', $_POST['name'], 2);
$first = $nameParts[0];
$last = isset($nameParts[1]) ? $nameParts[1] : '';
$email = $_POST['email'];

$stmt = $conn->prepare("UPDATE sellers SET first_name=?, last_name=?, email=? WHERE id=?");
$stmt->bind_param("sssi", $first, $last, $email, $id);
$stmt->execute();

echo json_encode(["success" => true]);
$conn->close();
?>
