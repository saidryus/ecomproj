<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can delete users from the admin panel
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== VALIDATE USER ID TO DELETE ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    // Invalid id, return to users list
    header('Location: users.php');
    exit;
}

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// ====== PERFORM SOFT DELETE ======
// Set is_deleted flag to 1 instead of hard deleting
// This allows the user to be restored later
$stmt = $db->prepare("UPDATE users SET is_deleted = 1 WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// ====== REDIRECT BACK TO USER LIST ======
header('Location: users.php');
exit;
