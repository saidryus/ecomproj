<?php
require_once __DIR__ . '/../config/database.php';

class Database
{
    // ====== CORE CONFIG VALUES ======

    //values pulled from config/database.php constants
    private $host     = DB_HOST;
    private $db_name  = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;

    //pdo connection handle (null until created)
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            // Try standard TCP connection first
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            // If that fails, try with socket (common on shared hosting)
            try {
                $this->conn = new PDO(
                    "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e2) {
                // Try 127.0.0.1 instead of localhost
                try {
                    $this->conn = new PDO(
                        "mysql:host=127.0.0.1;dbname=" . $this->db_name . ";charset=utf8mb4",
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } catch(PDOException $e3) {
                    die("Database Connection Error: " . $e3->getMessage() . 
                        "<br>Please check your database credentials in Database.php");
                }
            }
        }
        
        return $this->conn;
    }
}
