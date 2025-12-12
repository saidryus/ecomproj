# GameSense Marketplace – Technical Overview

This document describes the architecture, core models, calculations, and code flows of the GameSense marketplace project.

---

## 1. High‑level architecture

**Language and stack:**

- PHP (procedural controllers + OOP models).
- MySQL database accessed via PDO.
- Sessions for:
  - Login state.
  - Cart contents.

**Structure:**

- Models (business logic) in `classes/`.
- Controllers/views as individual `.php` pages.
- Shared layout in `includes/header.php` and `includes/footer.php`.
- Admin tools under `admin/`.
- CSS and JS in top‑level asset files like `style.css`, `scroll-animations.js`, etc.

---

## 2. Core model classes in `classes/`

### Database.php

**Purpose:**

- Encapsulates PDO connection setup.

**Typical usage in pages:**
- `$database = new Database();`
- `$db = $database->getConnection();`

**Behavior:**

- Uses configured host, db name, username, password.
- Enables exceptions on error.
- Sets default fetch mode to associative arrays.

---

### User.php

**Table:**

- `users` with fields like: `id`, `username`, `email`, `password_hash`, `is_admin`, `is_deleted`, `created_at`.

**Key responsibilities:**

- Register new users.
- Log users in and set session data.

**Typical methods:**

`register($username, $email, $password):`

- Validates non‑empty fields and unique email.
- Hashes password via `password_hash`.
- Inserts row into `users`.
- Returns `true` on success, `false` if email already in use or validation fails.

`login($email, $password):`

- Fetches user by email.
- Uses `password_verify` to compare given password with `password_hash`.
- On success:
  - Sets `$_SESSION['user_id']`, `$_SESSION['username']`, and possibly `$_SESSION['is_admin']`.
  - Returns `true`.
- On failure:
  - Returns `false`.

---

### Product.php

**Table:**

- `products` with fields like: `id`, `user_id`, `name`, `category`, `tags` (comma string), `description`, `price`, `stock`, `image` (legacy main image), `views`, `sold`, `is_approved`, `created_at`.

**Responsibilities:**

- Create, read, update, delete product records.
- Confirm ownership.

**Main methods:**

`create($userId, $name, $category, $tags, $description, $price, $stock, $image):`

- Inserts a new product row.
- Returns the new `id`.

`update($id, $userId, ...):`

- Updates product fields for the given `id`.
- Often requires that `user_id` matches or that caller checked ownership.

`delete($id):`

- Deletes a product row by `id`.

`readByUser($userId):`

- Returns all products where `user_id = :userId`.

`readOne($id)` / `getById($id):`

- Returns a single product row.
- Some variants may join `users` to include seller username.

`isOwner($productId, $userId):`

- Returns `true` if a row exists with given `id` and `user_id`.

---

### Cart.php

**Storage:**

- Cart data is not stored in the database.
- Instead, `$_SESSION['cart']` is used and looks like:
    - `[ productId => quantity, ... ];`

**Public methods:**

`init():`

- Ensures `$_SESSION['cart']` exists and is an array.

`add($productId, $qty):`

- Casts inputs to integers.
- Ignores if `$qty <= 0`.
- If product already in cart:
- Increases quantity by `$qty`.
- Else:
- Creates a new entry with quantity `$qty`.

`decrease($productId, $qty):`

- Decreases the quantity for that product.
- If new quantity ≤ 0, removes it from the cart.

`remove($productId):`

- Removes that product entry from cart entirely.

`clear():`

- Empties the whole cart: `$_SESSION['cart'] = []`.

`getItems():`

- Returns the current cart map (or an empty array if none).

---

### Wishlist.php

**Table:**

- `wishlist` with: `id`, `user_id`, `product_id`, `created_at`.

**Characteristics:**

- Persisted in DB per user.
- One record per user/product pair (enforced by code or a unique key).

**Methods:**

`add($userId, $productId):`

- Inserts `(user_id, product_id)` if not already present.

