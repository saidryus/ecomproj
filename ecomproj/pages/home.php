<?php
session_start();

// ====== REDIRECT LOGGED-IN USERS ======

//if user is already logged in, send them to main home/index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// ====== PAGE METADATA + HEADER ======

$page_title = "Welcome to GameSense";
require_once __DIR__ . '/../includes/header.php';
?>

<main class="guest-hero">
    <div class="container">
        <!--two-column hero: copy on the left, auth card on the right-->
        <section class="hero-modern">
            <!--intro text column-->
            <div class="hero-modern-text">
                <p class="hero-kicker">Gaming marketplace</p>
                <h1 class="hero-title">Welcome to GameSense</h1>
                <p class="hero-subtitle">
                    Buy and sell gaming gear from other players. Create an account
                    to list your own products and build your setup.
                </p>

                <!--small strip of “feature” pills under the hero text-->
                <div class="feature-strip">
                    <div class="feature-pill">
                        <span class="feature-label">Browse</span>
                        <span class="feature-value">Mice & keyboards</span>
                    </div>
                    <div class="feature-pill">
                        <span class="feature-label">Discover</span>
                        <span class="feature-value">Headsets & more</span>
                    </div>
                </div>
            </div>

            <!--auth card column with login/register ctas-->
            <div class="hero-modern-media">
                <div class="auth-card guest-hero-card">
                    <div class="auth-card-header">
                        <h2 class="auth-card-title">Get started</h2>
                        <p class="auth-card-subtitle">
                            Sign in or create a free account.
                        </p>
                    </div>
                    <div class="auth-actions">
                        <a href="login.php" class="btn hero-primary-cta">Log in</a>
                        <a href="signup.php" class="btn btn-secondary">Create account</a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</main>

<!--hero entrance animation script-->
<script src="../js/home-hero.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
