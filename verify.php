<?php
session_start();
require('razorpay-php/Razorpay.php'); // Ensure Razorpay PHP SDK is included

use Razorpay\Api\Api;

$keyId = "rzp_test_RIZ6VfQgTUV4t6";
$keySecret = "GYCQW1U2WARvDxoTLQpdisZm";

$api = new Api($keyId, $keySecret);

// Connect DB
$conn = new mysqli("localhost", "root", "", "thriftin");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

if (!empty($_POST['razorpay_payment_id']) && !empty($_POST['razorpay_order_id'])) {
    try {
        // Verify Signature
        $attributes = [
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];

        $api->utility->verifyPaymentSignature($attributes);

        // ✅ Signature Valid → update DB
        $order_id = $_POST['razorpay_order_id'];
        $payment_id = $_POST['razorpay_payment_id'];

        $stmt = $conn->prepare("UPDATE orders SET status='paid', payment_id=? WHERE razorpay_order_id=?");
        $stmt->bind_param("ss", $payment_id, $order_id);
        $stmt->execute();

        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
          <meta charset='UTF-8'>
          <title>Payment Success</title>
          <style>
            body { background:#0a0a0a; color:#fff; font-family:Arial, sans-serif; text-align:center; padding:50px; }
            .box { background:#1a1a2e; padding:40px; border-radius:20px; display:inline-block; }
            h1 { color:#4ade80; }
            p { color:#ccc; }
            a { display:inline-block; margin-top:20px; padding:12px 24px; background:#667eea; color:white; text-decoration:none; border-radius:10px; }
          </style>
        </head>
        <body>
          <div class='box'>
            <h1>🎉 Payment Successful!</h1>
            <p>Your payment ID: <strong>{$payment_id}</strong></p>
            <p>Thank you for shopping with thriftIN!</p>
            <a href='products_page.html'>Continue Shopping</a>
          </div>
        </body>
        </html>";
    } catch (\Exception $e) {
        // ❌ Invalid Signature
        echo "<h2 style='color:red;'>Payment Verification Failed</h2>";
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<h2 style='color:red;'>Invalid Access</h2>";
}