`remove($userId, $productId):`

- Deletes the row for that combination.

`isInWishlist($userId, $productId):`

- Checks if an entry exists; returns boolean.

`forUser($userId):`

- Returns all wishlist items for the user joined with product data:
- Product `name`, `category`, `tags`, `price`, and some primary image (e.g. main image using `product_images`).

---

### Review.php

**Table:**

- `reviews` with: `id`, `product_id`, `user_id`, `rating`, `comment`, `created_at`.
- DB has a UNIQUE constraint on `(product_id, user_id)` so one review per user per product.

**Methods:**

`addOrUpdate($productId, $userId, $rating, $comment):`

- Casts `productId` and `userId` to integers.
- Clamps `rating` between 1 and 5.
- Trims the comment.
- Uses:
    - INSERT ... ON DUPLICATE KEY UPDATE
    - rating = VALUES(rating),
    - comment = VALUES(comment),
    - created_at = CURRENT_TIMESTAMP

- So if the same user reviews again, rating and comment are updated rather than new row added.

`getForProduct($productId):`

- Selects all reviews for given product, joined with `users` to get the reviewer’s username.
- Orders by `created_at DESC`.
- Returns a PDO statement; the caller loops `fetch()` to display.

`getStats($productId):`

- Aggregates:
- `total_reviews = COUNT(*)`
- `avg_rating = AVG(rating)`
- If no rows:
- Returns `total = 0`, `avg = 0`.
- Else:
- Returns integer total and average rating rounded to 1 decimal.

`userHasPurchased($productId, $userId):`

- Joins `orders` and `order_items`:
- Checks that there is at least one order owned by `userId` including `productId`.
- Returns `true` if count > 0.
- Used to gate whether a user may leave a review.

---

### Message.php

**Table:**

- `messages` with: `id`, `sender_id`, `recipient_id`, `subject`, `body`, `is_read`, `created_at`.

**Methods:**

`send($fromId, $toId, $subject, $body):`

- Inserts a new row with `is_read = 0`.

`getInbox($userId):`

- Selects all messages where `recipient_id = :userId`.
- Joins `users` to get sender’s username.
- Sorts by `created_at DESC`.

`getSent($userId):`

- Similar but uses `sender_id = :userId` and joins recipient usernames.

`getByIdForUser($messageId, $userId):`

- Returns a message only if `userId` is either the sender or the recipient.
- Protects from viewing others’ messages.

`markAsRead($messageId, $userId):`

- Updates `is_read` to 1, restricted so only recipient can flip that flag.

---

## 3. Shared layout and styling

### includes/header.php

- Reads a `$page_title` variable set in each page (e.g., `"Saved items – GameSense"`).

**Outputs HTML `<head>`:**

- `<title>` includes `$page_title`.
- Links to `style.css`.

**Outputs site header:**

- Logo.
- Navigation:
- For guests: links to `home.php`, `login.php`, `signup.php`.
- For logged‑in users:
  - Links to `index.php`, `marketplace.php`, `dashboard.php`, `wishlist.php`, `messages_inbox.php`, cart icon, `logout.php`.
- For admins:
  - Additional “Admin” or direct links into `admin/users.php`, `admin/products.php`, etc.

### includes/footer.php

- Closes main container markup.

**Includes JS files:**

- `scroll-animations.js` – reveals elements on scroll.
- `home-hero.js` – hero animation.
- `marketplace-filters.js` – interactive filter chips.
- `product-gallery.js` – switches main image when clicking a thumbnail.
- `chatbot.js` – floating chatbot/help widget.

### style.css

Defines overall look and feel:

- Colors, typography.
- Layout grids for product cards, dashboard cards, admin tables.

**Classes like:**

- `.dashboard-title`, `.dashboard-subtitle`.
- `.product-grid`, `.marketplace-grid`, `.product-card`.
- `.status-pill--success`, `.status-pill--danger` for active/deleted user status.
- `.btn`, `.btn-secondary`, `.btn-pill`, etc.
- `.overview-bar-track` / `.overview-bar-fill` for admin charts.

