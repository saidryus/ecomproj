<?php
// ====== DEBUG SETTINGS (DEV ONLY) ======
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';

//page title for the shared header
$page_title = "List Item – E‑Commerce Store";
require_once __DIR__ . '/../includes/header.php';

// ====== STATIC OPTIONS (TAGS + CATEGORIES) ======

//tags users can pick as specs/features
$available_tags = [
    'mouse',
    'keyboard',
    'headset',
    'mousepad',
    'switches',
    'wireless',
    'wired',
    'accessory'
];

//main product categories for the dropdown
$available_categories = [
    'Mice',
    'Keyboards',
    'Headsets',
    'Mousepads',
    'Monitors',
    'Controllers',
    'Accessories'
];

// ====== AUTH GUARD (LOGIN REQUIRED) ======

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ====== SETUP MODEL + ERROR HOLDER ======

$product = new Product($db);
$errors  = [];

// ====== HANDLE FORM SUBMISSION ======

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //basic text fields
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');

    // ----- TAGS (MULTI-CHECKBOX) -----
    $selected_tags = $_POST['tags'] ?? [];
    if (!is_array($selected_tags)) {
        $selected_tags = [];
    }
    $selected_tags = array_map('trim', $selected_tags);
    $selected_tags = array_filter($selected_tags);
    //store tags as comma-separated string in db
    $tags = implode(', ', $selected_tags);

    //require at least one tag/spec
    if (empty($selected_tags)) {
        $errors[] = 'Please select at least one tag/spec.';
    }

    //long description + numeric fields
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $stock       = trim($_POST['stock'] ?? '');

    // ----- VALIDATION FOR BASIC FIELDS -----
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($category === '') {
        $errors[] = 'Category is required.';
    }
    if ($price === '' || !is_numeric($price) || $price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    if ($stock === '' || !ctype_digit($stock) || (int)$stock < 1) {
        $errors[] = 'New listings must have at least 1 in stock.';
    }

    // ----- MULTI-IMAGE VALIDATION (1–5 FILES) -----
    $images = $_FILES['images'] ?? null;

    if (!$images || empty($images['name'][0])) {
        $errors[] = 'Please upload at least one product image.';
    } else {
        $fileCount = 0;
        foreach ($images['name'] as $nameFile) {
            if ($nameFile === '') continue;
            $fileCount++;
        }
        if ($fileCount < 1 || $fileCount > 5) {
            $errors[] = 'You must upload between 1 and 5 images.';
        }
    }

    // ====== WHEN THERE ARE NO VALIDATION ERRORS ======
    if (empty($errors)) {
        $userId = (int) $_SESSION['user_id'];

        //wrap product + images in a single transaction
        $db->beginTransaction();

        //main image column is null; actual files go to product_images
        $newId = $product->create(
            $userId,
            $name,
            $category,
            $tags,
            $description,
            $price,
            $stock,
            null
        );

        if ($newId) {
            //make sure uploads directory exists
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            //loop through each uploaded file and move + record it
            $order = 0;
            foreach ($images['name'] as $idx => $origName) {
                if ($origName === '') continue;

                $ext      = pathinfo($origName, PATHINFO_EXTENSION);
                $safeName = 'prod_' . $newId . '_' . uniqid() . '.' . $ext;
                $target   = $uploadDir . $safeName;

                //move the file from temp folder to uploads folder
                if (!move_uploaded_file($images['tmp_name'][$idx], $target)) {
                    $errors[] = 'Failed to upload one of the images.';
                    break;
                }

                //save image record pointing to this product
                $stmtImg = $db->prepare(
                    "INSERT INTO product_images (product_id, filename, sort_order)
                     VALUES (:pid, :file, :ord)"
                );
                $stmtImg->execute([
                    ':pid'  => $newId,
                    ':file' => $safeName,
                    ':ord'  => $order++,
                ]);
            }
        } else {
            $errors[] = 'Something went wrong while saving the product.';
        }

        //commit or rollback depending on whether errors occurred
        if (empty($errors)) {
            $db->commit();
            header('Location: dashboard.php?message=created');
            exit;
        } else {
            $db->rollBack();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>List Item – E‑Commerce Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="list-page">
    <div class="container">
        <!--page title + intro text-->
        <div class="list-header reveal-on-scroll">
            <div>
                <h1 class="list-title">List a new product</h1>
                <p class="list-subtitle">
                    Share your item with the marketplace. Fill in the details below.
                </p>
            </div>
        </div>

        <!--validation errors (if any)-->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-info">
                <?php foreach ($errors as $msg): ?>
                    <div><?php echo htmlspecialchars($msg); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!--main listing form card-->
        <div class="list-form-card reveal-on-scroll">
            <form action="add_product.php" method="post" enctype="multipart/form-data">
                <!--product name-->
                <div class="form-group">
                    <label class="form-label" for="name">Product name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <!--category dropdown-->
                <div class="form-group">
                    <label class="form-label" for="category">Category</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">Select category…</option>
                        <?php
                        $selected_cat = $_POST['category'] ?? $product_data['category'];
                        foreach ($available_categories as $cat):
                            $sel = ($selected_cat === $cat) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $sel; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!--tags/specs multi-checkbox-->
                <div class="form-group">
                    <span class="form-label">Tags / specs</span>
                    <div style="display:flex; flex-wrap:wrap; gap:0.5rem; margin-top:0.4rem;">
                        <?php
                        $old_selected = $_POST['tags'] ?? [];
                        if (!is_array($old_selected)) {
                            $old_selected = [];
                        }
                        foreach ($available_tags as $tag):
                            $checked = in_array($tag, $old_selected, true) ? 'checked' : '';
                        ?>
                            <label style="font-size:0.8rem; color:#e5e7eb;">
                                <input
                                    type="checkbox"
                                    name="tags[]"
                                    value="<?php echo htmlspecialchars($tag); ?>"
                                    <?php echo $checked; ?>
                                    style="margin-right:0.3rem;">
                                <?php echo htmlspecialchars(ucfirst($tag)); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-help">
                        Pick all that apply (mouse, keyboard, wireless, switches, etc.).
                    </p>
                </div>

                <!--description textarea-->
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea
                        name="description"
                        id="description"
                        class="form-control"
                        rows="4"><?php
                        echo htmlspecialchars($_POST['description'] ?? '');
                    ?></textarea>
                </div>

                <!--price input-->
                <div class="form-group">
                    <label class="form-label" for="price">Price (PHP)</label>
                    <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        name="price"
                        id="price"
                        class="form-control"
                        value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                </div>

                <!--stock input-->
                <div class="form-group">
                    <label class="form-label" for="stock">Stock</label>
                    <input
                        type="number"
                        min="1"
                        name="stock"
                        id="stock"
                        class="form-control"
                        value="<?php echo htmlspecialchars($_POST['stock'] ?? '1'); ?>">
                </div>

                <!--multi-image upload field-->
                <div class="form-group">
                    <label class="form-label" for="images">Product images</label>
                    <input
                        type="file"
                        name="images[]"
                        id="images"
                        class="form-control"
                        accept="image/*"
                        multiple>
                    <p class="form-help">
                        Upload 1–5 images. First will be used as the main thumbnail.
                    </p>
                </div>

                <!--submit + cancel actions-->
                <div class="list-actions">
                    <button type="submit" class="btn">Publish item</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</main>

<!--scroll reveal animations for cards/sections-->
<script src="../js/scroll-animations.js?v=<?php echo time(); ?>"></script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
