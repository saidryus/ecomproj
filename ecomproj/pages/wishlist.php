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

// ====== DB + WISHLIST MODEL SETUP ======
$dbObj    = new Database();
$db       = $dbObj->getConnection();
$wishlist = new Wishlist($db);

// Fetch all wishlist products joined with product data for current user
$stmt  = $wishlist->forUser($_SESSION['user_id']);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($items);

// Page title used by shared header
$page_title = "Saved items – GameSense";
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <!-- Header row: title + count -->
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Saved items</h1>
            <p class="dashboard-subtitle">Products you have added to your wishlist.</p>
            <p class="marketplace-meta" style="margin-top:0.5rem;">
                <?php echo (int)$total; ?> saved item(s)
            </p>
        </div>
    </div>

    <?php if ($total === 0): ?>
        <!-- Empty state when wishlist has no items -->
        <div class="dashboard-empty-card reveal-on-scroll" style="margin-top:1.5rem;">
            <div class="dashboard-empty-text">
                <p class="dashboard-empty-title">No saved items yet.</p>
                <p class="dashboard-empty-subtitle">
                    Browse the marketplace and click “Save” on products you like.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Saved items grid; each card links to product details -->
        <div class="product-grid marketplace-grid reveal-on-scroll"
             style="margin-top:1.5rem; gap:1rem;">
            <?php foreach ($items as $row): ?>
                <article
                    class="list-form-card"
                    style="
                        padding:0;
                        overflow:hidden;
                        border-radius:1rem;
                        background:linear-gradient(135deg,#020617,#020617);
                        transition:transform 150ms ease, box-shadow 150ms ease;
                    ">
                    <a href="product_details.php?id=<?php echo (int)$row['id']; ?>"
                       style="display:flex; gap:0.9rem; align-items:flex-start;
                              padding:0.85rem 1.1rem; text-decoration:none;"
                       onmouseover="this.parentElement.style.transform='translateY(-2px)'; this.parentElement.style.boxShadow='0 12px 30px rgba(0,0,0,0.35)';"
                       onmouseout="this.parentElement.style.transform='none'; this.parentElement.style.boxShadow='none';">
                        <!-- Thumbnail: main image, fallback to legacy image, then initials -->
                        <div style="
                            width:72px; height:72px; border-radius:0.75rem;
                            overflow:hidden; background:#020617; flex-shrink:0;
                            display:flex; align-items:center; justify-content:center;">
                            <?php if (!empty($row['main_image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($row['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                                    style="width:100%; height:100%; object-fit:cover; transition:transform 200ms ease;"/>
                            <?php elseif (!empty($row['image'])): ?>
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($row['image']); ?>"
                                    alt="<?php echo htmlspecialchars($row['name']); ?>"
                                    style="width:100%; height:100%; object-fit:cover; transition:transform 200ms ease;"/>
                            <?php else: ?>
                                <span style="color:#4b5563; font-weight:600; font-size:0.9rem;">
                                    <?php echo strtoupper(substr($row['name'], 0, 2)); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Text stack: name, meta, price -->
                        <div style="display:flex; flex-direction:column; gap:0.25rem; min-width:0;">
                            <span style="
                                color:#e5e7eb; font-weight:600;
                                white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </span>
                            <span style="font-size:0.8rem; color:#9ca3af;">
                                <?php echo htmlspecialchars($row['category']); ?>
                                <?php if (!empty($row['tags'])): ?>
                                    • <?php echo htmlspecialchars($row['tags']); ?>
                                <?php endif; ?>
                            </span>
                            <span style="font-size:0.85rem; color:#fbbf24;">
                                ₱<?php echo number_format($row['price'], 2); ?>
                            </span>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