---

## 4. Authentication and session flow

### login.php

- Shows login form.

**On POST:**

- Reads `email`, `password`.
- Instantiates `User` and calls `login`.
- On success:
- Redirects to `dashboard.php`.
- On failure:
- Shows an error message.

### signup.php

- Shows register form with:
- `username`, `email`, `password`, `confirm_password`.

**On POST:**

- Checks passwords match.
- Calls `User::register($username, $email, $password)`.
- If registration works:
- Shows success banner (“You can now log in”).
- Else:
- Shows error (“Unable to create account…”).

### logout.php
 - session_start();
 - session_unset(); 
 - session_destroy();


- Redirects back to `home.php` or another public page.

### Access guards

**Normal user‑only pages:**

- At top:
    - if (!isset($_SESSION['user_id'])) {
Redirects to login.php and exit.
}

**Admin‑only pages (`admin/...`):**

- At top:
    - if (empty($_SESSION['is_admin'])) {
Redirects to admin login or ../login.php and exit.
}

---

## 5. Front page behavior

### home.php (guest landing)

- If user already logged in:
- Redirects to `index.php`.
- Otherwise:
- Shows marketing hero (“GameSense marketplace”).
- Right side: login/signup panel.
- Encourages account creation to list gear and buy.

### index.php (logged‑in home)

- Requires logged in user.

**Fetches most recent 4 approved products:**
- SELECT p.*,
(SELECT filename
FROM product_images
WHERE product_id = p.id
ORDER BY sort_order, id
LIMIT 1) AS main_image
FROM products p
WHERE is_approved = 1
ORDER BY created_at DESC
LIMIT 4;

**Main sections:**

- Hero:
  - Static copy about a featured gaming mouse, plus stats (latency, battery, weight, warranty).

- Featured gear grid:
  - For each product:
    - Chooses image: `main_image`; fallback to `image`; fallback to initials.
    - Shows category, seller username, tags, short description (trimmed).
    - Shows price, “Details” and “Add to cart” button.

---

## 6. Seller tools (dashboard, add/edit/delete products)

### dashboard.php

- Requires login.
- Uses `Product::readByUser($userId)` to get all listings by the current user.

**Computes metrics across those products:**

- `totalProducts = count(listings).`
- `totalValue = sum of product.price` (not stock * price).
- `totalViews = sum of product.views.`
- `totalSold = sum of product.sold.`
- `totalRevenue = sum of (product.price * product.sold).`
- `avgPrice = totalProducts > 0 ? totalValue / totalProducts : 0.`

**Per‑category metrics:**

For each product:

- `categoryTotals[category] += price.`
- `categoryCounts[category] += 1.`

Calculates `maxCategoryTotal` to scale bar widths.

Each category bar width:

- `(categoryTotals[cat] / maxCategoryTotal) * 100`, fallback to 0 if max is 0.

**UI:**

- Top hero with metrics.
- Category chart block with horizontal bars.
- “Your products” grid:
  - Shows each listing’s main image, name, short description, price, stats (views/sold).
  - Buttons:
    - “View” (to `product_details.php`).
    - “Edit” (to `edit_product.php`).
    - “Delete” (to `delete_product.php`, with confirm).

---

### add_product.php

- Requires login.

**Shows a form:**

Inputs:

- `name` (text).
- `category` (select from e.g. mouse, keyboard, audio, etc.).
- `tags[]` (multiple checkboxes).
- `description` (textarea).
- `price` (number).
- `stock` (integer).
- `images[]` (multiple file input, 1 to 5 images).

**On POST:**

Validations:

- Name non‑empty.
- Category non‑empty.
- At least one tag selected.
- Price numeric and > 0.
- Stock integer >= 1.
- Images: at least 1 and at most 5 non‑empty `$_FILES['images']`.

If validation fails:

