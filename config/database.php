<?php
// Database Configuration
class Database {
    private $host = "localhost";
    private $db_name = "ibs_store";
    private $username = "root";
    private $password = "";
    public $conn;

    // Get database connection
    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Don't echo errors directly as it breaks JSON responses
            error_log("Database connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>
