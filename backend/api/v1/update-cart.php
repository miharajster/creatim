<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/Cart.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';

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

// Whitelist for cart response
$cartWhitelist = ['cart', 'date_modified', 'submitted', 'phone', 'message'];

try {
    if ($method !== 'POST') {
        sendError(405, 'Method not allowed. Only POST requests are supported.');
    }
    
    $cart = new Cart();
    
    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['session_id']) || !isset($input['pwd'])) {
        sendError(400, 'Missing session_id or pwd in request body');
    }
    
    $sessionId = $input['session_id'];
    $pwd = $input['pwd'];
    
    // Determine operation: if 'cart' is present, it's an update; otherwise, it's a read
    if (isset($input['cart'])) {
        // Update cart operation
        $cartData = $input['cart'];
        $phone = $input['phone'] ?? null;
        
        // Note: Cart auto-resets if submitted (handled in Cart::updateCart())
        
        // Validate phone if provided
        if ($phone !== null && !empty($phone) && !ctype_digit($phone)) {
            sendError(400, 'Phone number must contain only digits');
        }
        
        // Convert cart array to JSON string if needed
        if (is_array($cartData)) {
            $cartData = json_encode($cartData);
        }
        
        $updated = $cart->updateCart($sessionId, $pwd, $cartData);
        
        if (!$updated) {
            sendError(401, 'Invalid session credentials or update failed');
        }
        
        // Update phone if provided
        if ($phone !== null) {
            $cart->updatePhone($sessionId, $pwd, $phone);
        }
        
        $response = [
            'cart' => $cartData,
            'phone' => $phone ? (int)$phone : null,
            'message' => 'Cart updated successfully'
        ];
        
        $response = Whitelist::apply($response, $cartWhitelist);
        sendSuccess($response);
        
    } else {
        // Read cart operation
        $cartData = $cart->getCart($sessionId, $pwd);
        
        if ($cartData === null) {
            sendError(401, 'Invalid session credentials');
        }
        
        $response = [
            'cart' => $cartData['cart'],
            'date_modified' => $cartData['date_modified'],
            'submitted' => (int)$cartData['submitted'],
            'phone' => $cartData['phone'] ? (int)$cartData['phone'] : null
        ];
        
        $response = Whitelist::apply($response, $cartWhitelist);
        sendSuccess($response);
    }
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
