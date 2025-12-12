<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can access marketplace-wide performance reports
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// ====== MOST VIEWED PRODUCTS (TOP 5, APPROVED ONLY) ======
$topViewedStmt = $db->query(
    "SELECT id, name, views, sold, price
     FROM products
     WHERE is_approved = 1
     ORDER BY views DESC
     LIMIT 5"
);
$topViewed = $topViewedStmt->fetchAll(PDO::FETCH_ASSOC);

// Find max views to scale bar widths proportionally
$maxViews = 0;
foreach ($topViewed as $row) {
    $maxViews = max($maxViews, (int)$row['views']);
}

// ====== TOP SELLERS BY UNITS SOLD (TOP 5, APPROVED ONLY) ======
$topSoldStmt = $db->query(
    "SELECT id, name, views, sold, price
     FROM products
     WHERE is_approved = 1
     ORDER BY sold DESC
     LIMIT 5"
);
$topSold = $topSoldStmt->fetchAll(PDO::FETCH_ASSOC);

// Find max sold count to scale bar widths
$maxSold = 0;
foreach ($topSold as $row) {
    $maxSold = max($maxSold, (int)$row['sold']);
}

// ====== PAGE HEADER ======
$page_title = 'Admin – Reports';
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Reports</h1>
            <p class="dashboard-subtitle">
                Top performing products in your marketplace.
            </p>
        </div>
    </div>

    <div class="dashboard-overview">
        <!-- ====== MOST VIEWED REPORT CARD ====== -->
        <div class="overview-card reveal-on-scroll admin-report-card">
            <h2 class="overview-title">Most viewed products</h2>
            <p class="overview-subtitle">Products with the highest view counts.</p>
            <div class="overview-bars">
                <?php if (empty($topViewed)): ?>
                    <p class="overview-empty">No data yet.</p>
                <?php else: ?>
                    <?php foreach ($topViewed as $p): ?>
                        <?php
                            $v     = (int)$p['views'];
                            $ratio = $maxViews > 0 ? ($v / $maxViews) : 0;
                            // Ensure very low values still show a small bar (8–100%)
                            $width = max(8, (int)round($ratio * 100));
                        ?>
                        <div class="overview-bar-row">
                            <div class="overview-bar-label-wrap">
                                <span class="overview-bar-label">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </span>
                            </div>
                            <div class="overview-bar-track">
                                <div class="overview-bar-fill"
                                     style="width: <?php echo $width; ?>%;"></div>
                            </div>
                            <div class="overview-bar-value">
                                <?php echo $v; ?> views
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ====== TOP SELLERS REPORT CARD ====== -->
        <div class="overview-card reveal-on-scroll admin-report-card" style="margin-top:2rem;">
            <h2 class="overview-title">Top sellers</h2>
            <p class="overview-subtitle">Products with the most units sold.</p>
            <div class="overview-bars">
                <?php if (empty($topSold)): ?>
                    <p class="overview-empty">No data yet.</p>
                <?php else: ?>
                    <?php foreach ($topSold as $p): ?>
                        <?php
                            $s     = (int)$p['sold'];
                            $ratio = $maxSold > 0 ? ($s / $maxSold) : 0;
                            $width = max(8, (int)round($ratio * 100)); // 8–100%
                        ?>
                        <div class="overview-bar-row">
                            <div class="overview-bar-label-wrap">
                                <span class="overview-bar-label">
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </span>
                            </div>
                            <div class="overview-bar-track">
                                <div class="overview-bar-fill"
                                     style="width: <?php echo $width; ?>%;"></div>
                            </div>
                            <div class="overview-bar-value">
                                <?php echo $s; ?> sold
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script src="../../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
