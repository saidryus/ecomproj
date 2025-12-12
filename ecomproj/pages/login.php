<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';

//page title used by shared header
$page_title = "Log in – GameSense";
require_once __DIR__ . '/../includes/header.php';

// ====== SETUP DB + USER MODEL ======
$database = new Database();
$db       = $database->getConnection();
$user     = new User($db);

//holds any login error message
$error = '';

// ====== HANDLE LOGIN SUBMIT ======
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    //try to log user in via user class
    if ($user->login($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<div class="container auth-page">
    <div class="auth-wrapper">
        <!--left column: small marketing copy for login-->
        <div class="auth-hero">
            <p class="auth-kicker">ACCOUNT</p>
            <h1 class="auth-title">Sign back in.</h1>
            <p class="auth-subtitle">
                Access your dashboard, manage listings and track your reservations in one place.
            </p>
            <div class="auth-badge-row">
                <span class="auth-badge">Secure login</span>
                <span class="auth-badge">Creator tools</span>
                <span class="auth-badge">No ads</span>
            </div>
        </div>

        <!--right column: actual login form-->
        <div class="auth-card">
            <div class="auth-card-header">
                <h2 class="auth-card-title">Welcome back</h2>
                <p class="auth-card-subtitle">
                    Use your GameSense account credentials.
                </p>
            </div>

            <!--error message when credentials fail-->
            <?php if ($error): ?>
                <div class="alert alert-info" style="margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!--login form-->
            <form method="POST" action="login.php">
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
                </div>

                <div class="auth-actions">
                    <button type="submit" class="btn" style="width: 100%;">Log in</button>
                    <p class="auth-extra">
                        New here?
                        <a href="signup.php">Create an account</a>
                    </p>
                </div>

                <p class="auth-meta-row">
                    By continuing, you agree to the GameSense terms and acknowledge the privacy policy.
                </p>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
