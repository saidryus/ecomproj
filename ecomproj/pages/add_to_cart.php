<?php
// Turn off error display for clean JSON
error_reporting(0);
ini_set('display_errors', 0);

// Start output buffering
ob_start();

session_start();
require_once __DIR__ . '/../classes/Cart.php';

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$redirect = $_POST['redirect'] ?? '';
$is_ajax = isset($_POST['ajax']) && $_POST['ajax'] == '1';

if ($product_id > 0) {
    Cart::add($product_id, 1);
    
    // AJAX response
    if ($is_ajax) {
        // Discard any output that might have been generated
        ob_end_clean();
        
        // Set header and output clean JSON
        header('Content-Type: application/json');
        die(json_encode([
            'success' => true,
            'cart_count' => Cart::getCount(),
            'product_id' => $product_id,
            'message' => 'Product added to cart successfully'
        ]));
    }
    
    // Normal redirect for "Buy now"
    if ($redirect === 'cart') {
        header("Location: cart.php");
        exit();
    }
    
    // Redirect back to referring page
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $referer");
    exit();
}

// Error handling
if ($is_ajax) {
    ob_end_clean();
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]));
}

header("Location: index.php");
exit();
