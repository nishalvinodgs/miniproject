<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $conn = new mysqli("localhost", "root", "", "thriftin");
    
    if ($conn->connect_error) {
        echo json_encode(['ok' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT first_name, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'ok' => true,
            'profile' => [
                'firstName' => $row['first_name'],
                'email' => $row['email']
            ]
        ]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'User not found']);
    }
    
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
}
?>



