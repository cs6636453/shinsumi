<?php
include "../../../db/connect.php"; // Adjust path as needed
if (!isset($_SESSION['username'])) header("Location: ../../../login_request/"); // Check admin login if applicable

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;
$errors = [];
$success_message = "";

// --- Redirect if no ID or invalid ID ---
if ($product_id === false || $product_id <= 0) {
    header("Location: ../../products.php?status=error&msg=Invalid Product ID"); // Redirect to product list
    exit;
}

// --- Fetch Existing Product Data ---
try {
    $stmt_prod = $pdo->prepare("SELECT * FROM gs_product WHERE pid = ?");
    $stmt_prod->execute([$product_id]);
    $product = $stmt_prod->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: ../../products.php?status=error&msg=Product not found");
        exit;
    }

    // --- Fetch Categories and Promotions for dropdowns ---
    $stmt_cat = $pdo->query("SELECT category_id, category_name FROM gs_category ORDER BY category_name");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_promo = $pdo->query("SELECT pr_id, pr_name FROM gs_promotion ORDER BY pr_name");
    $promotions = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
// --- End Fetch Data ---

// --- Initialize form variables with fetched data ---
$pname = $product['pname'];
$description = $product['description'];
$price = $product['price'];
$stock = $product['stock'];
$category_id = $product['category_id'];
$pr_id = $product['pr_id'] ?? 0; // Use 0 if NULL for "None" option

// --- Process Form Submission for Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Get updated values (similar to add.php) ---
    $pname = trim($_POST['pname'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $category_id = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $pr_id_post = filter_var($_POST['pr_id'] ?? '', FILTER_VALIDATE_INT);
    $pr_id = ($pr_id_post === 0 || $pr_id_post === false) ? null : $pr_id_post; // Handle "None"

    // --- Basic Validation (same as add.php) ---
    if (empty($pname)) $errors[] = "Product name is required.";
    if ($price === false || $price < 0) $errors[] = "Invalid price.";
    if ($stock === false || $stock < 0) $errors[] = "Invalid stock quantity.";
    if (empty($category_id) || $category_id === false) $errors[] = "Please select a category.";
    // Check if pr_id exists if not null
    if ($pr_id !== null) {
        $promo_exists = false;
        foreach($promotions as $promo) { if ($promo['pr_id'] == $pr_id) $promo_exists = true; }
        if (!$promo_exists) $errors[] = "Invalid promotion selected.";
    }
    // Check if category_id exists
    $cat_exists = false;
    foreach($categories as $cat) { if ($cat['category_id'] == $category_id) $cat_exists = true; }
    if (!$cat_exists) $errors[] = "Invalid category selected.";

    // --- Image Upload Handling (Optional Update) ---
    $target_dir = "../../../assets/images/products/";
    $new_image_uploaded = false;
    $uploadOk = 1;
    $imageFileType = null;

    if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] == 0 && $_FILES["fileToUpload"]["size"] > 0) {
        $new_image_uploaded = true;
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

        if ($check === false) {
            $errors[] = "New file is not an image.";
            $uploadOk = 0;
        } else {
            $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));
            if ($_FILES["fileToUpload"]["size"] > 2000000) {
                $errors[] = "Sorry, your new file is too large (Max 2MB).";
                $uploadOk = 0;
            }
            if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed for the new image.";
                $uploadOk = 0;
            }
        }
    }
    // --- End Image Handling ---

    // --- If No Errors, Update DB ---
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $sql = "UPDATE gs_product SET
                            pname = ?,
                            description = ?,
                            price = ?,
                            stock = ?,
                            category_id = ?,
                            pr_id = ?
                        WHERE pid = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pname, $description, $price, $stock, $category_id, $pr_id, $product_id]);

            // --- Handle New Image Upload ---
            if ($new_image_uploaded && $uploadOk == 1) {
                // Delete old image(s) - Careful with extensions
                $old_files = glob($target_dir . $product_id . ".*"); // Find files starting with PID
                if ($old_files) {
                    foreach ($old_files as $old_file) {
                        if (is_file($old_file)) {
                            unlink($old_file); // Delete the old file
                        }
                    }
                }

                // Move new file
                $target_file_name = $product_id . "." . $imageFileType;
                $target_path = $target_dir . $target_file_name;
                if (!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_path)) {
                    throw new Exception("Failed to move new uploaded file."); // Trigger rollback
                }
            } elseif ($new_image_uploaded && $uploadOk == 0) {
                // If image validation failed after other checks passed, roll back
                throw new Exception("Image validation failed, update cancelled.");
            }
            // --- End Handle New Image ---

            $pdo->commit();
            $success_message = "Product updated successfully!";
            // Refresh product data after update
            $stmt_prod->execute([$product_id]);
            $product = $stmt_prod->fetch(PDO::FETCH_ASSOC);
            // Re-initialize form variables after successful update
            $pname = $product['pname'];
            $description = $product['description'];
            $price = $product['price'];
            $stock = $product['stock'];
            $category_id = $product['category_id'];
            $pr_id = $product['pr_id'] ?? 0;

        } catch (Exception $e) { // Catch PDOException or general Exception
            $pdo->rollBack();
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                $errors[] = "Error: Product name might already exist.";
            } else {
                $errors[] = "Update error: " . $e->getMessage();
            }
        }
    }
}
// --- End Process Form Submission ---

