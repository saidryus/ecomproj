<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Wishlist.php';

// ====== DB + MODEL SETUP ======
$database = new Database();
$db       = $database->getConnection();
$product  = new Product($db);
$wishlist = new Wishlist($db);

// ====== FILTER INPUTS ======

//all tag filters that can be used
$available_filters = ['mouse','keyboard','headset','mousepad','switches','wireless','wired','accessory'];

//tags from query string (can be array or string)
$active_tags = $_GET['tags'] ?? [];
if (!is_array($active_tags)) {
    $active_tags = [$active_tags];
}
//keep only tags that are in the allowed list
$active_tags = array_values(array_intersect($active_tags, $available_filters));

//search keyword
$search = trim($_GET['q'] ?? '');

//price and stock filters
$minPrice  = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice  = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$onlyStock = isset($_GET['in_stock']) ? 1 : 0;

//sort mode: newest | price_asc | price_desc | popular
$sort = $_GET['sort'] ?? 'newest';

// ====== BUILD BASE QUERY (WITH MAIN IMAGE SUBQUERY) ======

$sql = "SELECT p.*,
               u.username,
               (SELECT filename FROM product_images pi
                WHERE pi.product_id = p.id
                ORDER BY pi.sort_order ASC, pi.id ASC
                LIMIT 1) AS main_image
        FROM products p
        JOIN users u ON p.user_id = u.id
        WHERE 1=1";
$params = [];

//search across name, category, tags and description
if ($search !== '') {
    $sql .= " AND (p.name LIKE :term
              OR p.category LIKE :term
              OR p.tags LIKE :term
              OR p.description LIKE :term)";
    $params[':term'] = '%' . $search . '%';
}

//price range filters
if ($minPrice !== null) {
    $sql .= " AND p.price >= :min_price";
    $params[':min_price'] = $minPrice;
}
if ($maxPrice !== null) {
    $sql .= " AND p.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

//stock filter
if ($onlyStock) {
    $sql .= " AND p.stock > 0";
}

//sorting
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.views DESC";
        break;
    default: //newest
        $sql .= " ORDER BY p.created_at DESC";
        break;
}

// ====== EXECUTE QUERY ======

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== TAG FILTERING IN PHP (OR MATCH) ======

//if any tag filters are active, keep products that contain at least one tag
if (!empty($active_tags)) {
    $products = array_filter($all_products, function ($item) use ($active_tags) {
        $tags_str = strtolower($item['tags'] ?? '');
        foreach ($active_tags as $tag) {
            if (strpos($tags_str, $tag) !== false) {
                return true;
            }
        }
        return false;
    });
    $products = array_values($products);
} else {
    $products = $all_products;
}

// ====== PAGINATION ======

