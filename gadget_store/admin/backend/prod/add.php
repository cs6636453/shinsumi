<?php
include "../../../db/connect.php"; // Adjust path as needed
if (!isset($_SESSION['username'])) header("Location: ../../../login_request/"); // Check admin login if applicable

$pname = "";
$description = "";
$price = "";
$stock = "";
$category_id = "";
$pr_id = ""; // Default to no promotion

$errors = [];
$success_message = "";

// --- Fetch Categories and Promotions for dropdowns ---
try {
    $stmt_cat = $pdo->query("SELECT category_id, category_name FROM gs_category ORDER BY category_name");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    $stmt_promo = $pdo->query("SELECT pr_id, pr_name FROM gs_promotion ORDER BY pr_name"); // Add more info if needed
    $promotions = $stmt_promo->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching dropdown data: " . $e->getMessage());
}
// --- End Fetch Dropdowns ---

// --- Process Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pname = trim($_POST['pname'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = filter_var($_POST['price'] ?? '', FILTER_VALIDATE_FLOAT);
    $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
    $category_id = filter_var($_POST['category_id'] ?? '', FILTER_VALIDATE_INT);
    $pr_id = filter_var($_POST['pr_id'] ?? '', FILTER_VALIDATE_INT);
    if ($pr_id === 0 || $pr_id === false) { // Handle "None" promotion
        $pr_id = null;
    }

    // --- Basic Validation ---
    if (empty($pname)) $errors[] = "Product name is required.";
    if ($price === false || $price < 0) $errors[] = "Invalid price.";
    if ($stock === false || $stock < 0) $errors[] = "Invalid stock quantity.";
    if (empty($category_id) || $category_id === false) $errors[] = "Please select a category.";
    // Check if pr_id exists if not null (optional but good)
    if ($pr_id !== null) {
        $promo_exists = false;
        foreach($promotions as $promo) { if ($promo['pr_id'] == $pr_id) $promo_exists = true; }
        if (!$promo_exists) $errors[] = "Invalid promotion selected.";
    }
    // Check if category_id exists (optional but good)
    $cat_exists = false;
    foreach($categories as $cat) { if ($cat['category_id'] == $category_id) $cat_exists = true; }
    if (!$cat_exists) $errors[] = "Invalid category selected.";

    // --- Image Upload Validation ---
    $target_file_name = ""; // Will be set after getting product ID
    $uploadOk = 1;
    $imageFileType = null;

    if (isset($_FILES["fileToUpload"]) && $_FILES["fileToUpload"]["error"] == 0) {
        $target_dir = "../../../assets/images/products/";
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

        if ($check === false) {
            $errors[] = "File is not an image.";
            $uploadOk = 0;
        } else {
            $imageFileType = strtolower(pathinfo($_FILES["fileToUpload"]["name"], PATHINFO_EXTENSION));
            // Check file size (e.g., 2MB limit)
            if ($_FILES["fileToUpload"]["size"] > 2000000) {
                $errors[] = "Sorry, your file is too large (Max 2MB).";
                $uploadOk = 0;
            }
            // Allow certain file formats
            if (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
                $errors[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                $uploadOk = 0;
            }
        }
    } else {
        // No file uploaded or an upload error occurred
        $errors[] = "Product image is required."; // Make image mandatory for new products
        $uploadOk = 0;
    }
    // --- End Image Validation ---


    // --- If No Errors, Insert into DB ---
    if (empty($errors) && $uploadOk == 1) {
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO gs_product (pname, description, price, stock, category_id, pr_id)
                        VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pname, $description, $price, $stock, $category_id, $pr_id]);

            $new_pid = $pdo->lastInsertId(); // Get the new product ID

            // --- Move and Rename Uploaded File ---
            $target_file_name = $new_pid . "." . $imageFileType;
            $target_path = $target_dir . $target_file_name;

            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_path)) {
                // Image moved successfully
                $pdo->commit();
                $success_message = "New product added successfully! (ID: " . $new_pid . ")";
                // Clear form fields after success
                $pname = $description = $price = $stock = $category_id = $pr_id = "";
                // Redirect to edit page after adding
                header("Location: edit.php?id=" . $new_pid . "&status=added");
                exit;
            } else {
                $pdo->rollBack();
                $errors[] = "Sorry, there was an error moving your uploaded file.";
            }
            // --- End Move File ---

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->errorInfo[1] == 1062) {
                $errors[] = "Error: Product name might already exist.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
// --- End Process Form Submission ---
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
    <title>Add New Product | GS MyAdmin Panel</title>
    <link rel="stylesheet" href="../../../assets/style/login_form.css">
    <link rel="stylesheet" href="../../inner.css"> <style>
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
        .form-buttons button { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .form-buttons button:hover { background-color: #0056b3; }
        .form-group input:invalid { border-color: red; } /* Basic HTML5 validation highlight */
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
    <h1>Add New Product</h1>

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

    <form action="add.php" method="POST" enctype="multipart/form-data">
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
                <option value="0">-- None --</option> <?php foreach ($promotions as $promo): ?>
                    <option value="<?= $promo['pr_id'] ?>" <?= ($pr_id == $promo['pr_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($promo['pr_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fileToUpload">Product Image (JPG, PNG, GIF - Max 2MB):</label>
            <input type="file" name="fileToUpload" id="fileToUpload" accept=".jpg,.jpeg,.png,.gif" required>
            <small>กรุณาใช้รูปภาพขนาด 800x1000 พิกเซลเท่านั้น</small>
        </div>

        <div class="form-buttons">
            <button type="submit">Add Product</button>
        </div>
    </form>
</main>

<script src="../../../scripts/index.js"></script>
<script src="../../scripts/login_req.js"></script> </body>
</html>