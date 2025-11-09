<?php

require_once __DIR__ . '/PdoConnector.php';
require_once __DIR__ . '/Cart.php';
require_once __DIR__ . '/ReadArticles.php';
require_once __DIR__ . '/ReadSubscriptions.php';

class Order
{
    private PDO $db;
    private Cart $cart;
    private ReadArticles $articles;
    private ReadSubscriptions $subscriptions;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
        $this->cart = new Cart();
        $this->articles = new ReadArticles();
        $this->subscriptions = new ReadSubscriptions();
    }

    /**
     * Submit an order
     * @param string $sessionId
     * @param string $pwd
     * @param string|null $customerPhone
     * @return array|null Returns order details on success, null on failure
     */
    public function submitOrder(string $sessionId, string $pwd, ?string $customerPhone = null): ?array
    {
        // Validate session
        if (!$this->cart->validate($sessionId, $pwd)) {
            return null;
        }

        // Get cart data
        $cartData = $this->cart->getCart($sessionId, $pwd);
        if (!$cartData || empty($cartData['cart'])) {
            return null;
        }

        // Parse cart items
        $cartItems = json_decode($cartData['cart'], true);
        if (empty($cartItems)) {
            return null;
        }

        // Calculate total price and separate articles/subscriptions
        $totalPrice = 0;
        $articlesList = [];
        $subscriptionsList = [];

        foreach ($cartItems as $item) {
            $itemId = $item['id'];
            $amount = $item['amount'];

            // Try to find in articles
            $article = $this->articles->getById($itemId);
            if ($article) {
                $articlesList[] = [
                    'id' => $itemId,
                    'amount' => $amount,
                    'name' => $article['name'],
                    'price' => $article['price']
                ];
                $totalPrice += $article['price'] * $amount;
                continue;
            }

            // Try to find in subscriptions
            $subscription = $this->subscriptions->getById($itemId);
            if ($subscription) {
                $subscriptionsList[] = [
                    'id' => $itemId,
                    'amount' => $amount,
                    'description' => $subscription['description'],
                    'price' => $subscription['price']
                ];
                $totalPrice += $subscription['price'] * $amount;
            }
        }

        // Begin transaction
        $this->db->beginTransaction();

        try {
            // Mark cart as submitted
            $stmt = $this->db->prepare('UPDATE carts SET submitted = 1, date_modified = datetime("now") WHERE session = :session AND pwd = :pwd');
            $stmt->bindValue(':session', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
            $stmt->execute();

            // Generate order number
            $orderNumber = time() . rand(1000, 9999);

            // Create order
            $stmt = $this->db->prepare('INSERT INTO orders (order_number, customer_phone, status, price, articles, subscription_pkg, session_id, date_created) VALUES (:order_number, :customer_phone, :status, :price, :articles, :subscription_pkg, :session_id, datetime("now"))');
            $stmt->bindValue(':order_number', $orderNumber, PDO::PARAM_INT);
            $stmt->bindValue(':customer_phone', $customerPhone ?? '', PDO::PARAM_STR);
            $stmt->bindValue(':status', 'NEEDS TO BE PROCESSED', PDO::PARAM_STR);
            $stmt->bindValue(':price', $totalPrice, PDO::PARAM_INT);
            $stmt->bindValue(':articles', json_encode($articlesList), PDO::PARAM_STR);
            $stmt->bindValue(':subscription_pkg', json_encode($subscriptionsList), PDO::PARAM_STR);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->execute();

            $orderId = $this->db->lastInsertId();

            // Commit transaction
            $this->db->commit();

            return [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'status' => 'NEEDS TO BE PROCESSED',
                'total_price' => $totalPrice,
                'articles' => $articlesList,
                'subscriptions' => $subscriptionsList,
                'phone' => $customerPhone
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    /**
     * Get order by ID
     * @param int $orderId
     * @return array|null
     */
    public function getOrderById(int $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->bindValue(':id', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get orders by session ID
     * @param string $sessionId
     * @return array
     */
    public function getOrdersBySession(string $sessionId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE session_id = :session_id ORDER BY date_created DESC');
        $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all orders
     * @return array
     */
    public function getAllOrders(): array
    {
        $stmt = $this->db->query('SELECT * FROM orders ORDER BY date_created DESC');
        return $stmt->fetchAll();
    }

    /**
     * Get purchases (processed orders) for a session
     * @param string $sessionId
     * @param string $pwd
     * @param string|null $phone
     * @return array|null Returns formatted purchases or null if no match
     */
    public function getPurchases(string $sessionId, string $pwd, ?string $phone = null): ?array
    {
        // Validate session
        if (!$this->cart->validate($sessionId, $pwd)) {
            return null;
        }

        // Build query - check for PROCESSED orders
        $sql = 'SELECT * FROM orders WHERE session_id = :session_id AND status = :status';
        $params = [
            ':session_id' => $sessionId,
            ':status' => 'PROCESSED'
        ];

        // Add phone filter if provided
        if ($phone !== null) {
            $sql .= ' AND customer_phone = :phone';
            $params[':phone'] = $phone;
        }

        $sql .= ' ORDER BY date_created DESC';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        $orders = $stmt->fetchAll();

        if (empty($orders)) {
            return [
                'articles' => [],
                'subscriptions' => []
            ];
        }

        // Aggregate all articles from all processed orders
        $articlesMap = [];

        foreach ($orders as $order) {
            // Parse articles
            if (!empty($order['articles'])) {
                $orderArticles = json_decode($order['articles'], true);
                if (is_array($orderArticles)) {
                    foreach ($orderArticles as $article) {
                        $id = $article['id'];
                        if (!isset($articlesMap[$id])) {
                            $articlesMap[$id] = ['id' => $id, 'amount' => 0];
                        }
                        $articlesMap[$id]['amount'] += $article['amount'];
                    }
                }
            }
        }

        // Get subscription from the latest order only (first in array, ordered by date DESC)
        $subscriptions = [];
        $latestOrder = $orders[0];
        if (!empty($latestOrder['subscription_pkg'])) {
            $orderSubscriptions = json_decode($latestOrder['subscription_pkg'], true);
            if (is_array($orderSubscriptions)) {
                foreach ($orderSubscriptions as $subscription) {
                    $subscriptions[] = ['id' => $subscription['id']];
                }
            }
        }

        return [
            'articles' => array_values($articlesMap),
            'subscriptions' => $subscriptions
        ];
    }
}
