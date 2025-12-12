<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
$dbObj      = new Database();
$db         = $dbObj->getConnection();
$messageObj = new Message($db);

$errors  = [];
$success = null;

//prefilled recipient + subject when coming from product page
$recipient_id    = isset($_GET['to']) ? (int)$_GET['to'] : 0;
$subject_prefill = isset($_GET['subject']) ? trim($_GET['subject']) : '';

// ====== HANDLE FORM SUBMISSION ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = (int)($_POST['recipient_id'] ?? 0);
    $subject      = trim($_POST['subject'] ?? '');
    $body         = trim($_POST['body'] ?? '');

    //basic validation
    if ($recipient_id <= 0) {
        $errors[] = "Recipient is required.";
    }
    if ($subject === '') {
        $errors[] = "Subject is required.";
    }
    if ($body === '') {
        $errors[] = "Message body is required.";
    }

    //send message when inputs are valid
    if (empty($errors)) {
        $ok = $messageObj->send($_SESSION['user_id'], $recipient_id, $subject, $body);
        if ($ok) {
            $success        = "Message sent.";
            $subject_prefill = '';
            $body           = '';
        } else {
            $errors[] = "Failed to send message.";
        }
    }
}

// ====== LOAD RECIPIENT NAME FOR UI ======
$recipient_name = 'User';
if ($recipient_id > 0) {
    $stmt = $db->prepare("SELECT username FROM users WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $recipient_id, PDO::PARAM_INT);
    $stmt->execute();
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recipient_name = $row['username'];
    }
}

//shared header (uses $page_title if you set it above)
include __DIR__ . '/../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <h1 class="section-heading reveal-on-scroll">Send Message</h1>

    <!--validation errors-->
    <?php if (!empty($errors)): ?>
        <div class="alert reveal-on-scroll">
            <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!--success notice-->
    <?php if ($success): ?>
        <div class="alert alert-success reveal-on-scroll">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!--message form card-->
    <div class="list-form-card reveal-on-scroll">
        <form method="post">
            <!--hidden id for the actual recipient-->
            <input type="hidden" name="recipient_id" value="<?php echo (int)$recipient_id; ?>">

            <div class="form-group">
                <label class="form-label">To</label>
                <input
                    type="text"
                    class="form-control"
                    value="<?php echo htmlspecialchars($recipient_name); ?>"
                    disabled>
            </div>

            <div class="form-group">
                <label class="form-label">Subject</label>
                <input
                    type="text"
                    name="subject"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['subject'] ?? $subject_prefill); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea
                    name="body"
                    rows="6"
                    class="form-control"><?php
                    echo htmlspecialchars($_POST['body'] ?? '');
                ?></textarea>
            </div>

            <div class="list-actions">
                <button type="submit" class="btn">Send message</button>
                <a href="messages_inbox.php" class="btn btn-secondary">Back to inbox</a>
            </div>
        </form>
    </div>
</main>
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
