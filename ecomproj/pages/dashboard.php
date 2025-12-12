<?php
// ====== DEV ERROR DISPLAY (REMOVE IN PRODUCTION) ======
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE CLASSES ======
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Product.php';

//page title used by shared header
$page_title = "Dashboard – GameSense";
require_once __DIR__ . '/../includes/header.php';

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

//current user info from session
$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Player';

// ====== SETUP DB + PRODUCT MODEL ======
$database = new Database();
$db       = $database->getConnection();
$product  = new Product($db);

// ====== LOAD PRODUCTS FOR THIS USER ======
$stmt         = $product->readByUser($user_id);
$userProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== METRICS AGGREGATION ======

$totalProducts  = count($userProducts);
$totalValue     = 0;
$totalViews     = 0;
$totalSold      = 0;
$categoryTotals = []; //category => total value
$categoryCounts = []; //category => number of products
$totalRevenue   = 0;  //price * sold across all products

foreach ($userProducts as $p) {
    $price = (float)($p['price'] ?? 0);
    $cat   = $p['category'] ?? 'Other';
    $sold  = (int)($p['sold'] ?? 0);
    $views = (int)($p['views'] ?? 0);

    $totalValue += $price;
    $totalViews += $views;
    $totalSold  += $sold;

    //revenue from this product
    $totalRevenue += $price * $sold;

    //per-category sums and counts
    $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $price;
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
}

//average listing price
$avgPrice = $totalProducts > 0 ? $totalValue / $totalProducts : 0;

//largest category total to scale the bars
$maxCategoryTotal = !empty($categoryTotals) ? max($categoryTotals) : 0;
?>

<div class="container dashboard-page">
    <!--greeting + quick create button-->
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">
                Welcome back, <?php echo htmlspecialchars($username); ?>!
            </h1>
            <p class="dashboard-subtitle">
                Manage your listings and keep track of your store at a glance.
            </p>
        </div>
        <a href="add_product.php" class="btn">Add new product</a>
    </div>

    <!--simple top metrics strip-->
    <section class="dashboard-metrics reveal-on-scroll">
        <div class="metric-card">
            <p class="metric-label">Total products</p>
            <p class="metric-value"><?php echo $totalProducts; ?></p>
        </div>
        <div class="metric-card">
            <p class="metric-label">Total value</p>
            <p class="metric-value">₱<?php echo number_format($totalValue, 2); ?></p>
        </div>
    </section>

    <!--richer overview card with more stats and category bars-->
    <section class="dashboard-metrics reveal-on-scroll">
        <div class="overview-card">
            <h2 class="overview-title">Store Overview</h2>
            <p class="overview-subtitle">
                Snapshot of your listings, views, and sales.
            </p>

            <!--key numbers row-->
            <div class="overview-metrics-row">
                <div class="overview-metric">
                    <span class="overview-metric-label">Total products</span>
                    <span class="overview-metric-value"><?php echo $totalProducts; ?></span>
                </div>
                <div class="overview-metric">
                    <span class="overview-metric-label">Inventory value</span>
                    <span class="overview-metric-value">
                        ₱<?php echo number_format($totalValue, 2); ?>
                    </span>
                </div>
                <div class="overview-metric">
                    <span class="overview-metric-label">Avg. price</span>
                    <span class="overview-metric-value">
                        ₱<?php echo number_format($avgPrice, 2); ?>
                    </span>
                </div>
                <div class="overview-metric">
                    <span class="overview-metric-label">Total views</span>
                    <span class="overview-metric-value"><?php echo $totalViews; ?></span>
                </div>
                <div class="overview-metric">
                    <span class="overview-metric-label">Total sold</span>
                    <span class="overview-metric-value"><?php echo $totalSold; ?></span>
                </div>
                <div class="overview-metric">
                    <span class="overview-metric-label">Total revenue</span>
                    <span class="overview-metric-value">
                        ₱<?php echo number_format($totalRevenue, 2); ?>
                    </span>
                </div>
            </div>

            <!--per-category bars (only when data exists)-->
            <?php if (!empty($categoryTotals)): ?>
                <div class="overview-bars">
                    <h3 class="overview-bars-title">By category (value)</h3>
                    <?php foreach ($categoryTotals as $cat => $value):
                        $percent = $maxCategoryTotal > 0
                            ? ($value / $maxCategoryTotal) * 100
                            : 0;
                    ?>
                        <div class="overview-bar-row">
                            <span class="overview-bar-label">
                                <?php echo htmlspecialchars($cat); ?>
                                <span class="overview-bar-count">
                                    (<?php echo $categoryCounts[$cat]; ?>)
                                </span>
                            </span>
                            <div class="overview-bar-track">
                                <div class="overview-bar-fill"
                                     style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                            <span class="overview-bar-value">
                                ₱<?php echo number_format($value, 2); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!--cards for each of the user's products-->
    <section class="dashboard-products reveal-on-scroll">
        <h2 class="section-heading">Your products</h2>

        <?php if ($totalProducts === 0): ?>
            <!--empty state when seller has no listings-->
            <div class="dashboard-empty-card">
                <div class="dashboard-empty-text">
                    <p class="dashboard-empty-title">
                        You haven't added any products yet.
                    </p>
                    <p class="dashboard-empty-subtitle">
                        Start by adding your first product to the store.
                    </p>
                </div>
                <a href="add_product.php" class="btn">
                    Add your first product
                </a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($userProducts as $index => $p): ?>
                    <article
                        class="product-card-modern reveal-on-scroll"
                        style="transition-delay: <?php echo min($index * 80, 600); ?>ms;"
                    >
                        <!--product thumbnail with fallback placeholder-->
                        <div class="product-media-wrapper">
                            <?php if (!empty($p['main_image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($p['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($p['name']); ?>"
                                    class="product-image-modern">
                            <?php elseif (!empty($p['image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($p['image']); ?>"
                                    alt="<?php echo htmlspecialchars($p['name']); ?>"
                                    class="product-image-modern">
                            <?php else: ?>
                                <div class="product-image-modern product-image-placeholder">
                                    <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!--text body with actions-->
                        <div class="product-body-modern">
                            <h3 class="product-name-modern">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </h3>
                            <p class="product-meta-modern">
                                <?php echo htmlspecialchars($p['category']); ?>
                            </p>
                            <p class="product-desc-modern">
                                <?php echo htmlspecialchars(
                                    mb_strimwidth($p['description'], 0, 80, '…')
                                ); ?>
                            </p>
                            <div class="product-footer-modern">
                                <span class="product-price-modern">
                                    ₱<?php echo number_format($p['price'], 2); ?>
                                </span>
                                <div class="product-actions-modern">
                                    <a
                                        href="product_details.php?id=<?php echo $p['id']; ?>"
                                        class="btn btn-secondary">
                                        View
                                    </a>
                                    <a
                                        href="edit_product.php?id=<?php echo $p['id']; ?>"
                                        class="btn btn-secondary">
                                        Edit
                                    </a>
                                    <a
                                        href="delete_product.php?id=<?php echo $p['id']; ?>"
                                        class="product-delete-link"
                                        onclick="return confirm('Delete this listing? This cannot be undone.');">
                                        Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<!--scroll reveal animations for dashboard sections-->
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
