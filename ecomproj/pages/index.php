<?php
session_start();

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Product.php';

// ====== AUTH GUARD FOR LOGGED-IN HOMEPAGE ======
//only logged-in users should see this version; guests use home.php
if (!isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

// ====== LOAD FEATURED PRODUCTS (LATEST 4, APPROVED) ======

$database = new Database();
$db       = $database->getConnection();

$sql = "SELECT p.*, u.username,
           (SELECT filename FROM product_images pi
            WHERE pi.product_id = p.id
            ORDER BY pi.sort_order ASC, pi.id ASC
            LIMIT 1) AS main_image
        FROM products p
        JOIN users u ON p.user_id = u.id
        WHERE p.is_approved = 1
        ORDER BY p.created_at DESC
        LIMIT 4";

$stmt     = $db->prepare($sql);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

//page title used by shared header
$page_title = "GameSense";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <!--hero section with marketing copy + featured product visual-->
    <section class="hero-modern reveal-on-scroll">
        <div class="hero-modern-text">
            <p class="hero-kicker">NEW • PRO SERIES</p>
            <h1 class="hero-title">Built for<br>Pure Focus.</h1>
            <p class="hero-subtitle">
                Lightweight precision gear engineered for competitive play,
                with latency so low it feels wired.
            </p>
            <div class="hero-cta-row">
                <a href="#products" class="btn hero-primary-cta">Shop gear</a>
                <a href="add_product.php" class="hero-secondary-cta">Sell your setup →</a>
            </div>
        </div>

        <div class="hero-modern-media">
            <div class="hero-product-orbit">
                <div class="hero-product-ring"></div>
                <img
                    src="../uploads/homepage_mouse_display.png"
                    alt="Featured Gaming Mouse"
                    class="hero-product-image">
            </div>
            <p class="hero-caption">
                Pulsar X2 • Wireless esports mouse • 32g • 8K polling
            </p>
        </div>
    </section>

    <!--quick feature stats row under hero-->
    <section class="feature-strip reveal-on-scroll">
        <div class="feature-pill">
            <span class="feature-label">Latency</span>
            <span class="feature-value">0.9 ms</span>
        </div>
        <div class="feature-pill">
            <span class="feature-label">Battery</span>
            <span class="feature-value">90 hrs</span>
        </div>
        <div class="feature-pill">
            <span class="feature-label">Weight</span>
            <span class="feature-value">38 g</span>
        </div>
        <div class="feature-pill">
            <span class="feature-label">Warranty</span>
            <span class="feature-value">2 years</span>
        </div>
    </section>

    <!--collections section describing main categories-->
    <section class="collection-section reveal-on-scroll">
        <div class="collection-text">
            <h2>One ecosystem.<br>All your gear.</h2>
            <p>
                Mix mice, keyboards, headsets and pads that feel like they were
                designed as a single product family, not separate pieces.
            </p>
        </div>
        <div class="collection-grid">
            <div class="collection-card">
                <h3>Mice</h3>
                <p>Ultra‑light shells, optical switches and flawless tracking.</p>
            </div>
            <div class="collection-card">
                <h3>Keyboards</h3>
                <p>Low‑profile, hot‑swap and quiet switches for any desk.</p>
            </div>
            <div class="collection-card">
                <h3>Audio</h3>
                <p>Close‑back headsets tuned for footsteps and comms.</p>
            </div>
        </div>
    </section>

   <!--featured products grid (latest approved items)-->
<section class="products" id="products">
    <h2 class="section-heading reveal-on-scroll">Featured gear</h2>

    <!--small toast when user was redirected here after add_to_cart-->
    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success reveal-on-scroll">
            <strong>Added to cart.</strong> <a href="cart.php">View cart</a>
        </div>
    <?php endif; ?>

    <?php if (count($products) > 0): ?>
        <div class="product-grid">
            <?php $index = 0; foreach ($products as $product_item): $index++; ?>
                <article
                    class="product-card-modern reveal-on-scroll"
                    style="transition-delay: <?php echo ($index * 80); ?>ms;">
                    <!--product thumbnail with main_image fallback logic-->
                    <div class="product-media-wrapper">
                        <?php if (!empty($product_item['main_image'])): ?>
                            <img
                                src="../uploads/<?php echo htmlspecialchars($product_item['main_image']); ?>"
                                alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                class="product-image-modern">
                        <?php elseif (!empty($product_item['image'])): ?>
                            <img
                                src="../uploads/<?php echo htmlspecialchars($product_item['image']); ?>"
                                alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                class="product-image-modern">
                        <?php else: ?>
                            <div class="product-image-modern product-image-placeholder">
                                <span><?php echo strtoupper(substr($product_item['name'], 0, 2)); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!--card body with meta + actions-->
                    <div class="product-body-modern">
                        <h3 class="product-name-modern">
                            <?php echo htmlspecialchars($product_item['name']); ?>
                        </h3>
                        <p class="product-meta-modern">
                            <?php echo htmlspecialchars($product_item['category']); ?>
                            • by <?php echo htmlspecialchars($product_item['username']); ?>
                            <?php if (!empty($product_item['tags'])): ?>
                                • <?php echo htmlspecialchars($product_item['tags']); ?>
                            <?php endif; ?>
                        </p>
                        <p class="product-desc-modern">
                            <?php echo htmlspecialchars(substr($product_item['description'], 0, 90)) . '…'; ?>
                        </p>
                        <div class="product-footer-modern">
                            <span class="product-price-modern">
                                ₱<?php echo number_format($product_item['price'], 2); ?>
                            </span>
                            <div class="product-actions-modern">
                                <a
                                    href="product_details.php?id=<?php echo $product_item['id']; ?>"
                                    class="btn btn-secondary">
                                    Details
                                </a>
                                <form method="POST" action="add_to_cart.php" class="add-to-cart-form" data-product-id="<?php echo $product_item['id']; ?>">
                                    <button type="submit" class="btn">
                                        Add to cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!--fallback when there are no approved products yet-->
        <div class="alert alert-info reveal-on-scroll">
            <h3>No products yet</h3>
            <p>Sign in and be the first to list your gear.</p>
            <a href="add_product.php" class="btn">List a product</a>
        </div>
    <?php endif; ?>
</section>
</div>

<!--scroll reveal animations for hero and product cards-->
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<script src="../js/cart-actions.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
