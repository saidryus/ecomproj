<?php

class Review
{
    // Shared PDO database connection
    private $conn;

    // Table name that stores product reviews
    private $table = "product_reviews";

    // Save DB connection for all methods
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ====== CREATE / UPDATE REVIEW ======

    // Create a new review or update an existing one for a product from a user
    public function addOrUpdate($productId, $userId, $rating, $comment)
    {
        // Normalize input values (types and ranges)
        $productId = (int)$productId;
        $userId    = (int)$userId;
        // Keep rating between 1 and 5
        $rating    = max(1, min(5, (int)$rating));
        // Trim whitespace and ensure it's a string
        $comment   = trim((string)$comment);

        // Insert review or update the existing one (same product+user pair)
        // Requires a UNIQUE constraint on (product_id, user_id)
        $sql = "INSERT INTO {$this->table} (product_id, user_id, rating, comment)
                VALUES (:pid, :uid, :rating, :comment)
                ON DUPLICATE KEY UPDATE
                    rating     = VALUES(rating),
                    comment    = VALUES(comment),
                    created_at = CURRENT_TIMESTAMP";

        // Prepare statement
        $stmt = $this->conn->prepare($sql);

        // Bind parameters
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
        $stmt->bindParam(':comment', $comment);

        // Return true if query succeeds, false otherwise
        return $stmt->execute();
    }

    // ====== READ REVIEWS FOR ONE PRODUCT ======

    // Get all reviews for a single product (with reviewer username)
    public function getForProduct($productId)
    {
        // Cast product id to int for safety
        $productId = (int)$productId;

        // Fetch reviews plus the username of the reviewer
        $sql = "SELECT r.*, u.username
                FROM {$this->table} r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = :pid
                ORDER BY r.created_at DESC";

        // Prepare and run
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();

        // Caller will loop over $stmt->fetch(...)
        return $stmt;
    }

    // ====== REVIEW STATS (COUNT + AVERAGE) ======

    // Get total review count and average rating for a product
    public function getStats($productId)
    {
        // Cast product id
        $productId = (int)$productId;

        // Aggregate stats for this product
        $sql = "SELECT
                    COUNT(*)    AS total_reviews,
                    AVG(rating) AS avg_rating
                FROM {$this->table}
                WHERE product_id = :pid";

        // Prepare and run
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // No reviews case: return zeros
        if (!$row || !$row['total_reviews']) {
            return ['total' => 0, 'avg' => 0];
        }

        // Return integer total and 1‑decimal average rating
        return [
            'total' => (int)$row['total_reviews'],
            'avg'   => round((float)$row['avg_rating'], 1)
        ];
    }

    // ====== PURCHASE CHECK (CAN USER REVIEW?) ======

    // Check if a user has ever purchased this product
    // Used to decide if they are allowed to leave a review
    public function userHasPurchased($productId, $userId)
    {
        // Normalize ids
        $productId = (int)$productId;
        $userId    = (int)$userId;

        // Join orders and order_items to confirm at least one matching purchase
        $sql = "SELECT COUNT(*) AS c
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.product_id = :pid
                  AND o.user_id = :uid";

        // Prepare and run
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // True if at least one matching purchase exists
        return !empty($row['c']) && (int)$row['c'] > 0;
    }
}
