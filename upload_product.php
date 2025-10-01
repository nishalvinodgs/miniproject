<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['seller'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// Resolve seller_id from session email
$sellerEmail = $_SESSION['seller'];
$sellerId = null;
$stmtSeller = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
$stmtSeller->bind_param("s", $sellerEmail);
$stmtSeller->execute();
$resSeller = $stmtSeller->get_result();
if ($resSeller && ($seller = $resSeller->fetch_assoc())) {
    $sellerId = (int)$seller['id'];
}
$stmtSeller->close();

if (!$sellerId) {
    http_response_code(400);
    echo "Seller not found";
    exit;
}

$title = $_POST['title'] ?? '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0.0;
$original_price = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
$category = $_POST['category'] ?? '';
$condition = $_POST['condition'] ?? '';
$description = $_POST['description'] ?? '';

// Handle image upload with robust checks
$targetDir = __DIR__ . "/uploads/";
if (!is_dir($targetDir)) {
    if (!mkdir($targetDir, 0777, true)) {
        http_response_code(500);
        echo "Failed to create uploads directory.";
        exit;
    }
}

// Ensure directory is writable
if (!is_writable($targetDir)) {
    @chmod($targetDir, 0777);
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo "No image file received. Ensure the form has enctype=\"multipart/form-data\".";
    exit;
}

$fileError = $_FILES['image']['error'];
if ($fileError !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive specified in the HTML form.',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk. Check folder permissions.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
    ];
    $msg = $errMap[$fileError] ?? 'Unknown upload error.';
    http_response_code(400);
    echo "Image upload failed: $msg";
    exit;
}

// Validate size (server may still block bigger files earlier)
$maxSize = 10 * 1024 * 1024; // 10MB
if ($_FILES['image']['size'] > $maxSize) {
    http_response_code(400);
    echo "Image too large. Max 10MB.";
    exit;
}

// Validate mime type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['image']['tmp_name']);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo "Unsupported image type. Allowed: JPG, PNG, WEBP.";
    exit;
}

// Generate safe unique name
$ext = $allowed[$mime];
$unique = bin2hex(random_bytes(6));
$imageName = time() . "_" . $unique . "." . $ext;
$imagePath = $targetDir . $imageName;

if (!is_uploaded_file($_FILES['image']['tmp_name']) || !move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
    http_response_code(400);
    echo "Image upload failed: could not move file. Check folder permissions on /uploads.";
    exit;
}

// Save relative path for DB
$dbImagePath = "uploads/" . $imageName;

// Ensure created_at is stored if the column exists
$hasCreatedAt = false;
$colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'created_at'");
if ($colRes && $colRes->num_rows > 0) { $hasCreatedAt = true; }

if ($hasCreatedAt) {
    $sql = "INSERT INTO products (title, price, original_price, category, condi, descript, image, seller_id, approval_status, created_at) VALUES (?,?,?,?,?,?,?,?, 'pending', NOW())";
} else {
    $sql = "INSERT INTO products (title, price, original_price, category, condi, descript, image, seller_id, approval_status) VALUES (?,?,?,?,?,?,?,?, 'pending')";
}

$stmt = $conn->prepare($sql);

// Always bind all placeholders; allow null for original_price
$stmt->bind_param(
    "sddssssi",
    $title,
    $price,
    $original_price,
    $category,
    $condition,
    $description,
    $dbImagePath,
    $sellerId
);

if ($stmt->execute()) {
    header("Location: seller_dashboard.php#my-products");
    exit;
} else {
    http_response_code(500);
    echo "Error: " . $conn->error;
}
?>
