<?php

require_once __DIR__ . '/PdoConnector.php';

class ReadArticles
{
    private PDO $db;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
    }

    /**
     * Get all articles
     * @return array
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT * FROM articles ORDER BY date_created DESC');
        return $stmt->fetchAll();
    }

    /**
     * Get a single article by ID
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM articles WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get articles by price range
     * @param int $minPrice
     * @param int $maxPrice
     * @return array
     */
    public function getByPriceRange(int $minPrice, int $maxPrice): array
    {
        $stmt = $this->db->prepare('SELECT * FROM articles WHERE price BETWEEN :min AND :max ORDER BY price ASC');
        $stmt->bindValue(':min', $minPrice, PDO::PARAM_INT);
        $stmt->bindValue(':max', $maxPrice, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Search articles by name or description
     * @param string $searchTerm
     * @return array
     */
    public function search(string $searchTerm): array
    {
        $stmt = $this->db->prepare('SELECT * FROM articles WHERE name LIKE :search OR description LIKE :search ORDER BY date_created DESC');
        $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get articles by supplier email
     * @param string $email
     * @return array
     */
    public function getBySupplier(string $email): array
    {
        $stmt = $this->db->prepare('SELECT * FROM articles WHERE supplier_email = :email ORDER BY date_created DESC');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get the total count of articles
     * @return int
     */
    public function getCount(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM articles');
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Get recent articles
     * @param int $limit
     * @return array
     */
    public function getRecent(int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT * FROM articles ORDER BY date_created DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
