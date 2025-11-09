<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/Cart.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
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

// Whitelist for new session response
$sessionWhitelist = ['session_id', 'pwd'];

try {
    if ($method !== 'GET') {
        sendError(405, 'Method not allowed. Only GET requests are supported.');
    }
    
    // Generate two random MD5 hashes
    $sessionId = md5(random_bytes(32) . microtime(true));
    $pwd = md5(random_bytes(32) . microtime(true));
    
    // Create new cart in database
    $cart = new Cart();
    $created = $cart->create($sessionId, $pwd);
    
    if (!$created) {
        sendError(500, 'Failed to create cart session');
    }
    
    $response = [
        'session_id' => $sessionId,
        'pwd' => $pwd
    ];
    
    $response = Whitelist::apply($response, $sessionWhitelist);
    sendSuccess($response);
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
