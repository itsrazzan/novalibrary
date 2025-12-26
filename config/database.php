<?php
class Database {
    private $host = "localhost";
    private $port = "5432";
    private $db_name = "novalibrary"; // Ganti dengan DB Anda
    private $username = "admin";          // Ganti dengan user DB Anda
    private $password = "BismillaH97";     // Ganti dengan password DB Anda
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
                // PostgreSQL connection string using unix socket
                $dsn = "pgsql:host=/var/run/postgresql;dbname=" . $this->db_name;
                $this->conn = new PDO($dsn, $this->username, $this->password);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>