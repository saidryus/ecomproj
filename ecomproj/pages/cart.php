<?php
// ====== DEV ERROR DISPLAY (REMOVE IN PRODUCTION) ======
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE CLASSES ======
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Cart.php';

//page title used by the shared header
$page_title = "Your cart – GameSense";
require_once __DIR__ . '/../includes/header.php';

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ====== HANDLE CART ACTIONS (POST) ======

Cart::init(); //always make sure cart exists

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    //per-item actions (increase / decrease / remove)
    if ($product_id > 0) {
        if ($action === 'decrease') {
            Cart::decrease($product_id, 1);
        } elseif ($action === 'increase') {
            Cart::add($product_id, 1);
        } elseif ($action === 'remove') {
            Cart::remove($product_id);
        }
        header("Location: cart.php");
        exit();
    }

    //clear the whole cart
    if ($action === 'clear') {
        Cart::clear();
        header("Location: cart.php");
        exit();
    }
}

// ====== LOAD PRODUCTS FOR DISPLAY ======

//database + product helper
$database = new Database();
$db       = $database->getConnection();
$product  = new Product($db);

//items in session cart: [product_id => qty]
$items    = Cart::getItems();

$cart_products = [];
$subtotal      = 0.0;

//for each product id in the cart, fetch details + main image
if (!empty($items)) {
    foreach ($items as $product_id => $qty) {
        $query = "SELECT p.id, p.name, p.description, p.price, p.category, p.image,
                         (SELECT filename FROM product_images pi
                          WHERE pi.product_id = p.id
                          ORDER BY pi.sort_order ASC, pi.id ASC
                          LIMIT 1) AS main_image
                  FROM products p
                  WHERE p.id = :id
                  LIMIT 1";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['quantity']   = (int)$qty;
            $row['line_total'] = $row['price'] * $row['quantity'];
            $subtotal         += $row['line_total'];
            $cart_products[]   = $row;
        }
    }
}
?>

<div class="container product-details-page">
    <h1 class="section-heading reveal-on-scroll">Your cart</h1>

    <?php if (empty($cart_products)): ?>
        <!--empty state when there are no items-->
        <div class="alert alert-empty reveal-on-scroll">
            <span class="alert-empty-text">
                Your cart is empty.
            </span>
            <a href="marketplace.php" class="alert-empty-link">
                Browse products →
            </a>
        </div>
    <?php else: ?>

        <!--main cart layout: items on the left, summary on the right-->
        <div class="cart-layout reveal-on-scroll">
            <div class="cart-items">
                <?php foreach ($cart_products as $item): ?>
                    <article class="cart-item-card">
                        <!--product thumbnail or placeholder-->
                        <div class="cart-item-media">
                            <?php if (!empty($item['main_image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($item['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php elseif (!empty($item['image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($item['image']); ?>"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php else: ?>
                                <div class="cart-item-placeholder">
                                    <?php echo strtoupper(substr($item['name'], 0, 2)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!--item info + quantity controls-->
                        <div class="cart-item-body">
                            <h2 class="cart-item-title">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h2>
                            <p class="cart-item-meta">
                                <?php echo htmlspecialchars($item['category']); ?>
                            </p>
                            <p class="cart-item-price">
                                ₱<?php echo number_format($item['price'], 2); ?>
                            </p>

                            <!--quantity buttons and remove-->
                            <div style="margin-top: 0.3rem; display: flex; align-items: center; gap: 0.4rem;">
                                <!--decrease quantity-->
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="decrease">
                                    <button type="submit"
                                            class="btn btn-secondary"
                                            style="padding: 0.3rem 0.7rem; font-size: 0.7rem;">
                                        −
                                    </button>
                                </form>

                                <!--current quantity-->
                                <span class="cart-item-qty" style="min-width: 2rem; text-align: center;">
                                    <?php echo $item['quantity']; ?>
                                </span>

                                <!--increase quantity-->
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="increase">
                                    <button type="submit"
                                            class="btn btn-secondary"
                                            style="padding: 0.3rem 0.7rem; font-size: 0.7rem;">
                                        +
                                    </button>
                                </form>

                                <!--remove line entirely-->
                                <form method="POST" action="cart.php" style="margin-left: 0.6rem;">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit"
                                            class="btn btn-secondary"
                                            style="font-size: 0.7rem;">
                                        Remove
                                    </button>
                                </form>
                            </div>

                            <!--line total for this product-->
                            <p class="cart-item-line-total" style="margin-top: 0.3rem;">
                                Line total: ₱<?php echo number_format($item['line_total'], 2); ?>
                            </p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!--summary card on the right-->
            <aside class="cart-summary reveal-on-scroll">
                <h2 class="cart-summary-title">Summary</h2>

                <div class="cart-summary-row">
                    <span>Subtotal</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="cart-summary-row">
                    <span>Shipping</span>
                    <span>Calculated at checkout</span>
                </div>
                <div class="cart-summary-row cart-summary-total">
                    <span>Total</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>

                <!--proceed to checkout (get request)-->
                <form method="GET" action="checkout.php" style="margin-top: 1rem">
                    <button type="submit" class="btn" style="width: 100%">
                        Proceed to checkout
                    </button>
                </form>

                <!--clear the entire cart-->
                <form method="POST" action="cart.php" style="margin-top: 0.6rem;">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit"
                            class="btn btn-secondary"
                            style="width: 100%; font-size: 0.75rem;">
                        Clear cart
                    </button>
                </form>

                <!--link back to browsing-->
                <a href="index.php"
                   class="hero-secondary-cta"
                   style="display: inline-block; margin-top: 0.8rem;">
                    Continue shopping →
                </a>
            </aside>
        </div>
    <?php endif; ?>
</div>

<!--scroll reveal for cards and summary-->
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
