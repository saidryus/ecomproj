<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Message.php';

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ====== DB + MESSAGE MODEL SETUP ======
$database   = new Database();
$db         = $database->getConnection();
$messageObj = new Message($db);

//all messages current user has sent
$sentStmt = $messageObj->getSent($_SESSION['user_id']);

//page title for shared header
$page_title = 'Sent messages – GameSense';
require_once __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <!--header row with link back to inbox-->
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Sent messages</h1>
            <p class="dashboard-subtitle">
                Messages you have sent to other users.
            </p>
        </div>
        <a href="messages_inbox.php" class="btn btn-secondary">View inbox</a>
    </div>

    <div class="dashboard-products reveal-on-scroll">
        <?php if ($sentStmt->rowCount() === 0): ?>
            <!--empty state when there are no sent messages-->
            <div class="dashboard-empty-card reveal-on-scroll">
                <div class="dashboard-empty-text">
                    <p class="dashboard-empty-title">No sent messages.</p>
                    <p class="dashboard-empty-subtitle">
                        Start a conversation from a product page or inbox.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!--list of sent messages, reusing cart-item layout-->
            <div class="cart-items">
                <?php while ($m = $sentStmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <div class="cart-item-card">
                        <div class="cart-item-media">
                            <div class="cart-item-placeholder">
                                <?php echo strtoupper(substr($m['recipient_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div>
                            <div class="cart-item-title">
                                <?php echo htmlspecialchars($m['subject']); ?>
                            </div>
                            <div class="cart-item-meta">
                                To <?php echo htmlspecialchars($m['recipient_name']); ?>
                                · <?php echo htmlspecialchars($m['created_at']); ?>
                            </div>
                            <div class="product-actions-modern" style="margin-top:0.6rem;">
                                <a
                                    href="message_view.php?id=<?php echo (int)$m['id']; ?>"
                                    class="btn btn-secondary">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