- Populates `$errors` array and re‑shows form.

If passes:

- Starts DB transaction.
- Uses `Product::create(...)` to insert product; gets `$productId`.
- Ensures `uploads/` exists, often using `is_dir` and `mkdir`.
- For each image:
  - Validates upload.
  - Generates unique filename (e.g. with `uniqid()`).
  - Moves file with `move_uploaded_file`.
  - Inserts into `product_images`:
    - `product_id`, `filename`, `sort_order`.
- If any step fails:
  - Rolls back transaction.
  - Shows error.
- If all succeed:
  - Commits transaction.
  - Redirects to `dashboard.php?message=created`.

---

### edit_product.php

- Requires login and ownership:

Reads `id` from GET.

- Uses `Product::isOwner($id, $userId)`.
- If false: redirects to `dashboard.php`.

Loads existing product details.

**Prepares tags:**

- `existingTags = explode(',', product.tags)` into an array.
- Checkboxes pre‑checked for tags in this array.

**On POST:**

Reads updated fields:

- `name`, `category`, `tags[]`, `description`, `price`, `stock`.

Performs similar validations as in add:

- Non‑empty required fields.
- Positive/zero values logic (here `price >= 0`, `stock >= 0`).

Optionally handles a single main image upload:

- If new image provided:
  - Store in `uploads/` and update `image` column.

Uses `Product::update(...)`.

- On success:
  - Redirects to `dashboard.php?message=updated`.

---

### delete_product.php

- Requires login.
- Reads `id` from GET and ensures > 0.
- Optionally confirms ownership using `isOwner`.
- Calls `Product::delete($id)`.
- Redirects to `dashboard.php?message=deleted`.

---

## 7. Marketplace browsing (`marketplace.php`)

### Inputs

**GET parameters:**

- `tags[]` – multiple tags.
- `q` – text search.
- `min_price` and `max_price` – numeric boundaries.
- `in_stock` – present if filtering to in‑stock only.
- `sort` – `newest`, `price_asc`, `price_desc`, `popular`.
- `page` – page number for pagination.

### Query building

Starts with a base SQL:
- SELECT p.*, u.username
FROM products p
JOIN users u ON p.user_id = u.id
WHERE p.is_approved = 1;

Depending on GET:

- If `q`:
  - Adds:
    ```
    AND (
      p.name LIKE :term
      OR p.category LIKE :term
      OR p.tags LIKE :term
      OR p.description LIKE :term
    );
    ```
- If `min_price`:
  - Adds `AND p.price >= :min_price.`
- If `max_price`:
  - Adds `AND p.price <= :max_price.`
- If `in_stock` is set:
  - Adds `AND p.stock > 0.`

### Sorting:

- `sort = newest`:
  - `ORDER BY p.created_at DESC.`
- `price_asc`:
  - `ORDER BY p.price ASC.`
- `price_desc`:
  - `ORDER BY p.price DESC.`
- `popular`:
  - `ORDER BY p.views DESC.`

### Tag filtering (applied in PHP after SQL)

After query returns results:

- If no tag filters active:
  - Use all items.
- If tags active:
  - For each product:
    - Convert product tags string to lowercase.
    - For each selected tag:
      - If tag substring is found in the tags string:
        - Keep the product and stop checking further tags.

### Pagination

- `perPage = 12.`
- `totalItems = count(filteredProducts).`
- `totalPages = max(1, ceil(totalItems / perPage)).`
- `page` clamped into `[1, totalPages].`
- Compute offset: `($page - 1) * $perPage.`
- Slice:
  - `$pageItems = array_slice(filteredProducts, offset, perPage);`.

### Rendering

**Filter sidebar:**

- Tag chips bound to hidden/visible checkboxes, styled via `marketplace-filters.js`.
- Min/max price numeric inputs.
- “Only in stock” checkbox.
- Sort dropdown.

**Top bar:**

- Search field.
- Shows count like “X results”.

**Product cards:**

