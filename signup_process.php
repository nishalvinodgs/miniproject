<?php
// DB Connection
$servername = "localhost";
$username   = "root";   // change if needed
$password   = "";       // change if needed
$dbname     = "thriftin";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

$userType   = $_POST['userType'];
$firstName  = $_POST['firstName'];
$lastName   = $_POST['lastName'];
$email      = $_POST['email'];
$phone      = $_POST['phone'];
$state      = $_POST['country'];
$city       = $_POST['city'];
$password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
$newsletter = isset($_POST['newsletter']) ? 1 : 0;
$terms      = isset($_POST['terms']) ? 1 : 0;

$table = ($userType === "seller") ? "sellers" : "customers";

// ✅ Check for duplicate email before inserting
$check = $conn->prepare("SELECT id FROM $table WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // Email already exists
    echo "<script>alert('Email already registered! Please log in.'); window.location.href='login_page.html';</script>";
    exit();
}
$check->close();

// ✅ Insert new user
$sql = "INSERT INTO $table 
        (first_name, last_name, email, phone, state, city, password_hash, newsletter_opt_in, terms_accepted, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssss", 
    $firstName, $lastName, $email, $phone, $state, $city, $password, $newsletter, $terms
);

if ($stmt->execute()) {
    header("Location: login_page.html?signup=success");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
