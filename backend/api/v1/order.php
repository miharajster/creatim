<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/Order.php';
require_once __DIR__ . '/../../lib/Cart.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';
require_once __DIR__ . '/../../lib/Sms.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sendError(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function sendSuccess($data, string $message = null): never
{
    $response = ['success' => true, 'data' => $data];
    if ($message) {
        $response['message'] = $message;
    }
    echo json_encode($response);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// Whitelist for order response
$orderWhitelist = ['order_id', 'order_number', 'status', 'total_price', 'articles', 'subscriptions', 'phone'];

try {
    if ($method !== 'POST') {
        sendError(405, 'Method not allowed. Only POST requests are supported.');
    }
    
    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['session_id']) || !isset($input['pwd'])) {
        sendError(400, 'Missing session_id or pwd in request body');
    }
    
    $sessionId = $input['session_id'];
    $pwd = $input['pwd'];
    
    // Always get phone from cart (primary source)
    $cart = new Cart();
    $cartData = $cart->getCart($sessionId, $pwd);
    $customerPhone = null;
    
    if ($cartData && !empty($cartData['phone'])) {
        $customerPhone = $cartData['phone'];
    }
    
    // If cart has no phone, fall back to request body
    if (empty($customerPhone) && isset($input['customer_phone'])) {
        $customerPhone = $input['customer_phone'];
    }
    
    // Phone is required for order submission
    if (empty($customerPhone)) {
        sendError(400, 'Phone number is required to submit an order');
    }
    
    // Validate phone is numeric only
    if (!ctype_digit((string)$customerPhone)) {
        sendError(400, 'Phone number must contain only digits');
    }
    
    // Create order
    $order = new Order();
    $result = $order->submitOrder($sessionId, $pwd, $customerPhone);
    
    if ($result === null) {
        sendError(400, 'Failed to submit order. Invalid session or empty cart.');
    }
    
    // Store SMS notification for sending
    $sms = new Sms();
    $smsContent = 'Thank you for your order! Your order will be processed as soon as possible by our team. - Creatim';
    $sms->storeToSMS((int)$customerPhone, $smsContent);
    
    $result = Whitelist::apply($result, $orderWhitelist);
    sendSuccess($result, 'Order submitted successfully');
    
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