- Choose an image:
  - `main_image` from `product_images`.
  - Else legacy `image`.
  - Else letters from name.
- Show:
  - Name, category, seller username, tags string.
  - Short description snippet.
  - Price formatted to 2 decimals.
- Actions:
  - “Details” → `product_details.php?id=....`
  - “Add to cart” form posting to `add_to_cart.php`.

**Pagination controls:**

- “Prev” (disabled if page = 1).
- “Next” (disabled if page = totalPages).
- Current page / total pages indicator.

---

## 8. Product details, wishlist, and reviews (`product_details.php`)

### Loading product and images

- Validates `id` from GET.
- Uses `Product::getById($id)`; if not found, redirects back to `index.php`.

Loads gallery:
- SELECT *
FROM product_images
WHERE product_id = :id
ORDER BY sort_order, id;


- If `product_images` not empty:
  - Main image = `product_images[0]['filename']`.
- Else:
  - Main image = `product['image']` if set; otherwise placeholder.

### Incrementing views

Executes:
- UPDATE products
SET views = views + 1
WHERE id = :id;


### Display

Shows:

- Large main image.
- Thumbnails (each with `data-full` pointing to full image URL).
- Product name, price, category, tags.
- Description with line breaks preserved.
- Seller username, listing date.

### Actions

**“Message seller”:**

- Appears if current user is logged in and is not the seller.
- Links to:
  - `message_send.php?to=<seller_id>&subject=Regarding: <product name>`.

**“Add to cart”:**

- Form posting to `add_to_cart.php` with hidden `product_id`.

**“Buy now”:**

- Same but includes `redirect=cart` so the next page is `cart.php`.

**“Save / Unsave”:**

- If logged in:
  - Checks `Wishlist::isInWishlist(userId, productId)`.
  - If not saved:
    - Shows “Save item” linking to:
      - `wishlist_toggle.php?product_id=<id>&mode=add&redirect=product_details.php?id=<id>`.
  - If saved:
    - Shows “Saved” / “Remove from saved” linking with `mode=remove`.

### Reviews

**Stats and list:**

- Calls `Review::getStats($productId)` for:
  - `total` and `avg`.
- Calls `Review::getForProduct($productId)` to render each review:
  - Username, rating (e.g. `4/5`), comment, `created_at`.

**Who can review:**

- Must be logged in.
- Must not be the seller.
- Must have purchased (checked by `Review::userHasPurchased($productId, $userId)`).

**On POST:**

- Reads `review_rating` and `review_comment`.
- Checks permission using `userHasPurchased`.
- If allowed:
  - Calls `Review::addOrUpdate($productId, $userId, $rating, $comment)`.
- Redirects to same product page (avoids duplicate form submission).

**UI:**

- If `$canReview`:
  - Shows a dropdown 1–5 with star labels and a text area.
- If logged in but not allowed:
  - Shows note about needing to purchase first.

---

## 9. Cart and checkout

### add_to_cart.php

- Requires login.

Reads:

- `product_id` from POST.
- Optional `redirect` from POST or GET.

**Behavior:**

- If `product_id <= 0` → redirect to `index.php`.
- Else:
  - `Cart::init();`
  - `Cart::add($product_id, 1);`.

**Redirect:**

- If `redirect == 'cart'`:
  - `header('Location: cart.php');`.
- Else:
  - `header('Location: index.php?added=1');`.

---

### cart.php

- Requires login.
- `Cart::init()` to ensure cart array exists.

**On POST:**

- Reads `action` and `product_id`.
- If `action` is:
  - `decrease`:
    - `Cart::decrease($product_id, 1);`.
  - `increase`:
    - `Cart::add($product_id, 1);`.
  - `remove`:
    - `Cart::remove($product_id);`.
  - `clear`:
    - `Cart::clear();` (ignores product id).
- After any action:
  - Redirects back to `cart.php` (PRG pattern).

**Loading cart data:**

