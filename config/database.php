<?php
class Database {
    private $host = "localhost";
    private $username = "root"; // Ganti dengan username database Anda
    private $password = ""; // Ganti dengan password database Anda
    private $database = "syariah";
    private $connection;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $this->connection = new mysqli($this->host, $this->username, $this->password, $this->database);

        if ($this->connection->connect_error) {
            die("Koneksi database gagal: " . $this->connection->connect_error);
        }

        // Set charset to utf8
        $this->connection->set_charset("utf8mb4");
    }

    public function getConnection() {
        return $this->connection;
    }

    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }

    public function executeQuery($sql) {
        $result = $this->connection->query($sql);
        
        if (!$result) {
            die("Query error: " . $this->connection->error);
        }
        
        return $result;
    }

    public function getLastInsertId() {
        return $this->connection->insert_id;
    }

    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

// Create global database instance
$db = new Database();
$conn = $db->getConnection();
?>