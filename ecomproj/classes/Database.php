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

    // ====== CONNECTION FACTORY ======

    //open a database connection and return the pdo instance
    public function getConnection()
    {
        //start with no connection
        $this->conn = null;

        try {
            //build the dsn string and create a new pdo object
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            //turn on exceptions so pdo throws errors instead of failing silently
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            //in a real app you would log this instead of echoing it
            echo "connection error: " . $exception->getMessage();
        }

        //return the pdo connection (or null if it failed)
        return $this->conn;
    }
}
?>