- `$items = Cart::getItems();`.
- If empty → show “Your cart is empty”.
- Else:
  - Builds a list of product ids from array keys.
  - For each product id:
    - Queries `products` plus:
      - Subquery to get `main_image` from `product_images` if any.
    - Attaches `quantity` from the cart.
    - Calculates `line_total = price * quantity`.
    - Adds to `$subtotal`.

**Summary:**

- Shows each line with:
  - Thumbnail, name, category, price, quantity.
- Buttons:
  - `−` (decrease).
  - `+` (increase).
  - “Remove”.

Right side summary card:

- Subtotal (sum of line totals).
- Shipping note (e.g. “Calculated at checkout”).
- Total (currently equal to subtotal).

Buttons:

- “Proceed to checkout” (GET `checkout.php`).
- “Clear cart”.
- “Continue shopping” link to `index.php`.

---

### checkout.php

- Requires login.
- Currently simple:
  - Displays stub/placeholder about checkout.
  - Used to future‑proof integration with payment provider and order creation.

---

## 10. Wishlist (saved items)

### wishlist_toggle.php

- Requires login.

Reads:

- `product_id` (int) from GET.
- `mode` from GET, default `add`.
- `redirect` from GET, default `marketplace.php`.

**Behavior:**

- If `product_id <= 0`:
  - `header("Location: $redirect");`.
- Else:
  - If `mode === 'remove'`:
    - `Wishlist::remove($_SESSION['user_id'], $product_id);`.
  - Else:
    - `Wishlist::add($_SESSION['user_id'], $product_id);`.
- Redirects to `$redirect`.

---

### wishlist.php

- Requires login.
    - $wishlist = new Wishlist($db);
$stmt = $wishlist->forUser($_SESSION['user_id']);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($items);


- If `total === 0`:
  - Show card: “No saved items yet.”
- Else:
  - Show a list of “Saved items” entries:
    - Each is a card linking to `product_details.php?id=<id>`.

**Thumbnail area:**

- If `main_image`: use `/uploads/main_image`.
- Else if `image`: use `/uploads/image`.
- Else:
  - Show two‑letter monogram from product name.

**Text area:**

- Product name.
- Category, and tags if non‑empty.
- Price formatted to 2 decimals.

---

## 11. Messaging system flows

### message_send.php

- Requires login.

Optional query parameters:

- `to` – recipient user id (from product page).
- `subject` – pre‑filled subject.

- Loads recipient username for display if `to` is present.

**On POST:**

Reads:

- `recipient_id`, `subject`, `body`.

Validations:

- Recipient id > 0.
- Subject not empty.
- Body not empty.

If errors:

- Shows them and preserves entered values.

If no errors:

- `Message::send($_SESSION['user_id'], $recipient_id, $subject, $body);`.

On success:

- Sets `$success = "Message sent."`.
- Clears form fields.

---

### messages_inbox.php

- Requires login.
    - $messages = $messageObj->getInbox($_SESSION['user_id']);


- If no rows:
  - Show “No messages yet”.
- Else:
  - For each message:
    - Shows:
      - Sender initial in small avatar circle.
      - Subject; if `is_read == 0`, adds “- New”.
      - “From senderName · dateTime”.
      - “View” button → `message_view.php?id=<message_id>`.

---

### messages_sent.php

- Similar to inbox but uses `getSent($userId)`.

Shows:

- “To recipientName · dateTime” and subject.

---

### message_view.php

- Requires login.

GET `id`.
- $msg = $messageObj->getByIdForUser($id, $_SESSION['user_id']);


- If no message:
  - Redirects to `messages_inbox.php`.
- If current user is the recipient and `is_read == 0`:
  - Calls `markAsRead($id, $_SESSION['user_id']);`.

**Renders:**

- Subject.
- From/To lines:
  - If current user is recipient:
    - Show “From: senderUsername”.
  - Else if current user is sender:
    - Show “To: recipientUsername”.
- Date/time.
- Body with newlines preserved.

