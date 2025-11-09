<?php

require_once __DIR__ . '/PdoConnector.php';

class Sms
{
    private PDO $db;

    public function __construct()
    {
        $this->db = PdoConnector::getInstance();
    }

    /**
     * Store SMS to log for future sending
     * @param int $customerPhone Phone number (numeric only)
     * @param string $content SMS message content
     * @return bool True if stored successfully, false otherwise
     */
    public function storeToSMS(int $customerPhone, string $content): bool
    {
        try {
            $stmt = $this->db->prepare('INSERT INTO sms_log (customer_phone, content) VALUES (:customer_phone, :content)');
            $stmt->bindValue(':customer_phone', $customerPhone, PDO::PARAM_INT);
            $stmt->bindValue(':content', $content, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log('Failed to store SMS: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all SMS logs
     * @return array
     */
    public function getAllSMS(): array
    {
        $stmt = $this->db->query('SELECT * FROM sms_log ORDER BY created DESC');
        return $stmt->fetchAll();
    }

    /**
     * Get unsent SMS logs (sent is NULL)
     * @return array
     */
    public function getUnsentSMS(): array
    {
        $stmt = $this->db->query('SELECT * FROM sms_log WHERE sent IS NULL ORDER BY created ASC');
        return $stmt->fetchAll();
    }

    /**
     * Mark SMS as sent
     * @param int $id SMS log ID
     * @return bool
     */
    public function markAsSent(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE sms_log SET sent = datetime("now") WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
