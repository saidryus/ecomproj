<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Message.php';

// ====== AUTH GUARD (LOGIN REQUIRED) ======
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ====== VALIDATE MESSAGE ID ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: messages_inbox.php");
    exit;
}

// ====== DB + MESSAGE MODEL SETUP ======
$dbObj      = new Database();
$db         = $dbObj->getConnection();
$messageObj = new Message($db);

//fetch message only if current user is sender or recipient
$message = $messageObj->getByIdForUser($id, $_SESSION['user_id']);
if (!$message) {
    header("Location: messages_inbox.php");
    exit;
}

//mark as read when user is the recipient
if ((int)$message['recipient_id'] === (int)$_SESSION['user_id']) {
    $messageObj->markAsRead($id, $_SESSION['user_id']);
}

//figure out if this is incoming or sent message for label text
$isIncoming    = ((int)$message['recipient_id'] === (int)$_SESSION['user_id']);
$otherUserName = $isIncoming ? $message['sender_name'] : $message['recipient_name'];

//prepare reply subject (avoid stacking multiple "Re: ")
$replySubject = strpos($message['subject'], 'Re: ') === 0
    ? $message['subject']
    : 'Re: ' . $message['subject'];

//id of the person we reply to
$replyToId = $isIncoming ? (int)$message['sender_id'] : (int)$message['recipient_id'];

//shared header (uses existing layout)
include __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <h1 class="section-heading reveal-on-scroll">Message</h1>

    <div class="list-form-card reveal-on-scroll">
        <p class="product-details-meta">
            <strong>Subject:</strong>
            <?php echo htmlspecialchars($message['subject']); ?>
        </p>
        <p class="product-details-meta" style="margin-top:0.3rem;">
            <strong><?php echo $isIncoming ? 'From' : 'To'; ?>:</strong>
            <?php echo htmlspecialchars($otherUserName); ?>
        </p>
        <p class="product-details-meta" style="margin-top:0.3rem;">
            <strong>Sent:</strong>
            <?php echo htmlspecialchars($message['created_at']); ?>
        </p>

        <hr style="border-color:#1f2937; margin:1.3rem 0;">

        <!--main message body-->
        <div class="product-details-description">
            <?php echo nl2br(htmlspecialchars($message['body'])); ?>
        </div>

        <!--reply + back buttons-->
        <div class="list-actions" style="margin-top:1.5rem;">
            <a
                href="message_send.php?to=<?php echo $replyToId; ?>&subject=<?php echo urlencode($replySubject); ?>"
                class="btn">
                Reply
            </a>
            <a href="messages_inbox.php" class="btn btn-secondary">Back to inbox</a>
        </div>
    </div>
</main>
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
