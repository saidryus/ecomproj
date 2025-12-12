<?php
// ====== DEV ERROR DISPLAY (REMOVE IN PRODUCTION) ======
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ====== CORE INCLUDES ======
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Product.php';

//page title used by the shared header
$page_title = "Edit product – GameSense";
require_once __DIR__ . '/../includes/header.php';

// ====== STATIC OPTIONS (TAGS + CATEGORIES) ======

//available tags/specs to attach to a product
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

//main product categories
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

// ====== SETUP MODEL + OWNERSHIP CHECKS ======

$productModel = new Product($db);

$userId = (int)$_SESSION['user_id'];
$errors = [];

//product id from query string
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}

//only owner of the product can edit it
if (!$productModel->isOwner($id, $userId)) {
    header('Location: dashboard.php');
    exit;
}

//load existing product data for this id
$product_data = $productModel->readOne($id);
if (!$product_data) {
    header('Location: dashboard.php');
    exit;
}

//tags stored in db (comma separated) → array
$current_tags = [];
if (!empty($product_data['tags'])) {
    $current_tags = array_map('trim', explode(',', $product_data['tags']));
}

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
    $tags          = implode(', ', $selected_tags);

    //at least one tag required on edit too
    if (empty($selected_tags)) {
        $errors[] = 'Please select at least one tag/spec.';
    }

    //description + numeric fields
    $description = trim($_POST['description'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $stock       = trim($_POST['stock'] ?? '');

    //keep current image path by default
    $imagePath   = $product_data['image'];

    // ----- VALIDATION -----
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($category === '') {
        $errors[] = 'Category is required.';
    }
    if ($price === '' || !is_numeric($price) || $price < 0) {
        $errors[] = 'Price must be a valid non‑negative number.';
    }
    if ($stock === '' || !ctype_digit($stock) || (int)$stock < 0) {
        $errors[] = 'Stock must be a valid non‑negative integer.';
    }

    // ----- OPTIONAL NEW MAIN IMAGE (legacy single image) -----
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['image']['name']);
        $ext      = pathinfo($fileName, PATHINFO_EXTENSION);
        $safeName = 'prod_' . uniqid() . '.' . $ext;
        $target   = $uploadDir . $safeName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = $safeName;
        } else {
            $errors[] = 'Failed to upload new image.';
        }
    }

    // ====== PERFORM UPDATE WHEN NO ERRORS ======
    if (empty($errors)) {
        $ok = $productModel->update(
            $id,
            $name,
            $category,
            $tags,
            $description,
            $price,
            $stock,
            $imagePath
        );

        if ($ok) {
            header('Location: dashboard.php?message=updated');
            exit;
        } else {
            $errors[] = 'Something went wrong while updating the product.';
        }
    }
}
?>

<div class="container list-page">
    <!--header text for edit screen-->
    <div class="list-header">
        <div>
            <h1 class="list-title">Edit product</h1>
            <p class="list-subtitle">
                Update your listing details below.
            </p>
        </div>
    </div>

    <!--validation errors-->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-info">
            <?php foreach ($errors as $msg): ?>
                <div><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!--main edit form card-->
    <div class="list-form-card">
        <form
            action="edit_product.php?id=<?php echo $id; ?>"
            method="post"
            enctype="multipart/form-data"
        >
            <!--product name-->
            <div class="form-group">
                <label class="form-label" for="name">Product name</label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? $product_data['name']); ?>">
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
                        <option
                            value="<?php echo htmlspecialchars($cat); ?>"
                            <?php echo $sel; ?>>
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
                    //if posting back, prefer posted tags; otherwise use existing tags
                    $post_tags = $_POST['tags'] ?? null;
                    if ($post_tags !== null) {
                        $selected_for_form = is_array($post_tags) ? $post_tags : [];
                    } else {
                        $selected_for_form = $current_tags;
                    }

                    foreach ($available_tags as $tag):
                        $checked = in_array($tag, $selected_for_form, true) ? 'checked' : '';
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
                    echo htmlspecialchars($_POST['description'] ?? $product_data['description']);
                ?></textarea>
            </div>

            <!--price input-->
            <div class="form-group">
                <label class="form-label" for="price">Price (USD)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    name="price"
                    id="price"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['price'] ?? $product_data['price']); ?>">
            </div>

            <!--stock input-->
            <div class="form-group">
                <label class="form-label" for "stock">Stock</label>
                <input
                    type="number"
                    min="0"
                    name="stock"
                    id="stock"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['stock'] ?? $product_data['stock']); ?>">
            </div>

            <!--existing main image + upload for replacement/extra images-->
            <div class="form-group">
                <label class="form-label" for="image">Product image</label>
                <?php if (!empty($product_data['image'])): ?>
                    <div style="margin-bottom:0.5rem;">
                        <img
                            src="uploads/<?php echo htmlspecialchars($product_data['image']); ?>"
                            alt="<?php echo htmlspecialchars($product_data['name']); ?>"
                            style="max-width:160px;border-radius:0.75rem;">
                    </div>
                <?php endif; ?>
                <input
                    type="file"
                    name="images[]"
                    id="images"
                    class="form-control"
                    accept="image/*"
                    multiple>
                <p class="form-help">
                    You can upload additional images (total max 5). Existing ones stay unless removed.
                </p>
            </div>

            <!--save / cancel buttons-->
            <div class="list-actions">
                <button type="submit" class="btn">Save changes</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
