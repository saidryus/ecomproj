<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../classes/Database.php';

// ====== AUTH GUARD (ADMIN ONLY) ======
// Only admins can view and manage user accounts
if (empty($_SESSION['is_admin'])) {
    header('Location: ../login.php');
    exit;
}

// ====== DB CONNECTION ======
$database = new Database();
$db       = $database->getConnection();

// Fetch all users, including soft-deleted ones, oldest first
$stmt = $db->query(
    "SELECT id, username, email, created_at, is_deleted 
     FROM users 
     ORDER BY id ASC"
);

// ====== PAGE HEADER ======
$page_title = 'Admin – Users';
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="container" style="padding-top:3rem; padding-bottom:4rem;">
    <!-- Header row: title + quick "Add user" action -->
    <div class="dashboard-header-row reveal-on-scroll">
        <div>
            <h1 class="dashboard-title">Users</h1>
            <p class="dashboard-subtitle">
                Manage all registered accounts in the system.
            </p>
        </div>
        <a href="../signup.php" class="btn">Add user</a>
    </div>

    <!-- Users table card -->
    <div class="list-form-card reveal-on-scroll admin-users-card">
        <table class="admin-users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th>Status</th>
                    <th style="text-align:right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                        <td>
                            <!-- Status pill reflects soft-delete flag -->
                            <span class="status-pill <?php echo $u['is_deleted'] ? 'status-pill--danger' : 'status-pill--success'; ?>">
                                <?php echo $u['is_deleted'] ? 'Deleted' : 'Active'; ?>
                            </span>
                        </td>
                        <td class="admin-users-actions">
                            <!-- Edit user -->
                            <a
                                href="edit_user.php?id=<?php echo (int)$u['id']; ?>"
                                class="btn-pill btn-pill--ghost">
                                Edit
                            </a>

                            <!-- Delete or restore depending on current state -->
                            <?php if (!$u['is_deleted']): ?>
                                <a
                                    href="delete_user.php?id=<?php echo (int)$u['id']; ?>"
                                    class="btn-pill btn-pill--danger"
                                    onclick="return confirm('Remove this user? This cannot be undone.');">
                                    Remove
                                </a>
                            <?php else: ?>
                                <a
                                    href="restore_user.php?id=<?php echo (int)$u['id']; ?>"
                                    class="btn-pill btn-pill--primary">
                                    Restore
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
