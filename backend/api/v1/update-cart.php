<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/Cart.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
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
    $cart = new Cart();
    
    if ($method === 'GET') {
        // Read cart - validate session and pwd from query params
        if (!isset($_GET['session_id']) || !isset($_GET['pwd'])) {
            sendError(400, 'Missing session_id or pwd parameters');
        }
        
        $sessionId = $_GET['session_id'];
        $pwd = $_GET['pwd'];
        
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
        
    } elseif ($method === 'POST') {
        // Update cart - validate and update
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['session_id']) || !isset($input['pwd'])) {
            sendError(400, 'Missing session_id or pwd in request body');
        }
        
        if (!isset($input['cart'])) {
            sendError(400, 'Missing cart data in request body');
        }
        
        $sessionId = $input['session_id'];
        $pwd = $input['pwd'];
        $cartData = $input['cart'];
        $phone = $input['phone'] ?? null;
        
        // Check if cart is already submitted
        if ($cart->isSubmitted($sessionId, $pwd)) {
            sendError(403, 'Cannot update cart. This cart has already been submitted.');
        }
        
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
        sendError(405, 'Method not allowed. Only GET and POST requests are supported.');
    }
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
