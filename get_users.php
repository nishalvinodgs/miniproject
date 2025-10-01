<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "thriftIN");
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

$sql = "SELECT id, CONCAT(first_name, ' ', last_name) AS name, email FROM customers";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['status'] = 'Active'; // you can adjust status logic later
        $users[] = $row;
    }
}

echo json_encode($users);
$conn->close();
?>
