<?php
// ====== INBOX LIST (RECEIVED MESSAGES) ======
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Message.php';

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ====== DB + MESSAGE MODEL SETUP ======
$dbObj      = new Database();
$db         = $dbObj->getConnection();
$messageObj = new Message($db);

//all messages where current user is recipient
$inboxStmt = $messageObj->getInbox($_SESSION['user_id']);

//shared header (nav, css, etc.)
include __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <!--page title + link to sent messages-->
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Inbox</h1>
            <p class="dashboard-subtitle">Private messages sent to you.</p>
        </div>
        <a href="messages_sent.php" class="btn btn-secondary">View sent</a>
    </div>

    <div class="dashboard-products reveal-on-scroll">
        <?php if ($inboxStmt->rowCount() === 0): ?>
            <!--empty state when there are no messages-->
            <div class="dashboard-empty-card">
                <div class="dashboard-empty-text">
                    <p class="dashboard-empty-title">No messages yet.</p>
                    <p class="dashboard-empty-subtitle">
                        When other users contact you, they will appear here.
                    </p>
                </div>
            </div>
        <?php else: ?>
            <!--list of inbox items, styled using cart-item card layout-->
            <div class="cart-items">
                <?php while ($m = $inboxStmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <?php $isUnread = (int)$m['is_read'] === 0; ?>
                    <div class="cart-item-card inbox-item<?php echo $isUnread ? ' inbox-item--unread' : ''; ?>">
                        <div class="cart-item-media">
                            <div class="cart-item-placeholder">
                                <?php echo strtoupper(substr($m['sender_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <div>
                            <div class="cart-item-title">
                                <?php echo htmlspecialchars($m['subject']); ?>
                                <?php if ($isUnread): ?>
                                    <span class="cart-item-qty">• New</span>
                                <?php endif; ?>
                            </div>
                            <div class="cart-item-meta">
                                From <?php echo htmlspecialchars($m['sender_name']); ?>
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
<?php include __DIR__ . '/../includes/footer.php'; ?>
