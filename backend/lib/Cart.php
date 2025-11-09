<?php

require_once __DIR__ . '/PdoConnector.php';

class Cart
{
    private PDO $db;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
    }

    /**
     * Create a new cart with session and pwd
     * @param string $session
     * @param string $pwd
     * @return bool
     */
    public function create(string $session, string $pwd): bool
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO carts (session, pwd, cart, date_modified) VALUES (:session, :pwd, :cart, datetime("now"))');
            $stmt->bindValue(':session', $session, PDO::PARAM_STR);
            $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
            $stmt->bindValue(':cart', '', PDO::PARAM_STR);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Validate session and pwd
     * @param string $session
     * @param string $pwd
     * @return bool
     */
    public function validate(string $session, string $pwd): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM carts WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }

    /**
     * Get cart data by session and pwd
     * @param string $session
     * @param string $pwd
     * @return array|null
     */
    public function getCart(string $session, string $pwd): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM carts WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update phone number
     * @param string $session
     * @param string $pwd
     * @param int|string $phone
     * @return bool
     */
    public function updatePhone(string $session, string $pwd, $phone): bool
    {
        if (!$this->validate($session, $pwd)) {
            return false;
        }

        // Validate phone is numeric only
        if (!empty($phone) && !ctype_digit((string)$phone)) {
            return false;
        }

        // Convert to integer for storage
        $phoneInt = empty($phone) ? null : (int)$phone;

        $stmt = $this->db->prepare('UPDATE carts SET phone = :phone WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':phone', $phoneInt, $phoneInt === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Check if cart is submitted
     * @param string $session
     * @param string $pwd
     * @return bool
     */
    public function isSubmitted(string $session, string $pwd): bool
    {
        $stmt = $this->db->prepare('SELECT submitted FROM carts WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if (!$result) {
            return false;
        }
        
        return (int)$result['submitted'] === 1;
    }

    /**
     * Update cart data
     * @param string $session
     * @param string $pwd
     * @param string $cartData
     * @return bool
     */
    public function updateCart(string $session, string $pwd, string $cartData): bool
    {
        if (!$this->validate($session, $pwd)) {
            return false;
        }

        // Check if cart is already submitted - if so, reset it first
        if ($this->isSubmitted($session, $pwd)) {
            $this->resetSubmittedCart($session, $pwd);
        }

        $stmt = $this->db->prepare('UPDATE carts SET cart = :cart, date_modified = datetime("now") WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':cart', $cartData, PDO::PARAM_STR);
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Reset a submitted cart (allows new orders with same session)
     * @param string $session
     * @param string $pwd
     * @return bool
     */
    public function resetSubmittedCart(string $session, string $pwd): bool
    {
        if (!$this->validate($session, $pwd)) {
            return false;
        }

        $stmt = $this->db->prepare('UPDATE carts SET submitted = 0, cart = "", date_modified = datetime("now") WHERE session = :session AND pwd = :pwd');
        $stmt->bindValue(':session', $session, PDO::PARAM_STR);
        $stmt->bindValue(':pwd', $pwd, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    /**
     * Delete old carts (older than 30 days)
     * @return int Number of deleted records
     */
    public function cleanupOldCarts(): int
    {
        $stmt = $this->db->prepare('DELETE FROM carts WHERE date_modified < datetime("now", "-30 days")');
        $stmt->execute();
        return $stmt->rowCount();
    }
}
