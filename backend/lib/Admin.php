<?php

require_once __DIR__ . '/PdoConnector.php';

class Admin
{
    private PDO $db;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
    }

    // Articles Methods
    public function addArticle(string $name, string $description, int $price, string $supplierEmail): bool
    {
        $stmt = $this->db->prepare('INSERT INTO articles (name, description, price, supplier_email, date_created) VALUES (?, ?, ?, ?, ?)');
        return $stmt->execute([$name, $description, $price, $supplierEmail, date('Y-m-d')]);
    }

    public function updateArticle(int $id, string $name, string $description, int $price, string $supplierEmail): bool
    {
        $stmt = $this->db->prepare('UPDATE articles SET name = ?, description = ?, price = ?, supplier_email = ? WHERE id = ?');
        return $stmt->execute([$name, $description, $price, $supplierEmail, $id]);
    }

    public function deleteArticle(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM articles WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getAllArticles(): array
    {
        return $this->db->query('SELECT * FROM articles ORDER BY date_created DESC')->fetchAll();
    }

    // Subscriptions Methods
    public function addSubscription(string $description, int $price, int $physical): bool
    {
        $stmt = $this->db->prepare('INSERT INTO subscription (description, price, physical, date_created) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$description, $price, $physical, date('Y-m-d')]);
    }

    public function updateSubscription(int $id, string $description, int $price, int $physical): bool
    {
        $stmt = $this->db->prepare('UPDATE subscription SET description = ?, price = ?, physical = ? WHERE id = ?');
        return $stmt->execute([$description, $price, $physical, $id]);
    }

    public function deleteSubscription(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM subscription WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getAllSubscriptions(): array
    {
        return $this->db->query('SELECT * FROM subscription ORDER BY price ASC')->fetchAll();
    }

    // Orders Methods
    public function addOrder(int $orderNumber, string $customerPhone, string $status, int $price, ?string $articles, ?string $subscriptionPkg): bool
    {
        $stmt = $this->db->prepare('INSERT INTO orders (order_number, customer_phone, status, price, articles, subscription_pkg, date_created) VALUES (?, ?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$orderNumber, $customerPhone, $status, $price, $articles, $subscriptionPkg, date('Y-m-d')]);
    }

    public function updateOrder(int $id, int $orderNumber, string $customerPhone, string $status, int $price, ?string $articles, ?string $subscriptionPkg): bool
    {
        $stmt = $this->db->prepare('UPDATE orders SET order_number = ?, customer_phone = ?, status = ?, price = ?, articles = ?, subscription_pkg = ? WHERE id = ?');
        return $stmt->execute([$orderNumber, $customerPhone, $status, $price, $articles, $subscriptionPkg, $id]);
    }

    public function deleteOrder(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM orders WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getAllOrders(): array
    {
        return $this->db->query('SELECT * FROM orders ORDER BY date_created DESC')->fetchAll();
    }
}
