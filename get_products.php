<?php
include 'db_connect.php';

$data = json_decode(file_get_contents("php://input"), true);
$category = $data['category'] ?? 'all';
$brand = $data['brand'] ?? '';
$maxPrice = $data['maxPrice'] ?? 50000;
$conditions = $data['conditions'] ?? ['new', 'like-new', 'good', 'fair'];
$sortBy = $data['sortBy'] ?? 'popular';
$page = $data['page'] ?? 1;
$perPage = 12;

// Build the SQL query
$sql = "SELECT * FROM products WHERE approval_status='approved' AND price <= ?";
$params = [$maxPrice];
$types = "d";

// Add category filter
if ($category !== 'all') {
    $sql .= " AND category=?";
    $params[] = $category;
    $types .= "s";
}

// Add brand filter (search in title or description)
if (!empty($brand)) {
    $sql .= " AND (LOWER(title) LIKE ? OR LOWER(descript) LIKE ?)";
    $brandPattern = '%' . strtolower($brand) . '%';
    $params[] = $brandPattern;
    $params[] = $brandPattern;
    $types .= "ss";
}

// Add condition filter
if (!empty($conditions) && is_array($conditions)) {
    $conditionPlaceholders = str_repeat('?,', count($conditions) - 1) . '?';
    $sql .= " AND condi IN ($conditionPlaceholders)";
    $params = array_merge($params, $conditions);
    $types .= str_repeat('s', count($conditions));
}

// Add sorting
switch($sortBy) {
    case 'newest':
        $sql .= " ORDER BY created_at DESC, id DESC";
        break;
    case 'price-low':
        $sql .= " ORDER BY price ASC, id ASC";
        break;
    case 'price-high':
        $sql .= " ORDER BY price DESC, id DESC";
        break;
    case 'popular':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

// Add pagination
$offset = ($page - 1) * $perPage;
$sql .= " LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM products WHERE approval_status='approved' AND price <= ?";
$countParams = [$maxPrice];
$countTypes = "d";

if ($category !== 'all') {
    $countSql .= " AND category=?";
    $countParams[] = $category;
    $countTypes .= "s";
}

if (!empty($brand)) {
    $countSql .= " AND (LOWER(title) LIKE ? OR LOWER(descript) LIKE ?)";
    $brandPattern = '%' . strtolower($brand) . '%';
    $countParams[] = $brandPattern;
    $countParams[] = $brandPattern;
    $countTypes .= "ss";
}

if (!empty($conditions) && is_array($conditions)) {
    $conditionPlaceholders = str_repeat('?,', count($conditions) - 1) . '?';
    $countSql .= " AND condi IN ($conditionPlaceholders)";
    $countParams = array_merge($countParams, $conditions);
    $countTypes .= str_repeat('s', count($conditions));
}

$countStmt = $conn->prepare($countSql);
$countStmt->bind_param($countTypes, ...$countParams);
$countStmt->execute();
$countRes = $countStmt->get_result();
$totalCount = $countRes->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $perPage);

echo json_encode([
    "products" => $products,
    "totalPages" => $totalPages,
    "totalCount" => $totalCount,
    "currentPage" => $page
]);
?>
