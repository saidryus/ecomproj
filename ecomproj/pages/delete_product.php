<?php
session_start();

// ====== DEV ERROR DISPLAY (REMOVE IN PRODUCTION) ======
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';

//$db is created inside config/database.php
if (!isset($db) || !$db) {
    //hard stop if db is not ready
    die('Database connection problem.');
}

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ====== ID VALIDATION ======
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: dashboard.php');
    exit;
}

$id     = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];

// ====== MODEL SETUP ======
$product = new Product($db);

// ====== OWNERSHIP CHECK ======
//only proceed when current user owns this product
if (method_exists($product, 'isOwner') && !$product->isOwner($id, $userId)) {
    header('Location: dashboard.php');
    exit;
}

// ====== EXISTENCE CHECK ======
$item = $product->readOne($id);
if (!$item) {
    header('Location: dashboard.php');
    exit;
}

// ====== DELETE & REDIRECT ======
//delete product row and send user back to dashboard
$product->delete($id);

header('Location: dashboard.php?message=deleted');
exit;
