<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/Order.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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

// Whitelist for purchases response
$purchasesWhitelist = ['articles', 'subscriptions'];

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    if ($method !== 'POST') {
        sendError(405, 'Method not allowed. Only POST requests are supported.');
    }
    
    // Parse input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required parameters
    if (!isset($input['session_id']) || !isset($input['pwd'])) {
        sendError(400, 'Missing session_id or pwd in request body');
    }
    
    $sessionId = $input['session_id'];
    $pwd = $input['pwd'];
    $phone = $input['phone'] ?? null;
    
    // Get purchases
    $order = new Order();
    $purchases = $order->getPurchases($sessionId, $pwd, $phone);
    
    if ($purchases === null) {
        sendError(401, 'Invalid session credentials');
    }
    
    $purchases = Whitelist::apply($purchases, $purchasesWhitelist);
    sendSuccess($purchases);
    
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
