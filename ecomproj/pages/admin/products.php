<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can moderate product visibility from this panel
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// ====== HANDLE VISIBILITY TOGGLE ACTION ======
// ?action=show|hide&id=123 toggles product approval flag
if (isset($_GET['action'], $_GET['id'])) {
    $id     = (int)$_GET['id'];
    $action = $_GET['action'];

    if ($id > 0) {
        if ($action === 'show') {
            $stmt = $db->prepare("UPDATE products SET is_approved = 1 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } elseif ($action === 'hide') {
            $stmt = $db->prepare("UPDATE products SET is_approved = 0 WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    // Always return to listing after an action
    header('Location: products.php');
    exit;
}

// ====== LOAD PRODUCTS + BASIC STATS FOR TABLE ======
$stmt = $db->query(
    "SELECT p.id, p.name, p.category, p.price, p.is_approved, p.views, p.sold,
            u.username
     FROM products p
     JOIN users u ON p.user_id = u.id
     ORDER BY p.created_at DESC"
);

// ====== PAGE HEADER ======
$page_title = 'Admin – Products';
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Products</h1>
            <p class="dashboard-subtitle">
                Hide or re-show products and see their stats.
            </p>
        </div>
    </div>

    <!-- Moderation table: visibility + basic metrics per product -->
    <div class="list-form-card reveal-on-scroll admin-products-card">
        <table class="admin-products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Views</th>
                    <th>Sold</th>
                    <th>Visibility</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo (int)$p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['username']); ?></td>
                        <td><?php echo htmlspecialchars($p['category']); ?></td>
                        <td>₱<?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo (int)$p['views']; ?></td>
                        <td><?php echo (int)$p['sold']; ?></td>
                        <td>
                            <span class="status-pill <?php echo $p['is_approved'] ? 'status-pill--success' : 'status-pill--danger'; ?>">
                                <?php echo $p['is_approved'] ? 'Visible' : 'Hidden'; ?>
                            </span>
                        </td>
                        <td class="admin-products-actions">
                            <?php if ($p['is_approved']): ?>
                                <a
                                    href="products.php?action=hide&id=<?php echo (int)$p['id']; ?>"
                                    class="btn-pill btn-pill--warning">
                                    Hide
                                </a>
                            <?php else: ?>
                                <a
                                    href="products.php?action=show&id=<?php echo (int)$p['id']; ?>"
                                    class="btn-pill btn-pill--primary">
                                    Show
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>
<script src="../../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
