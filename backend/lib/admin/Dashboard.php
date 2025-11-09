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

    private function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $action = $_POST['action'] ?? '';

        try {
            switch ($action) {
                case 'add_article':
                    $this->admin->addArticle(
                        $_POST['name'],
                        $_POST['description'],
                        (int)$_POST['price'],
                        $_POST['supplier_email']
                    );
                    $this->redirectWithMessage('Article added successfully!');
                    break;

                case 'edit_article':
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
                    $this->admin->addSubscription(
                        $_POST['description'],
                        (int)$_POST['price'],
                        (int)$_POST['physical']
                    );
                    $this->redirectWithMessage('Subscription added successfully!');
                    break;

                case 'edit_subscription':
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
