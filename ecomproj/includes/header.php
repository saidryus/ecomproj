<?php
//start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//pull in core config and classes used in the header
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/Cart.php';
require_once __DIR__ . '/../classes/Message.php';

//create shared db connection used for cart total and message badges
$database = new Database();
$db       = $database->getConnection();

//cart: make sure it exists and get current item count
Cart::init();
$cart_count = Cart::getCount();

//messages: unread count for the logged-in user (0 if guest)
$messageObj  = new Message($db);
$unreadCount = isset($_SESSION['user_id'])
    ? $messageObj->countUnread($_SESSION['user_id'])
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--page title falls back to a default if $page_title is not set-->
    <title><?php echo isset($page_title) ? $page_title : 'GameSense – Gaming Marketplace'; ?></title>

    <!--global stylesheet (from project root /css)-->
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
<header>
    <nav class="container">
        <!--brand/logo on the left-->
        <div class="logo">
            <a href="/pages/index.php" class="logo-link">
                <img
                    src="/uploads/gamesense_logo.png"
                    alt="GameSense"
                    class="logo-image">
            </a>
        </div>

        <!--main navigation links-->
        <ul class="nav-links">
            <li><a href="/pages/index.php">HOME</a></li>
            <li><a href="/pages/marketplace.php">MARKETPLACE</a></li>

            <!--cart link with item count badge-->
            <li>
                <a href="/pages/cart.php" class="nav-cart-link">
                    <span class="cart-icon" aria-hidden="true">&#128722;</span>
                    <span class="cart-count-badge"><?php echo $cart_count; ?></span>
                </a>
            </li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!--logged-in user dropdown-->
                <li class="nav-user">
                    <button type="button" class="nav-user-trigger" id="navUserTrigger">
                        <span class="nav-user-icon" aria-hidden="true">👤</span>
                        <span class="nav-user-name">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </span>
                        <span class="nav-user-caret">▾</span>
                    </button>

                    <!--user menu with dashboard, listings, inbox, etc.-->
                    <div class="nav-user-menu" id="navUserMenu">
                        <a href="/pages/dashboard.php">Dashboard</a>
                        <a href="/pages/add_product.php">List item</a>
                        <a href="/pages/messages_inbox.php">
                            Inbox<?php if ($unreadCount > 0): ?>
                                (<?php echo (int)$unreadCount; ?>)
                            <?php endif; ?>
                        </a>
                        <a href="/pages/wishlist.php">Saved items</a>

                        <!--extra admin links when current user is an admin-->
                        <?php if (!empty($_SESSION['is_admin'])): ?>
                            <a href="/pages/admin/users.php">Users</a>
                            <a href="/pages/admin/products.php">Products</a>
                            <a href="/pages/admin/reports.php">Reports</a>
                        <?php endif; ?>

                        <a href="/pages/logout.php">Log out</a>
                    </div>
                </li>
            <?php else: ?>
                <!--guest links-->
                <li><a href="/pages/login.php">LOG IN</a></li>
                <li><a href="/pages/signup.php">REGISTER</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
