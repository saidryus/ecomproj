<?php
class Product
{
    // PDO connection object
    private $conn;

    // Table name used for all queries
    private $table_name = "products";

    // Public properties that mirror columns (optional, handy for some patterns)
    public $id;
    public $user_id;
    public $name;
    public $category;
    public $tags;        // Comma‑separated tags/specs
    public $description;
    public $price;
    public $stock;
    public $image;       // Legacy single image column (main image)

    // Constructor: expect a PDO connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // ====== LIST / MARKETPLACE ======

    // Simple wrapper for readAll (kept for backwards compatibility)
    public function read()
    {
        return $this->readAll();
    }

    // Get ALL products (for marketplace/index)
    // Joins users so each row also has the seller username
    public function readAll()
    {
        $query = "SELECT p.*, u.username
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // Caller will loop over $stmt->fetch(...)
        return $stmt;
    }

    // ====== DASHBOARD (USER PRODUCTS) ======

    // Get all products for a specific user (seller dashboard)
    // Also pulls the first image from product_images as main_image
    public function readByUser($userId)
    {
        $userId = (int) $userId;

        $query = "SELECT p.*,
                         (SELECT filename FROM product_images pi
                          WHERE pi.product_id = p.id
                          ORDER BY pi.sort_order ASC, pi.id ASC
                          LIMIT 1) AS main_image
                  FROM " . $this->table_name . " p
                  WHERE p.user_id = :user_id
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // ====== SINGLE PRODUCT ======

    // Fetch a single product by id, including seller username
    public function readOne($id)
    {
        $id = (int) $id;

        $query = "SELECT p.*, u.username
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.id = :id
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        // Return associative array or null if not found
        return $row ? $row : null;
    }

    // Convenience alias
    public function getById($id)
    {
        return $this->readOne($id);
    }

    // ====== CREATE ======

    // Insert a new product row and return its new ID (or false on failure)
    public function create($userId, $name, $category, $tags, $description, $price, $stock, $imagePath = null)
    {
        $query = "INSERT INTO " . $this->table_name . "
                  (user_id, name, category, tags, description, price, stock, image, created_at)
                  VALUES
                  (:user_id, :name, :category, :tags, :description, :price, :stock, :image, NOW())";

        $stmt = $this->conn->prepare($query);

        // Basic sanitization/typing before binding
        $userId   = (int) $userId;
        $name     = trim($name);
        $category = trim($category);
        $tags     = trim($tags);          // already comma‑separated
        $desc     = trim($description);
        $price    = (float) $price;
        $stock    = (int) $stock;
        $image    = $imagePath ? trim($imagePath) : null;

        // Bind parameters to placeholders
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':description', $desc);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':image', $image);

        if ($stmt->execute()) {
            // Return the auto‑incremented ID of the new product
            return $this->conn->lastInsertId();
        }

        return false;
    }

    // ====== UPDATE ======

    // Update an existing product row
    // $imagePath can be null to keep/remove image depending on caller logic
    public function update($id, $name, $category, $tags, $description, $price, $stock, $imagePath = null)
    {
        $id = (int) $id;

        $query = "UPDATE " . $this->table_name . "
                     SET name        = :name,
                         category    = :category,
                         tags        = :tags,
                         description = :description,
                         price       = :price,
                         stock       = :stock,
                         image       = :image
                   WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Basic sanitization/typing
        $name     = trim($name);
        $category = trim($category);
        $tags     = trim($tags);
        $desc     = trim($description);
        $price    = (float) $price;
        $stock    = (int) $stock;
        $image    = $imagePath ? trim($imagePath) : null;

        // Bind values
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':description', $desc);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
        $stmt->bindParam(':image', $image);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ====== DELETE ======

    // Permanently remove a product by id
    public function delete($id)
    {
        $id = (int) $id;

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // ====== OWNERSHIP CHECK ======

    // Check if a given user is the owner of a product
    // Useful for guarding edit/delete actions
    public function isOwner($productId, $userId)
    {
        $productId = (int) $productId;
        $userId    = (int) $userId;

        $query = "SELECT COUNT(*) AS cnt
                  FROM " . $this->table_name . "
                  WHERE id = :pid
                    AND user_id = :uid";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['cnt']) && (int)$row['cnt'] > 0;
    }

    // ====== SEARCH ======

    // Full‑text style search across name, category, tags, description
    public function search($term)
    {
        // Add wildcards for LIKE query
        $like = '%' . $term . '%';

        $query = "SELECT p.*, u.username
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.user_id = u.id
                  WHERE p.name        LIKE :term
                     OR p.category    LIKE :term
                     OR p.tags        LIKE :term
                     OR p.description LIKE :term
                  ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':term', $like, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt;
    }
}
?>
