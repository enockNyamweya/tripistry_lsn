<?php
// Database.php - Singleton PDO Connection
require_once __DIR__ . '/../../config/env.php';

class Database {
    private static $instance = null;
    private $pdo;

    // Database configuration variables
    private $host;
    private $db;
    private $user; 
    private $pass;
    private $charset = 'utf8mb4';

    private function __construct() {
        $this->host = env('DB_HOST', 'localhost');
        $this->db   = env('DB_NAME', 'tripistry_lsn');
        $this->user = env('DB_USER', 'root');
        $this->pass = env('DB_PASS', '');

        $dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // True prepared statements
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (\PDOException $e) {
            // In a production app we would log this instead of throwing directly to user
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    // Prevent cloning and unserialization of the singleton
    private function __clone() {}
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
