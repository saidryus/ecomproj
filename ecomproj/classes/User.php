<?php

class User
{
    // ====== CORE PROPERTIES ======

    //database connection (pdo)
    private $conn;

    //users table name
    private $table_name = "users";

    //save db connection when the class is created
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ====== REGISTRATION ======

    //register a new user account
    public function register($username, $email, $password)
    {
        //basic normalization to avoid null/whitespace issues
        $username = trim((string)$username);
        $email    = trim((string)$email);
        $password = (string)$password;

        //require all three fields to be non empty
        if ($username === '' || $email === '' || $password === '') {
            return false;
        }

        //simple sanitizing: remove html tags
        $username_clean = strip_tags($username);
        $email_clean    = strip_tags($email);

        //hash plain password before storing in db
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " (username, email, password) 
                  VALUES (:username, :email, :password)";
        $stmt = $this->conn->prepare($query);

        //bind user data to placeholders
        $stmt->bindParam(":username", $username_clean);
        $stmt->bindParam(":email", $email_clean);
        $stmt->bindParam(":password", $password_hash);

        try {
            //return true when insert succeeds
            if ($stmt->execute()) {
                return true;
            }
        } catch (PDOException $e) {
            //you could log $e->getMessage() somewhere for debugging
            return false;
        }

        return false;
    }

    // ====== LOGIN ======

    //log an existing user in and populate $_SESSION
    public function login($email, $password)
    {
        //normalize raw input
        $email    = trim((string)$email);
        $password = (string)$password;

        //quick guard against empty values
        if ($email === '' || $password === '') {
            return false;
        }

        //simple hard coded admin account for demo/testing
        if ($email === 'admin@admin.op' && $password === 'admin') {
            $_SESSION['user_id']   = 0;
            $_SESSION['username']  = 'Admin';
            $_SESSION['email']     = 'admin';
            $_SESSION['is_admin']  = true;
            return true;
        }

        //strip tags from email to be safe
        $email_clean = strip_tags($email);

        //look up user by email
        $query = "SELECT id, username, email, password 
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email_clean);
        $stmt->execute();

        //if we found exactly one matching user
        if ($stmt->rowCount() === 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            //verify plain password against stored hash
            if (password_verify($password, $row['password'])) {
                //store user data in session for later requests
                $_SESSION['user_id']  = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['email']    = $row['email'];
                $_SESSION['is_admin'] = false;
                return true;
            }
        }

        //if we reach here login failed
        return false;
    }
}