// Find current image file
$current_image = null;
$img_files = glob("../../../assets/images/products/" . $product_id . ".*");
if ($img_files && count($img_files) > 0) {
    $current_image = basename($img_files[0]); // Get the filename of the first match
}

// Handle status messages from redirects (e.g., after adding)
if(isset($_GET['status']) && $_GET['status'] == 'added') {
    $success_message = "New product added successfully!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <link rel="icon" href="../../../assets/favicon/xobazjr.ico" type="image/x-icon" />
    <link rel="stylesheet" href="../../../assets/style/nav.css" />
    <link rel="stylesheet" href="../../../assets/style/global.css" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@1" rel="stylesheet" />
    <link rel="stylesheet" href="../../../assets/style/index.css">
    <title>Edit Product #<?= htmlspecialchars($product_id) ?> | GS MyAdmin Panel</title>
    <link rel="stylesheet" href="../../../assets/style/login_form.css">
    <link rel="stylesheet" href="../../../assets/style/desktop_admin.css">
    <link rel="stylesheet" href="../../inner.css"> <style>
        /* (Same styles as add.php, with addition for current image) */
        main.myMain { max-width: 800px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* --- CSS Fix --- */
            height: auto; /* Allow browser to determine height */
            line-height: normal; /* Use default line height */
            /* ------------- */
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group input[type="file"] { padding: 5px; height: auto; /* Ensure file input height is auto too */ }
        .form-group small { font-size: 0.85em; color: #6c757d; margin-top: 3px; display: block;} /* Style for the image size text */
        .error-list { color: red; margin-bottom: 15px; list-style-position: inside; }
        .success-message { color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .form-buttons { margin-top: 20px; }
        .form-buttons button, .form-buttons a { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; text-decoration: none; display: inline-block; vertical-align: middle; } /* Style link like button */
        .form-buttons button:hover, .form-buttons a:hover { background-color: #0056b3; }
        .form-buttons a { background-color: #6c757d; margin-left: 10px; } /* Grey for cancel */
        .form-buttons a:hover { background-color: #5a6268; }
        .current-image img { max-width: 150px; height: auto; border: 1px solid #ddd; margin-top: 5px; }
    </style>
</head>
<body>
<nav>
    <div id="top_row">
        <section class="container" id="menu_btn" onclick="animateMenuButton(this)">
            <div class="bar1"></div><div class="bar2"></div><div class="bar3"></div>
        </section>
        <section id="shop_name"><a href="../../">GS MyAdmin Dashboard</a></section>
        <section id="login_btn"><a id="login"><img src="../../../assets/images/loading.gif" alt="loading"></a></section>
    </div>
</nav>
<div id="side-nav-overlay"></div>
<div id="side-nav-menu" class="side-nav">
    <ul class="side-nav-list">
        <li><a href="../../" class="nav-item-button"><span class="material-symbols-outlined">home</span><span>Dashboard</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../../products.html" class="nav-item-button active"><span class="material-symbols-outlined">package_2</span><span>Products</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../../orders.html" class="nav-item-button"><span class="material-symbols-outlined">order_approve</span><span>Orders</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../../promotions.html" class="nav-item-button"><span class="material-symbols-outlined">loyalty</span><span>Promotions</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
    </ul>
</div>

<main class="myMain">
    <h1>Edit Product #<?= htmlspecialchars($product_id) ?></h1>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>

    <form action="edit.php?id=<?= htmlspecialchars($product_id) ?>" method="POST" enctype="multipart/form-data">

        <div class="form-group">
            <label for="pname">Product Name:</label>
            <input type="text" id="pname" name="pname" value="<?= htmlspecialchars($pname) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="form-group">
            <label for="price">Price (Baht):</label>
            <input type="number" id="price" name="price" value="<?= htmlspecialchars($price) ?>" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label for="stock">Stock Quantity:</label>
            <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($stock) ?>" min="0" required>
        </div>

        <div class="form-group">
            <label for="category_id">Category:</label>
            <select id="category_id" name="category_id" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['category_id'] ?>" <?= ($category_id == $cat['category_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pr_id">Promotion (Optional):</label>
            <select id="pr_id" name="pr_id">
                <option value="0">-- None --</option>
                <?php foreach ($promotions as $promo): ?>
                    <option value="<?= $promo['pr_id'] ?>" <?= ($pr_id == $promo['pr_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($promo['pr_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Current Image:</label>
            <div class="current-image">
                <?php if ($current_image): ?>
                    <img src="../../../assets/images/products/<?= htmlspecialchars($current_image) ?>?t=<?= time() // Cache buster ?>" alt="Current Product Image">
                    <p><?= htmlspecialchars($current_image) ?></p>
                <?php else: ?>
                    <p>No image currently uploaded.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="fileToUpload">Upload New Image (Optional - Overwrites current):</label>
            <input type="file" name="fileToUpload" id="fileToUpload" accept=".jpg,.jpeg,.png,.gif">
            <small>Leave blank to keep the current image. กรุณาใช้รูปภาพขนาด 800x1000 พิกเซลเท่านั้น</small>
        </div>

        <div class="form-buttons">
            <button type="submit">Update Product</button>
            <a href="../../products.html">Cancel</a> </div>
    </form>
</main>

<script src="../../../scripts/index.js"></script>
<script src="../../scripts/login_req.js"></script>
</body>
</html>