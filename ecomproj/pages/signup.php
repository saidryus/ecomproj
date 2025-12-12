<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

// Page title used by shared header layout
$page_title = "Create account – GameSense";
require_once __DIR__ . '/../includes/header.php';

// ====== DB + USER MODEL SETUP ======
$database = new Database();
$db       = $database->getConnection();
$user     = new User($db);

// Feedback messages for the UI
$error   = '';
$success = '';

// ====== HANDLE SIGNUP SUBMIT ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username         = $_POST['username'] ?? '';
    $email            = $_POST['email'] ?? '';
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Simple confirmation check before hitting the model
    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Delegate validation/uniqueness checks to User::register
        if ($user->register($username, $email, $password)) {
            $success = "Account created successfully. You can now log in.";
        } else {
            $error = "Unable to create account. Email might already be in use, or fields were invalid.";
        }
    }
}
?>

<div class="container auth-page">
    <div class="auth-wrapper">
        <!-- Left side: marketing copy for new users -->
        <div class="auth-hero">
            <p class="auth-kicker">ACCOUNT</p>
            <h1 class="auth-title">Join the marketplace.</h1>
            <p class="auth-subtitle">
                List your gear, reserve drops and keep your setup synced across
                devices with a single account.
            </p>
            <div class="auth-badge-row">
                <span class="auth-badge">List items</span>
                <span class="auth-badge">Secure checkout</span>
                <span class="auth-badge">Track orders</span>
            </div>
        </div>

        <!-- Right side: actual signup form -->
        <div class="auth-card">
            <div class="auth-card-header">
                <h2 class="auth-card-title">Create your account</h2>
                <p class="auth-card-subtitle">It only takes a minute.</p>
            </div>

            <!-- Error and success feedback -->
            <?php if ($error): ?>
                <div class="alert alert-info" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Signup form -->
            <form method="POST" action="signup.php">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required>
                    <p class="form-help">
                        Use at least 8 characters with letters and numbers.
                    </p>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-control"
                        required>
                </div>

                <div class="auth-actions">
                    <button type="submit" class="btn" style="width: 100%;">
                        Create account
                    </button>
                    <p class="auth-extra">
                        Already have an account?
                        <a href="login.php">Log in instead</a>
                    </p>
                </div>

                <p class="auth-meta-row">
                    By creating an account, you agree to the GameSense terms and
                    confirm that you are at least 18 years old.
                </p>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
