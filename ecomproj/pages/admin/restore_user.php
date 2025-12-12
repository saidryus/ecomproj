<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can restore (undelete) user accounts
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== VALIDATE USER ID ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: users.php');
    exit;
}

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// ====== UNDELETE USER (SOFT DELETE FLAG) ======
// Flip is_deleted back to 0 to restore access
$stmt = $db->prepare("UPDATE users SET is_deleted = 0 WHERE id = :id");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();

// ====== REDIRECT BACK TO USER LIST ======
header('Location: users.php');
exit;
