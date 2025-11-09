<?php

class PdoConnector
{
    private static ?PDO $instance = null;
    private string $dbPath;

    private function __construct()
    {
        $this->dbPath = __DIR__ . '/../../database.db';
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $connector = new self();
            try {
                self::$instance = new PDO('sqlite:' . $connector->dbPath);
                self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new Exception("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    private function __clone() {}
    
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
