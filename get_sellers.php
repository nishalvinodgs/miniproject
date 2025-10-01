<?php
// get_sellers.php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "thriftin");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email, 'Active' AS status, 0 AS products FROM sellers";
$result = $conn->query($sql);

$sellers = [];
while($row = $result->fetch_assoc()) {
    $sellers[] = $row;
}
echo json_encode($sellers);
$conn->close();
?>
