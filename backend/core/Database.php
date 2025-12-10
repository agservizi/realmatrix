<?php

require_once __DIR__ . '/Env.php';

class Database
{
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct()
    {
        Env::load();
        $host = Env::get('DB_HOST', 'localhost');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');
        $db   = Env::get('DB_NAME', 'realmatrix');
        $this->connection = new mysqli($host, $user, $pass, $db);
        if ($this->connection->connect_errno) {
            throw new RuntimeException('Database connection failed: ' . $this->connection->connect_error);
        }
        $this->connection->set_charset('utf8mb4');
    }

    public static function getInstance(): Database
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli
    {
        return $this->connection;
    }
}
