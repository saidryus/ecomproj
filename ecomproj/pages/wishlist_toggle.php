<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Wishlist.php';

// ====== INPUTS: PRODUCT + MODE + REDIRECT ======
$pid      = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$mode     = $_GET['mode'] ?? 'add';          // 'add' or 'remove'
$redirect = $_GET['redirect'] ?? 'marketplace.php';

// If product id is invalid, just bounce back to the redirect target
if ($pid <= 0) {
    header("Location: {$redirect}");
    exit;
}

// ====== DB + WISHLIST MODEL SETUP ======
$dbObj    = new Database();
$db       = $dbObj->getConnection();
$wishlist = new Wishlist($db);

// ====== TOGGLE WISHLIST ENTRY ======
// Simple toggle based on ?mode=add|remove, scoped to current user
if ($mode === 'remove') {
    $wishlist->remove($_SESSION['user_id'], $pid);
} else {
    $wishlist->add($_SESSION['user_id'], $pid);
}

// ====== REDIRECT BACK TO CALLER ======
header("Location: {$redirect}");
exit;
