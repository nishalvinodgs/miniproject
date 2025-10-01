<?php
// Disable error reporting to prevent XML output
error_reporting(0);
ini_set('display_errors', 0);

// Database connection settings
$host = "localhost";
$user = "root";       // default XAMPP username
$pass = "";           // default XAMPP password is empty
$db   = "thriftin";       // <-- replace with your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // Check if this is an AJAX request expecting JSON
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    } else {
        die("Connection failed: " . $conn->connect_error);
    }
}
?>