**“Reply” button:**

- Link:
  - `message_send.php?to=<otherUserId>&subject=<Re: cleanSubject>`.
- If subject doesn’t already start with `Re:` it adds it.

---

## 12. Admin panel

### Common admin guard

At top of each `admin/*.php` file:

- session_start();
if (empty($_SESSION['is_admin'])) {
Redirect to ../login.php;
}


---

### admin/users.php

Queries all users:

- SELECT id, username, email, created_at, is_deleted
FROM users
ORDER BY id ASC;


Renders table:

- Columns:
  - ID, Username, Email, Created, Status, Action.

**Status:**

- If `is_deleted` truthy → show pill “Deleted”.
- Else → pill “Active”.

**Actions:**

- Edit → `edit_user.php?id=<id>`.
- If active:
  - Remove → `delete_user.php?id=<id>` with confirm dialog.
- If deleted:
  - Restore → `restore_user.php?id=<id>`.

---

### admin/edit_user.php

- Guard: admin only.
- GET `id`, must be > 0.

Loads:

- SELECT id, username, email
FROM users
WHERE id = :id
LIMIT 1;


- If not found:
  - Redirect back to `users.php`.

**On POST:**

Reads `username`, `email`.

- If either empty:
  - Adds error: “Username and email are required.”
- If no errors:
    - UPDATE users
SET username = :username, email = :email
WHERE id = :id;

- Sets `$success = 'User updated.'`.
- Updates `$user` array so page shows new values.

---

### admin/delete_user.php

- Guard: admin only.
- GET `id`, must be > 0.

Executes:
- DELETE FROM users
WHERE id = :id;


- Redirects back to `users.php`.

---

### admin/restore_user.php

- Guard: admin only.
- GET `id`, must be > 0.

Executes:

- UPDATE users
SET is_deleted = 0
WHERE id = :id;


- Redirects back to `users.php`.

---

### admin/products.php

- Lists all products:

     - SELECT ... FROM products ...


- with seller info and approval status.

Provides admin controls:

- Approve/unapprove products.
- Possibly delete listings or hide them.

---

### admin/reports.php

- Guard: admin only.

**Top 5 “Most viewed”:**

Query:
- SELECT id, name, views, sold, price
FROM products
WHERE is_approved = 1
ORDER BY views DESC
LIMIT 5;


Compute `$maxViews = max(views)` or 0.

For each product:

- Compute:
    - $ratio = $maxViews > 0 ? ($views / $maxViews) : 0;
$width = max(8, round($ratio * 100)); // bar width between 8–100%


**Top 5 “Top sellers”:**

Query:
- SELECT id, name, views, sold, price
FROM products
WHERE is_approved = 1
ORDER BY sold DESC
LIMIT 5;


Compute `$maxSold`.

For each:

- `$ratio = $maxSold > 0 ? ($sold / $maxSold) : 0;`.
- `$width = max(8, round($ratio * 100));`.

**UI:**

Two cards:

- One for view counts (“Most viewed products”).
- One for sold counts (“Top sellers”).

Each row shows:

- Product name.
- Horizontal bar with width proportional to `views` or `sold`.
- Numeric text like “123 views” or “45 sold”.

---

## 13. Front‑end JavaScript

- `scroll-animations.js`:
  - Adds animations as elements with `.reveal-on-scroll` enter viewport.
- `home-hero.js`:
  - Handles entrance animation for the hero on `home.php` / `index.php`.
- `marketplace-filters.js`:
  - Makes filter chips clickable.
  - Syncs selected chips with actual form inputs (checkboxes) so filter submits work.
- `product-gallery.js`:
  - On product detail page:
    - Binds click events on thumbnail buttons.
    - When clicked:
      - Sets `src` of main image element to thumbnail’s `data-full` URL.
- `chatbot.js`:
  - Renders a floating toggle button and chat window.
  - Handles user input, basic open/close, and placeholder responses or integration.

