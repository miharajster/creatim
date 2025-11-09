<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/ReadArticles.php';
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

// Whitelist for article columns
$articleWhitelist = ['id', 'name', 'description', 'price'];

try {
    if ($method !== 'GET') {
        sendError(405, 'Method not allowed. Only GET requests are supported.');
    }
    
    $readArticles = new ReadArticles();
    
    if (isset($_GET['id'])) {
        // Validate ID type
        if (!is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
            sendError(400, 'Invalid ID format. Must be a positive integer');
        }
        
        $id = (int)$_GET['id'];
        $article = $readArticles->getById($id);
        
        if ($article === null) {
            sendError(404, 'Article not found');
        }
        
        $article = Whitelist::apply($article, $articleWhitelist);
        sendSuccess($article);
    }
    
    // Get all articles
    $articles = $readArticles->getAll();
    $articles = Whitelist::apply($articles, $articleWhitelist);
    sendSuccess($articles);
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
