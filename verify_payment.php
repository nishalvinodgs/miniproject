<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$host = 'localhost';
$dbname = 'thriftin';
$username = 'root'; // Update with your DB username
$password = '';     // Update with your DB password

// Razorpay configuration
$razorpay_key_id = 'rzp_test_RIZ6VfQgTUV4t6';
$razorpay_secret = 'GYCQW1U2WARvDxoTLQpdisZm';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    // Validate required fields
    $required_fields = ['razorpay_order_id', 'razorpay_payment_id', 'razorpay_signature', 'order_id'];
    
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Verify Razorpay signature
    $generated_signature = hash_hmac('sha256', 
        $input['razorpay_order_id'] . '|' . $input['razorpay_payment_id'], 
        $razorpay_secret
    );
    
    if (!hash_equals($generated_signature, $input['razorpay_signature'])) {
        throw new Exception('Invalid payment signature');
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update order with payment details
    $stmt = $pdo->prepare("
        UPDATE orders 
        SET payment_id = ?, 
            razorpay_signature = ?, 
            payment_status = 'paid',
            order_status = 'paid',
            updated_at = NOW()
        WHERE order_id = ? AND razorpay_order_id = ?
    ");
    
    $result = $stmt->execute([
        $input['razorpay_payment_id'],
        $input['razorpay_signature'],
        $input['order_id'],
        $input['razorpay_order_id']
    ]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('Order not found or already processed');
    }
    
    // Insert payment record
    $stmt = $pdo->prepare("
        INSERT INTO payments (
            order_id, razorpay_payment_id, razorpay_order_id, 
            razorpay_signature, amount, currency, status, 
            payment_date, created_at
        ) 
        SELECT order_id, ?, razorpay_order_id, ?, total_amount, 'INR', 'captured', NOW(), NOW()
        FROM orders 
        WHERE order_id = ?
    ");
    
    $stmt->execute([
        $input['razorpay_payment_id'],
        $input['razorpay_signature'],
        $input['order_id']
    ]);
    
    // Get order details for response
    $stmt = $pdo->prepare("
        SELECT o.*, c.email as customer_email 
        FROM orders o 
        LEFT JOIN customers c ON o.customer_id = c.id 
        WHERE o.order_id = ?
    ");
    $stmt->execute([$input['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $pdo->commit();
    
    // Send confirmation email (optional - implement if needed)
    // sendOrderConfirmationEmail($order);
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment verified successfully',
        'data' => [
            'order_id' => $input['order_id'],
            'payment_id' => $input['razorpay_payment_id'],
            'status' => 'paid',
            'order_status' => 'paid'
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log error (in production, use proper logging)
    error_log('Payment verification error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Optional: Function to send order confirmation email
function sendOrderConfirmationEmail($order) {
    // Implement email sending logic here
    // You can use PHPMailer or similar library
    
    $to = $order['customer_email'];
    $subject = "Order Confirmation - thriftIN Order #{$order['order_id']}";
    $message = "
        <html>
        <head><title>Order Confirmation</title></head>
        <body>
            <h2>Thank you for your order!</h2>
            <p>Your order #{$order['order_id']} has been confirmed and payment received.</p>
            <p>Order Total: ₹{$order['total_amount']}</p>
            <p>We will process your order and send you tracking information soon.</p>
            <br>
            <p>Thanks for shopping with thriftIN!</p>
        </body>
        </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: orders@thriftin.com" . "\r\n";
    
    // Uncomment to send email
    // mail($to, $subject, $message, $headers);
}
?>