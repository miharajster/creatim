<?php

require_once __DIR__ . '/../Admin.php';

class Dashboard
{
    private Admin $admin;
    private string $message = '';
    private string $error = '';
    private array $articles = [];
    private array $subscriptions = [];
    private array $orders = [];

    public function __construct()
    {
        session_start();
        $this->admin = new Admin();
        
        // Load messages from session
        if (isset($_SESSION['message'])) {
            $this->message = $_SESSION['message'];
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            $this->error = $_SESSION['error'];
            unset($_SESSION['error']);
        }
        
        $this->handleRequest();
        $this->loadData();
    }

    private function validateArticle(array $data): void
    {
        if (empty($data['name']) || !is_string($data['name'])) {
            throw new Exception('Name must be a non-empty text');
        }
        if (empty($data['description']) || !is_string($data['description'])) {
            throw new Exception('Description must be a non-empty text');
        }
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            throw new Exception('Price must be a positive number');
        }
        if (empty($data['supplier_email']) || !filter_var($data['supplier_email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Supplier email must be a valid email address');
        }
    }

    private function validateSubscription(array $data): void
    {
        if (empty($data['description']) || !is_string($data['description'])) {
            throw new Exception('Description must be a non-empty text');
        }
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            throw new Exception('Price must be a positive number');
        }
        if (!isset($data['physical']) || !in_array((int)$data['physical'], [0, 1], true)) {
            throw new Exception('Type must be 0 (Digital) or 1 (Physical)');
        }
    }

    private function validateOrder(array $data): void
    {
        if (!isset($data['order_number']) || !is_numeric($data['order_number']) || $data['order_number'] <= 0) {
            throw new Exception('Order number must be a positive integer');
        }
        if (!empty($data['customer_phone']) && !is_numeric($data['customer_phone'])) {
            throw new Exception('Customer phone must be a number');
        }
        if (empty($data['status']) || !is_string($data['status'])) {
            throw new Exception('Status must be a non-empty text');
        }
        if (!isset($data['price']) || !is_numeric($data['price']) || $data['price'] < 0) {
            throw new Exception('Price must be a positive number');
        }
        // Articles and subscription_pkg are optional text fields
        if (isset($data['articles']) && !empty($data['articles']) && !is_string($data['articles'])) {
            throw new Exception('Articles must be text');
        }
        if (isset($data['subscription_pkg']) && !empty($data['subscription_pkg']) && !is_string($data['subscription_pkg'])) {
            throw new Exception('Subscription package must be text');
        }
    }

    private function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'add_article':
                    $this->validateArticle($_POST);
                    $this->admin->addArticle(
                        $_POST['name'],
                        $_POST['description'],
                        (int)$_POST['price'],
                        $_POST['supplier_email']
                    );
                    $this->redirectWithMessage('Article added successfully!');
                    break;

                case 'edit_article':
                    $this->validateArticle($_POST);
                    $this->admin->updateArticle(
                        (int)$_POST['id'],
                        $_POST['name'],
                        $_POST['description'],
                        (int)$_POST['price'],
                        $_POST['supplier_email']
                    );
                    $this->redirectWithMessage('Article updated successfully!');
                    break;

                case 'delete_article':
                    $this->admin->deleteArticle((int)$_POST['id']);
                    $this->redirectWithMessage('Article deleted successfully!');
                    break;

                case 'add_subscription':
                    $this->validateSubscription($_POST);
                    $this->admin->addSubscription(
                        $_POST['description'],
                        (int)$_POST['price'],
                        (int)$_POST['physical']
                    );
                    $this->redirectWithMessage('Subscription added successfully!');
                    break;

                case 'edit_subscription':
                    $this->validateSubscription($_POST);
                    $this->admin->updateSubscription(
                        (int)$_POST['id'],
                        $_POST['description'],
                        (int)$_POST['price'],
                        (int)$_POST['physical']
                    );
                    $this->redirectWithMessage('Subscription updated successfully!');
                    break;

                case 'delete_subscription':
                    $this->admin->deleteSubscription((int)$_POST['id']);
                    $this->redirectWithMessage('Subscription deleted successfully!');
                    break;

                case 'add_order':
                    $this->validateOrder($_POST);
                    $this->admin->addOrder(
                        (int)$_POST['order_number'],
                        $_POST['customer_phone'],
                        $_POST['status'],
                        (int)$_POST['price'],
                        $_POST['articles'] ?: null,
                        $_POST['subscription_pkg'] ?: null
                    );
                    $this->redirectWithMessage('Order added successfully!');
                    break;

                case 'edit_order':
                    $this->validateOrder($_POST);
                    $this->admin->updateOrder(
                        (int)$_POST['id'],
                        (int)$_POST['order_number'],
                        $_POST['customer_phone'],
                        $_POST['status'],
                        (int)$_POST['price'],
                        $_POST['articles'] ?: null,
                        $_POST['subscription_pkg'] ?: null
                    );
                    $this->redirectWithMessage('Order updated successfully!');
                    break;

                case 'delete_order':
                    $this->admin->deleteOrder((int)$_POST['id']);
                    $this->redirectWithMessage('Order deleted successfully!');
                    break;
            }
        } catch (Exception $e) {
            $this->redirectWithMessage('Error: ' . $e->getMessage(), true);
        }
    }

    private function redirectWithMessage(string $message, bool $isError = false): void
    {
        if ($isError) {
            $_SESSION['error'] = $message;
        } else {
            $_SESSION['message'] = $message;
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    private function loadData(): void
    {
        $this->articles = $this->admin->getAllArticles();
        $this->subscriptions = $this->admin->getAllSubscriptions();
        $this->orders = $this->admin->getAllOrders();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function getOrders(): array
    {
        return $this->orders;
    }
}
