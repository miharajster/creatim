<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/ReadArticles.php';
require_once __DIR__ . '/../../lib/PdoConnector.php';
require_once __DIR__ . '/../../lib/Whitelist.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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
$articleWhitelist = ['id', 'name', 'description', 'price', 'date_created'];

try {
    switch ($method) {
        case 'GET':
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
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate JSON
            if ($data === null) {
                sendError(400, 'Invalid JSON format');
            }
            
            // Validate required fields
            if (!isset($data['name']) || !is_string($data['name'])) {
                sendError(400, 'Field "name" is required and must be a string');
            }
            
            if (!isset($data['description']) || !is_string($data['description'])) {
                sendError(400, 'Field "description" is required and must be a string');
            }
            
            if (!isset($data['supplier_email']) || !is_string($data['supplier_email'])) {
                sendError(400, 'Field "supplier_email" is required and must be a string');
            }
            
            // Validate email format
            if (!filter_var($data['supplier_email'], FILTER_VALIDATE_EMAIL)) {
                sendError(400, 'Field "supplier_email" must be a valid email address');
            }
            
            // Validate price if provided
            if (isset($data['price']) && !is_numeric($data['price'])) {
                sendError(400, 'Field "price" must be a number');
            }
            
            $db = PdoConnector::getInstance();
            $stmt = $db->prepare('INSERT INTO articles (name, description, price, supplier_email, date_created) VALUES (:name, :description, :price, :supplier_email, :date_created)');
            $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':price' => $data['price'] ?? 0,
                ':supplier_email' => $data['supplier_email'],
                ':date_created' => date('Y-m-d')
            ]);
            
            sendSuccess(['id' => $db->lastInsertId()], 'Article created successfully');
            
        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate JSON
            if ($data === null) {
                sendError(400, 'Invalid JSON format');
            }
            
            // Validate ID
            if (!isset($data['id']) || !is_numeric($data['id']) || (int)$data['id'] <= 0) {
                sendError(400, 'Field "id" is required and must be a positive integer');
            }
            
            $allowedFields = ['name', 'description', 'price', 'supplier_email'];
            $updates = [];
            $params = [':id' => (int)$data['id']];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Type validation
                    if ($field === 'price' && !is_numeric($data[$field])) {
                        sendError(400, "Field \"$field\" must be a number");
                    }
                    
                    if (in_array($field, ['name', 'description', 'supplier_email']) && !is_string($data[$field])) {
                        sendError(400, "Field \"$field\" must be a string");
                    }
                    
                    if ($field === 'supplier_email' && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        sendError(400, 'Field "supplier_email" must be a valid email address');
                    }
                    
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                sendError(400, 'No valid fields to update');
            }
            
            $db = PdoConnector::getInstance();
            $sql = 'UPDATE articles SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                sendError(404, 'Article not found or no changes made');
            }
            
            sendSuccess([], 'Article updated successfully');
            
        case 'DELETE':
            // Validate ID
            if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
                sendError(400, 'Query parameter "id" is required and must be a positive integer');
            }
            
            $id = (int)$_GET['id'];
            $db = PdoConnector::getInstance();
            $stmt = $db->prepare('DELETE FROM articles WHERE id = :id');
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                sendError(404, 'Article not found');
            }
            
            sendSuccess([], 'Article deleted successfully');
            
        default:
            sendError(405, 'Method not allowed');
    }
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
