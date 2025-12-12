<?php

class Wishlist
{
    // ====== CORE PROPERTIES ======

    //shared database connection (pdo)
    private $conn;

    //wishlist table name
    private $table = "wishlists";

    //save db connection for later use
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ====== ADD / REMOVE ITEMS ======

    //add product to a user's wishlist (ignores duplicates)
    public function add($userId, $productId)
    {
        //force ids to integer to avoid weird values
        $userId    = (int)$userId;
        $productId = (int)$productId;

        $sql = "INSERT IGNORE INTO {$this->table} (user_id, product_id)
                VALUES (:uid, :pid)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);

        //returns true if the insert ran without error
        return $stmt->execute();
    }

    //remove product from a user's wishlist
    public function remove($userId, $productId)
    {
        //cast ids to int for safety
        $userId    = (int)$userId;
        $productId = (int)$productId;

        $sql = "DELETE FROM {$this->table}
                WHERE user_id = :uid AND product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ====== FETCH WISHLIST CONTENT ======

    //get all wishlist items for one user (includes product details + main image)
    public function forUser($userId)
    {
        $userId = (int)$userId;

        $sql = "SELECT p.*,
                       u.username,
                       (SELECT filename FROM product_images pi
                        WHERE pi.product_id = p.id
                        ORDER BY pi.sort_order ASC, pi.id ASC
                        LIMIT 1) AS main_image
                FROM {$this->table} w
                JOIN products p ON w.product_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE w.user_id = :uid
                ORDER BY w.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        //caller loops over $stmt->fetch(...)
        return $stmt;
    }

    // ====== CHECK MEMBERSHIP ======

    //check if a specific product is already in a user's wishlist
    public function isInWishlist($userId, $productId)
    {
        //normalize ids
        $userId    = (int)$userId;
        $productId = (int)$productId;

        $sql = "SELECT COUNT(*) AS c
                FROM {$this->table}
                WHERE user_id = :uid AND product_id = :pid";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        //true if at least one matching row exists
        return !empty($row['c']) && (int)$row['c'] > 0;
    }
}
?>
