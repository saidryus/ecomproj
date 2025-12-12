<?php

// ====== DATABASE CONFIGURATION CONSTANTS ======

//host where mysql is running
define('DB_HOST', 'localhost');
//database name for this project
define('DB_NAME', 'ecommerce_db');
//mysql username
define('DB_USER', 'root');
//mysql password
define('DB_PASS', '');

// ====== INITIAL SETUP (DATABASE + CORE TABLES) ======

try {
    //base connection: connect only to the server, no specific database yet
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    //throw exceptions when something goes wrong
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //create database if it does not exist yet
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);

    //switch the connection to use our project database
    $pdo->exec("USE " . DB_NAME);

    // ====== USERS TABLE ======
    //stores basic account info and login credentials
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // ====== PRODUCTS TABLE (BASE) ======
    //core product data; more columns can be added by later migrations
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,                -- seller/owner id
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category VARCHAR(100) NOT NULL,
            image VARCHAR(255),                  -- legacy single image (main image)
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                      ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // ====== EXPOSE PDO HANDLE TO REST OF APP ======
    //other files include this and use $db as the shared connection
    $db = $pdo;

} catch (PDOException $e) {
    //if anything fails during setup, stop execution with a clear message
    die("Database setup error: " . $e->getMessage());
}
