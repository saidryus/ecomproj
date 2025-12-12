<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can edit user accounts from this panel
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== VALIDATE TARGET USER ID ======
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: users.php');
    exit;
}

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// ====== LOAD EXISTING USER DATA ======
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = :id LIMIT 1");
$stmt->bindParam(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If user does not exist, go back to list
if (!$user) {
    header('Location: users.php');
    exit;
}

// ====== HANDLE UPDATE SUBMIT ======
$errors  = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    // Basic required fields check; uniqueness can be handled separately if needed
    if ($username === '' || $email === '') {
        $errors[] = 'Username and email are required.';
    }

    if (empty($errors)) {
        $upd = $db->prepare(
            "UPDATE users
             SET username = :username, email = :email
             WHERE id = :id"
        );
        $upd->bindParam(':username', $username);
        $upd->bindParam(':email', $email);
        $upd->bindParam(':id', $id, PDO::PARAM_INT);
        $upd->execute();

        // Reflect new values in current page state
        $success          = 'User updated.';
        $user['username'] = $username;
        $user['email']    = $email;
    }
}

// ====== PAGE HEADER ======
$page_title = 'Edit user – Admin';
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <!-- Back button with animation -->
    <a href="users.php" class="btn btn-secondary reveal-on-scroll" style="margin-bottom:1.5rem;">
        ← Back to users
    </a>

    <!-- Edit form card with animation -->
    <div class="list-form-card reveal-on-scroll">
        <!-- Validation errors -->
        <?php if (!empty($errors)): ?>
            <div class="alert">
                <?php foreach ($errors as $e): ?>
                    <p><?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Success flash message -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Edit form for username + email -->
        <form method="post">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input
                    type="text"
                    name="username"
                    class="form-control"
                    value="<?php echo htmlspecialchars($user['username']); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($user['email']); ?>">
            </div>

            <div class="list-actions">
                <button type="submit" class="btn">Save changes</button>
            </div>
        </form>
    </div>
</main>
<script src="../../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
