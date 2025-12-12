<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Cart.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

Cart::init();

// if cart empty, just go back
$items = Cart::getItems();
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$database = new Database();
$db       = $database->getConnection();

// Fake “order”: increase sold counts
foreach ($items as $productId => $qty) {
    $stmt = $db->prepare("UPDATE products SET sold = sold + :qty WHERE id = :id");
    $stmt->bindParam(':id',  $productId, PDO::PARAM_INT);
    $stmt->bindParam(':qty', $qty,       PDO::PARAM_INT);
    $stmt->execute();
}

// >>> ADD THIS BLOCK HERE <<<

// compute total from cart
$total = Cart::getTotal($db);

// create order
$stmt = $db->prepare("INSERT INTO orders (user_id, total) VALUES (:uid, :total)");
$stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindParam(':total', $total);
$stmt->execute();
$orderId = $db->lastInsertId();

// insert order items
$itemStmt = $db->prepare(
    "INSERT INTO order_items (order_id, product_id, quantity, price_each)
     VALUES (:oid, :pid, :qty, :price)"
);

foreach ($items as $productId => $qty) {
    $p = $db->prepare("SELECT price FROM products WHERE id = :id LIMIT 1");
    $p->bindParam(':id', $productId, PDO::PARAM_INT);
    $p->execute();
    if (!$row = $p->fetch(PDO::FETCH_ASSOC)) {
        continue;
    }

    $itemStmt->bindParam(':oid', $orderId, PDO::PARAM_INT);
    $itemStmt->bindParam(':pid', $productId, PDO::PARAM_INT);
    $itemStmt->bindParam(':qty', $qty, PDO::PARAM_INT);
    $itemStmt->bindParam(':price', $row['price']);
    $itemStmt->execute();
}

// >>> END ADDED BLOCK <<<

Cart::clear();

// show simple confirmation page
$page_title = "Order confirmed – GameSense";
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container product-details-page">
    <h1 class="section-heading">Order confirmed</h1>
    <div class="alert alert-success">
        <p>Your fake checkout is complete. This order was recorded in your dashboard analytics and your cart has been cleared.</p>
    </div>
    <a href="index.php" class="btn">Back to products</a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