$perPage = 12;
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$totalItems = count($products);
$totalPages = max(1, (int)ceil($totalItems / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset        = ($page - 1) * $perPage;
$products_page = array_slice($products, $offset, $perPage);

// ====== PAGE HEADER ======
$page_title = "Marketplace – GameSense";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 0 3.5rem 0;">
    <div class="marketplace-layout">
        <!-- ====== LEFT SIDEBAR (FILTERS) ====== -->
        <aside class="marketplace-sidebar">
            <h2 class="sidebar-title">Filters</h2>
            <p class="sidebar-subtitle">Refine by specs and gear type.</p>

            <form method="get" class="sidebar-filters">
                <!--tag chips-->
                <div class="sidebar-section">
                    <h3 class="sidebar-section-title">Tags</h3>
                    <div class="tag-chip-group">
                        <?php foreach ($available_filters as $tag):
                            $selected = in_array($tag, $active_tags, true);
                        ?>
                            <label
                                class="tag-chip <?php echo $selected ? 'tag-chip--selected' : ''; ?>"
                                data-tag-chip>
                                <input
                                    type="checkbox"
                                    name="tags[]"
                                    value="<?php echo htmlspecialchars($tag); ?>"
                                    <?php echo $selected ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars(ucfirst($tag)); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!--price + stock-->
                <div class="sidebar-section" style="margin-top:1.5rem;">
                    <h3 class="sidebar-section-title">Price range</h3>
                    <div style="display:flex; gap:0.5rem;">
                        <input
                            type="number"
                            step="0.01"
                            name="min_price"
                            class="form-control"
                            placeholder="Min"
                            value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                        <input
                            type="number"
                            step="0.01"
                            name="max_price"
                            class="form-control"
                            placeholder="Max"
                            value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                    </div>
                    <label style="display:block; margin-top:0.6rem; font-size:0.8rem;">
                        <input
                            type="checkbox"
                            name="in_stock"
                            value="1"
                            <?php if ($onlyStock) echo 'checked'; ?>>
                        Only in stock
                    </label>
                </div>

                <!--sort dropdown-->
                <div class="sidebar-section" style="margin-top:1.5rem;">
                    <h3 class="sidebar-section-title">Sort</h3>
                    <select name="sort" class="form-control">
                        <option value="newest"     <?php if ($sort==='newest')     echo 'selected'; ?>>Newest</option>
                        <option value="price_asc"  <?php if ($sort==='price_asc')  echo 'selected'; ?>>Price: low to high</option>
                        <option value="price_desc" <?php if ($sort==='price_desc') echo 'selected'; ?>>Price: high to low</option>
                        <option value="popular"    <?php if ($sort==='popular')    echo 'selected'; ?>>Most viewed</option>
                    </select>
                </div>

                <!--apply/clear buttons-->
                <div class="sidebar-actions">
                    <button type="submit" class="btn">Apply filters</button>
                    <a href="marketplace.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </aside>

        <!-- ====== RIGHT COLUMN (RESULTS + SEARCH) ====== -->
        <main class="marketplace-main">
            <div class="marketplace-header-row">
                <div>
                    <h1 class="list-title">Marketplace</h1>
                    <p class="list-subtitle">
                        Browse all gaming gear listed by the community.
                    </p>
                    <p class="marketplace-meta">
                        <?php echo $totalItems; ?> items
                    </p>
                </div>

                <!--search bar that preserves current filters-->
                <form class="marketplace-search" method="get" action="marketplace.php">
                    <?php
                    //re-emit current filters as hidden inputs so search keeps them
                    foreach ($active_tags as $tag) {
                        echo '<input type="hidden" name="tags[]" value="' . htmlspecialchars($tag) . '">';
                    }
                    if ($minPrice !== null) {
                        echo '<input type="hidden" name="min_price" value="' . htmlspecialchars($minPrice) . '">';
                    }
                    if ($maxPrice !== null) {
                        echo '<input type="hidden" name="max_price" value="' . htmlspecialchars($maxPrice) . '">';
                    }
                    if ($onlyStock) {
                        echo '<input type="hidden" name="in_stock" value="1">';
                    }
                    echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sort) . '">';
                    ?>
                    <input
                        type="text"
                        name="q"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search name, tags, description…"
                        class="form-control marketplace-search-input">
                    <button type="submit" class="btn btn-secondary marketplace-search-btn">
                        Search
                    </button>
                </form>
            </div>

            <?php if ($totalItems > 0): ?>
                <!--product cards grid-->
                <div class="product-grid marketplace-grid">
                    <?php $index = 0; foreach ($products_page as $product_item): $index++; ?>
                        <?php
                        $inWishlist = isset($_SESSION['user_id'])
                            ? $wishlist->isInWishlist($_SESSION['user_id'], $product_item['id'])
                            : false;
                        ?>
                        <article
                            class="product-card-modern reveal-on-scroll"
                            style="transition-delay: <?php echo ($index * 40); ?>ms;">
                            <!--thumbnail with main_image / image / placeholder-->
                            <div class="product-media-wrapper">
                                <?php if (!empty($product_item['main_image'])): ?>
                                    <img
                                        src="/uploads/<?php echo htmlspecialchars($product_item['main_image']); ?>"
                                        alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                        class="product-image-modern">
                                <?php elseif (!empty($product_item['image'])): ?>
                                    <img
                                        src="/uploads/<?php echo htmlspecialchars($product_item['image']); ?>"
                                        alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                        class="product-image-modern">
                                <?php else: ?>
                                    <div class="product-image-modern product-image-placeholder">
                                        <span><?php echo strtoupper(substr($product_item['name'], 0, 2)); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!--card body: meta + actions-->
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
    <button type="submit" class="btn">Add to cart</button>
</form>

                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!--pagination controls-->
                <?php if ($totalPages > 1): ?>
                    <div class="marketplace-pagination">
                        <?php
                        $baseParams = $_GET;
                        unset($baseParams['page']);
                        $baseQuery = http_build_query($baseParams);
                        $baseUrl   = 'marketplace.php' . ($baseQuery ? ('?' . $baseQuery . '&') : '?');
                        ?>
                        <a href="<?php echo $baseUrl . 'page=' . max(1, $page - 1); ?>"
                           class="pagination-link <?php echo $page <= 1 ? 'pagination-link--disabled' : ''; ?>">
                            Prev
                        </a>

                        <span class="pagination-info">
                            Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                        </span>

                        <a href="<?php echo $baseUrl . 'page=' . min($totalPages, $page + 1); ?>"
                           class="pagination-link <?php echo $page >= $totalPages ? 'pagination-link--disabled' : ''; ?>">
                            Next
                        </a>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!--no results state-->
                <div class="alert alert-info">
                    <h3>No matching listings</h3>
                    <p>Try a different combination of tags or search terms.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!--js for interactive tag chips + scroll reveals-->
<script src="/js/cart-actions.js?v=<?php echo time(); ?>"></script>
<script src="/js/marketplace-filters.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<script src="/js/scroll-animations.js?v=<?php echo time(); ?>"></script>
