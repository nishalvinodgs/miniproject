<?php
// create_order.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // CORS preflight
    http_response_code(200);
    exit;
}

// --- CONFIG: move these to env/config in production ---
$host = 'localhost';
$dbname = 'thriftin';
$username = 'root';
$password = '';
$razorpay_key_id = 'rzp_test_RIZ6VfQgTUV4t6';
$razorpay_secret = 'GYCQW1U2WARvDxoTLQpdisZm'; // consider moving to env
// -------------------------------------------------------

try {
    // Connect DB
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Read input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['cart_items']) || !isset($input['total_amount'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    $cartItems = $input['cart_items'];
    $totalAmount = (float)$input['total_amount'];

    if (!is_array($cartItems) || count($cartItems) === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    // Find or create customer (if email provided)
    $customerId = null;
    if (!empty($input['customer_email'])) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$input['customer_email']]);
        $customerId = $stmt->fetchColumn();

        if (!$customerId) {
            $stmt = $pdo->prepare("INSERT INTO customers (email, first_name, phone, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([
                $input['customer_email'],
                $input['address_full_name'] ?? null,
                $input['customer_phone'] ?? null
            ]);
            $customerId = $pdo->lastInsertId();
        }
    }

    // Create Razorpay order (amount in paisa)
    $amountPaise = (int) round($totalAmount * 100);
    $razorpayOrderData = [
        'amount' => $amountPaise,
        'currency' => 'INR',
        'receipt' => 'receipt_' . time(),
        'payment_capture' => 1
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($razorpayOrderData));
    curl_setopt($ch, CURLOPT_USERPWD, $razorpay_key_id . ':' . $razorpay_secret);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!in_array($http_status, [200, 201])) {
        throw new Exception('Failed to create Razorpay order: HTTP ' . $http_status . ' - ' . $response);
    }

    $razorpayOrder = json_decode($response, true);
    if (!$razorpayOrder || empty($razorpayOrder['id'])) {
        throw new Exception('Invalid response from Razorpay: ' . $response);
    }

    // Insert order into DB (matching columns from your SQL dump: address_* names)
    $insertOrderSql = "
        INSERT INTO orders (
            customer_id, total_amount, payment_status, order_status,
            address_full_name, address_line1, address_line2,
            address_city, address_state, address_pincode, address_phone,
            razorpay_order_id, created_at, updated_at
        ) VALUES (
            :customer_id, :total_amount, 'pending', 'created',
            :address_full_name, :address_line1, :address_line2,
            :address_city, :address_state, :address_pincode, :address_phone,
            :razorpay_order_id, NOW(), NOW()
        )
    ";
    $stmt = $pdo->prepare($insertOrderSql);
    $stmt->execute([
        ':customer_id' => $customerId ?: null,
        ':total_amount' => number_format($totalAmount, 2, '.', ''),
        ':address_full_name' => $input['address_full_name'] ?? null,
        ':address_line1' => $input['address_line1'] ?? null,
        ':address_line2' => $input['address_line2'] ?? null,
        ':address_city' => $input['address_city'] ?? null,
        ':address_state' => $input['address_state'] ?? null,
        ':address_pincode' => $input['address_pincode'] ?? null,
        ':address_phone' => $input['address_phone'] ?? null,
        ':razorpay_order_id' => $razorpayOrder['id']
    ]);

    $orderId = $pdo->lastInsertId();

    // Insert order items - match order_items table: (order_id, product_id, product_name, image, condition_text, price, quantity)
    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, image, condition_text, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cartItems as $item) {
        // defensive defaults if keys missing
        $productId = isset($item['id']) ? $item['id'] : null;
        $productName = $item['title'] ?? $item['name'] ?? null;
        $image = $item['image'] ?? null;
        $conditionText = $item['condition'] ?? $item['condition_text'] ?? null;
        $price = isset($item['price']) ? (float)$item['price'] : 0.00;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;

        $stmtItem->execute([
            $orderId,
            $productId,
            $productName,
            $image,
            $conditionText,
            number_format($price, 2, '.', ''),
            $quantity
        ]);
    }

    // commit
    $pdo->commit();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'order_id' => (int)$orderId,
            'razorpay_order_id' => $razorpayOrder['id']
        ]
    ]);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order creation error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}