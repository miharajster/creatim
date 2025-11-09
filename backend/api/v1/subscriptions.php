<?php

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/ReadSubscriptions.php';
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

// Whitelist for subscription columns
$subscriptionWhitelist = ['id', 'description', 'price', 'physical'];

try {
    switch ($method) {
        case 'GET':
            $readSubscriptions = new ReadSubscriptions();
            
            if (isset($_GET['id'])) {
                // Validate ID type
                if (!is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
                    sendError(400, 'Invalid ID format. Must be a positive integer');
                }
                
                $id = (int)$_GET['id'];
                $subscription = $readSubscriptions->getById($id);
                
                if ($subscription === null) {
                    sendError(404, 'Subscription not found');
                }
                
                $subscription = Whitelist::apply($subscription, $subscriptionWhitelist);
                sendSuccess($subscription);
            }
            
            // Get all subscriptions
            $subscriptions = $readSubscriptions->getAll();
            $subscriptions = Whitelist::apply($subscriptions, $subscriptionWhitelist);
            sendSuccess($subscriptions);
            
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate JSON
            if ($data === null) {
                sendError(400, 'Invalid JSON format');
            }
            
            // Validate required fields
            if (!isset($data['description']) || !is_string($data['description'])) {
                sendError(400, 'Field "description" is required and must be a string');
            }
            
            // Validate price if provided
            if (isset($data['price']) && !is_numeric($data['price'])) {
                sendError(400, 'Field "price" must be a number');
            }
            
            // Validate physical if provided
            if (isset($data['physical']) && !is_numeric($data['physical'])) {
                sendError(400, 'Field "physical" must be a number (0 or 1)');
            }
            
            // Ensure physical is 0 or 1
            $physical = isset($data['physical']) ? (int)$data['physical'] : 0;
            if ($physical !== 0 && $physical !== 1) {
                sendError(400, 'Field "physical" must be 0 or 1');
            }
            
            $db = PdoConnector::getInstance();
            $stmt = $db->prepare('INSERT INTO subscription (description, price, physical, date_created) VALUES (:description, :price, :physical, :date_created)');
            $stmt->execute([
                ':description' => $data['description'],
                ':price' => $data['price'] ?? 0,
                ':physical' => $physical,
                ':date_created' => date('Y-m-d')
            ]);
            
            sendSuccess(['id' => $db->lastInsertId()], 'Subscription created successfully');
            
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
            
            $allowedFields = ['description', 'price', 'physical'];
            $updates = [];
            $params = [':id' => (int)$data['id']];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    // Type validation
                    if ($field === 'description' && !is_string($data[$field])) {
                        sendError(400, "Field \"$field\" must be a string");
                    }
                    
                    if ($field === 'price' && !is_numeric($data[$field])) {
                        sendError(400, "Field \"$field\" must be a number");
                    }
                    
                    if ($field === 'physical') {
                        if (!is_numeric($data[$field])) {
                            sendError(400, "Field \"$field\" must be a number (0 or 1)");
                        }
                        
                        $physicalVal = (int)$data[$field];
                        if ($physicalVal !== 0 && $physicalVal !== 1) {
                            sendError(400, "Field \"$field\" must be 0 or 1");
                        }
                        
                        $data[$field] = $physicalVal;
                    }
                    
                    $updates[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($updates)) {
                sendError(400, 'No valid fields to update');
            }
            
            $db = PdoConnector::getInstance();
            $sql = 'UPDATE subscription SET ' . implode(', ', $updates) . ' WHERE id = :id';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() === 0) {
                sendError(404, 'Subscription not found or no changes made');
            }
            
            sendSuccess([], 'Subscription updated successfully');
            
        case 'DELETE':
            // Validate ID
            if (!isset($_GET['id']) || !is_numeric($_GET['id']) || (int)$_GET['id'] <= 0) {
                sendError(400, 'Query parameter "id" is required and must be a positive integer');
            }
            
            $id = (int)$_GET['id'];
            $db = PdoConnector::getInstance();
            $stmt = $db->prepare('DELETE FROM subscription WHERE id = :id');
            $stmt->execute([':id' => $id]);
            
            if ($stmt->rowCount() === 0) {
                sendError(404, 'Subscription not found');
            }
            
            sendSuccess([], 'Subscription deleted successfully');
            
        default:
            sendError(405, 'Method not allowed');
    }
} catch (Exception $e) {
    sendError(500, 'Internal server error: ' . $e->getMessage());
}
