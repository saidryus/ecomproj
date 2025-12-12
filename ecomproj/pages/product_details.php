<?php
// Product details page:
// Shows gallery, seller info, wishlist controls, cart actions, and purchase-gated reviews.

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES & MODELS ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../classes/Wishlist.php';

// Shared DB connection + model instances
$database   = new Database();
$db         = $database->getConnection();
$product    = new Product($db);
$reviewObj  = new Review($db);
$wishlistObj = new Wishlist($db);

// ====== INPUT VALIDATION (PRODUCT ID) ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    // Invalid or missing id; send user back to homepage
    header("Location: index.php");
    exit();
}

// ====== LOAD PRODUCT + IMAGES ======
$product_data = $product->getById($id);
if (!$product_data) {
    // Do not show a broken page when product is missing
    header("Location: index.php");
    exit();
}

// All images for gallery, ordered so first is main image
$imgStmt = $db->prepare(
    "SELECT filename
     FROM product_images
     WHERE product_id = :pid
     ORDER BY sort_order ASC, id ASC"
);
$imgStmt->execute([':pid' => $id]);
$product_images = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// ====== HANDLE REVIEW SUBMISSION (ONLY BUYERS) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_rating'])) {
    // Require login to leave a review
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Only allow reviews from users who actually purchased this product
    if (!$reviewObj->userHasPurchased($id, $_SESSION['user_id'])) {
        header("Location: product_details.php?id=" . $id);
        exit;
    }

    // Rating + optional comment; add or update single review per user
    $rating  = (int)($_POST['review_rating'] ?? 0);
    $comment = $_POST['review_comment'] ?? '';
    $reviewObj->addOrUpdate($id, $_SESSION['user_id'], $rating, $comment);

    // Post‑redirect‑get to avoid duplicate submissions
    header("Location: product_details.php?id=" . $id);
    exit;
}

// ====== LOAD REVIEWS + REVIEW PERMISSIONS ======

// Aggregate stats: avg rating + total count
$reviewStats = $reviewObj->getStats($id);
// All reviews for listing section
$reviewsStmt = $reviewObj->getForProduct($id);

// Determine if current user can leave a review
$canReview = false;
if (isset($_SESSION['user_id']) &&
    (int)$_SESSION['user_id'] !== (int)$product_data['user_id']) {
    // Buyer-only review rule; sellers cannot review their own products
    $canReview = $reviewObj->userHasPurchased($id, $_SESSION['user_id']);
}

// ====== WISHLIST STATE FOR CURRENT USER ======
$inWishlist = false;
if (isset($_SESSION['user_id'])) {
    // Used to toggle "Save item / Unsave" CTA
    $inWishlist = $wishlistObj->isInWishlist($_SESSION['user_id'], $id);
}

// ====== TRACK PRODUCT VIEWS ======
// Increment simple view counter once per page load
$updateViews = $db->prepare("UPDATE products SET views = views + 1 WHERE id = :id");
$updateViews->bindParam(':id', $id, PDO::PARAM_INT);
$updateViews->execute();

