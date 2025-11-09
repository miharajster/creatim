<?php

require_once __DIR__ . '/PdoConnector.php';

class ReadSubscriptions
{
    private PDO $db;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
    }

    /**
     * Get all subscriptions
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM subscription ORDER BY price ASC');
        return $stmt->fetchAll();
    }

    /**
     * Get a single subscription by ID
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get subscriptions by type (physical or digital)
     * @param bool $isPhysical
     * @return array
     */
    public function getByType(bool $isPhysical): array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription WHERE physical = :physical ORDER BY price ASC');
        $stmt->bindValue(':physical', $isPhysical ? 1 : 0, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get subscriptions by price range
     * @param int $minPrice
     * @param int $maxPrice
     * @return array
     */
    public function getByPriceRange(int $minPrice, int $maxPrice): array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription WHERE price BETWEEN :min AND :max ORDER BY price ASC');
        $stmt->bindValue(':min', $minPrice, PDO::PARAM_INT);
        $stmt->bindValue(':max', $maxPrice, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Search subscriptions by description
     * @param string $searchTerm
     * @return array
     */
    public function search(string $searchTerm): array
    {
        $stmt = $this->db->prepare('SELECT * FROM subscription WHERE description LIKE :search ORDER BY price ASC');
        $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get physical subscriptions only
     * @return array
     */
    public function getPhysical(): array
    {
        return $this->getByType(true);
    }

    /**
     * Get digital subscriptions only
     * @return array
     */
    public function getDigital(): array
    {
        return $this->getByType(false);
    }

    /**
     * Get the total count of subscriptions
     * @return int
     */
    public function getCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM subscription');
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Get the cheapest subscription
     * @return array|null
     */
    public function getCheapest(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM subscription ORDER BY price ASC LIMIT 1');
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get the most expensive subscription
     * @return array|null
     */
    public function getMostExpensive(): ?array
    {
        $stmt = $this->db->query('SELECT * FROM subscription ORDER BY price DESC LIMIT 1');
        $result = $stmt->fetch();
        return $result ?: null;
    }
}