// ====== PAGE TITLE + HEADER ======
$page_title = $product_data['name'] . " – GameSense";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container product-details-page">
    <!-- Back button with animation -->
    <a href="marketplace.php" class="btn btn-secondary reveal-on-scroll" style="margin-bottom: 2rem;">
        ← Back to products
    </a>

    <div class="product-details-layout">
        <!-- ====== IMAGE GALLERY COLUMN ====== -->
        <div class="product-details-image-wrapper reveal-on-scroll">
            <?php
            // Build gallery array: prefer multi-image table; fall back to legacy single image
            $gallery = !empty($product_images) ? $product_images : [];
            ?>
            <?php if (!empty($gallery) || !empty($product_data['image'])): ?>
                <?php
                $mainImage = !empty($gallery) ? $gallery[0] : $product_data['image'];
                ?>
                <div class="product-gallery-main">
                    <img
                        id="product-main-image"
                        src="/uploads/<?php echo htmlspecialchars($mainImage); ?>"
                        alt="<?php echo htmlspecialchars($product_data['name']); ?>">
                </div>

                <?php if (!empty($gallery) && count($gallery) > 1): ?>
                    <!-- Thumbnail strip to swap main image -->
                    <div class="product-gallery-thumbs">
                        <?php foreach ($gallery as $idx => $img): ?>
                            <button
                                type="button"
                                class="product-thumb-btn"
                                data-full="/uploads/<?php echo htmlspecialchars($img); ?>">
                                <img
                                    src="/uploads/<?php echo htmlspecialchars($img); ?>"
                                    alt="<?php echo htmlspecialchars($product_data['name']); ?> thumbnail <?php echo $idx+1; ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Generic placeholder when no images are available -->
                <div class="product-gallery-main">
                    <img
                        src="https://via.placeholder.com/800x600/020617/ffffff?text=Product+image"
                        alt="<?php echo htmlspecialchars($product_data['name']); ?>">
                </div>
            <?php endif; ?>
        </div>

        <!-- ====== RIGHT COLUMN: DETAILS, REVIEWS, CTAs ====== -->
        <div class="product-details-main">
            <!-- Title + price + basic meta -->
            <div class="reveal-on-scroll">
                <h1 class="product-details-title">
                    <?php echo htmlspecialchars($product_data['name']); ?>
                </h1>

                <div>
                    <div class="product-details-price">
                        ₱<?php echo number_format($product_data['price'], 2); ?>
                    </div>
                    <div class="product-details-meta">
                        <?php echo htmlspecialchars($product_data['category']); ?>
                        <?php if (!empty($product_data['tags'])): ?>
                            • <?php echo htmlspecialchars($product_data['tags']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Description block -->
            <div class="reveal-on-scroll">
                <h3 class="product-details-section-title">Description</h3>
                <p class="product-details-description">
                    <?php echo nl2br(htmlspecialchars($product_data['description'])); ?>
                </p>
            </div>

            <!-- Seller info + contact seller CTA -->
            <div class="reveal-on-scroll">
                <h3 class="product-details-section-title">Seller information</h3>
                <div class="seller-card">
                    <p><strong>Seller:</strong> <?php echo htmlspecialchars($product_data['username']); ?></p>
                    <?php if (!empty($product_data['created_at'])): ?>
                        <p><strong>Listed on:</strong> <?php echo htmlspecialchars($product_data['created_at']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_id']) &&
                          (int)$_SESSION['user_id'] !== (int)$product_data['user_id']): ?>
                    <!-- Prefilled subject helps seller know which listing the message is about -->
                    <div class="product-contact-cta" style="margin-top:1rem;">
                        <a
                            href="message_send.php?to=<?php echo (int)$product_data['user_id']; ?>&subject=<?php echo urlencode('Regarding: ' . $product_data['name']); ?>"
                            class="btn">
                            Message seller
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Reviews summary + form + list -->
            <div class="reveal-on-scroll">
                <h3 class="product-details-section-title">Reviews</h3>
                <p class="product-details-meta">
                    <?php if ($reviewStats['total'] > 0): ?>
                        ★ <?php echo $reviewStats['avg']; ?> / 5
                        · <?php echo $reviewStats['total']; ?> review(s)
                    <?php else: ?>
                        No reviews yet.
                    <?php endif; ?>
                </p>

                <?php if ($canReview): ?>
                    <!-- Single review per buyer; form will update if they already reviewed -->
                    <form method="post" style="margin-top:1rem;">
                        <div class="form-group">
                            <label class="form-label">Your rating</label>
                            <select name="review_rating" class="form-control" required>
                                <option value="">Select…</option>
                                <option value="5">★★★★★ (5)</option>
                                <option value="4">★★★★☆ (4)</option>
                                <option value="3">★★★☆☆ (3)</option>
                                <option value="2">★★☆☆☆ (2)</option>
                                <option value="1">★☆☆☆☆ (1)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Your review</label>
                            <textarea
                                name="review_comment"
                                rows="3"
                                class="form-control"
                                placeholder="Share your experience with this product"></textarea>
                        </div>
                        <button type="submit" class="btn">Submit review</button>
                    </form>
                <?php elseif (isset($_SESSION['user_id']) &&
                              (int)$_SESSION['user_id'] !== (int)$product_data['user_id']): ?>
                    <p class="product-details-meta" style="margin-top:0.75rem;">
                        You can leave a review after purchasing this product.
                    </p>
                <?php endif; ?>

                <?php if ($reviewStats['total'] > 0): ?>
                    <!-- Existing reviews list -->
                    <div style="margin-top:1.5rem; display:flex; flex-direction:column; gap:0.75rem;">
                        <?php while ($r = $reviewsStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <div class="seller-card">
                                <p>
                                    <strong><?php echo htmlspecialchars($r['username']); ?></strong>
                                    · ★ <?php echo (int)$r['rating']; ?>/5
                                </p>
                                <p><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                                <p style="font-size:0.7rem; color:#9ca3af; margin-top:0.25rem;">
                                    <?php echo htmlspecialchars($r['created_at']); ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Purchase + wishlist actions -->
            <div class="product-contact-cta reveal-on-scroll">
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <!-- Add to cart: stays on page -->
                    <form
    method="POST"
    action="add_to_cart.php"
    class="add-to-cart-form"
    data-product-id="<?php echo $product_data['id']; ?>"
>
    <button type="submit" class="btn btn-secondary">
        Add to cart
    </button>
</form>

                    <!-- Buy now: add then redirect to cart for checkout -->
                    <form method="POST" action="add_to_cart.php">
                        <input type="hidden" name="product_id" value="<?php echo $product_data['id']; ?>">
                        <input type="hidden" name="redirect" value="cart">
                        <button type="submit" class="btn">
                            Buy now
                        </button>
                    </form>

                    <!-- Wishlist toggle for logged‑in users -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a
                            href="wishlist_toggle.php?product_id=<?php echo (int)$product_data['id']; ?>&mode=<?php echo $inWishlist ? 'remove' : 'add'; ?>&redirect=product_details.php?id=<?php echo (int)$product_data['id']; ?>"
                            class="btn btn-secondary">
                            <?php echo $inWishlist ? 'Unsave' : 'Save item'; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/js/product-gallery.js?v=<?php echo time(); ?>"></script>
<script src="/js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<script src="/js/cart-actions.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
