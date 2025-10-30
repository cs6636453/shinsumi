<?php
include "../../../db/connect.php"; // Adjust path as needed
if (!isset($_SESSION['username'])) header("Location: ../../../login_request/"); // Check admin login

$promo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$promotion = null;
$errors = [];
$success_message = "";

if ($promo_id === false || $promo_id <= 0) {
    header("Location: ../../promotions.php?status=error&msg=Invalid Promotion ID");
    exit;
}

try {
    // Fetch Existing Promotion Data
    $stmt_promo = $pdo->prepare("SELECT * FROM gs_promotion WHERE pr_id = ?");
    $stmt_promo->execute([$promo_id]);
    $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);

    if (!$promotion) {
        header("Location: ../../promotions.php?status=error&msg=Promotion not found");
        exit;
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Initialize form variables
$pr_name = $promotion['pr_name'];
$description = $promotion['description'];
$discount_type = $promotion['discount_type'];
$discount_value = $promotion['discount_value'];
$start_date = $promotion['start_date'];
$end_date = $promotion['end_date'];

// --- Process Form Submission for Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated values
    $pr_name = trim($_POST['pr_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'fixed';
    $discount_value = filter_var($_POST['discount_value'] ?? '', FILTER_VALIDATE_FLOAT);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    // --- Basic Validation (Same as add) ---
    if (empty($pr_name)) $errors[] = "Promotion name is required.";
    if (!in_array($discount_type, ['fixed', 'percent'])) $errors[] = "Invalid discount type.";
    if ($discount_value === false || $discount_value <= 0) $errors[] = "Invalid discount value. Must be greater than 0.";
    if ($discount_type === 'percent' && $discount_value > 1) $errors[] = "Percent value must be between 0.01 and 1.00 (e.g., 0.15 for 15%).";
    if (empty($start_date)) $errors[] = "Start date is required.";
    if (empty($end_date)) $errors[] = "End date is required.";
    if (!empty($start_date) && !empty($end_date) && $end_date < $start_date) $errors[] = "End date must be after the start date.";

    // --- If No Errors, Update DB ---
    if (empty($errors)) {
        try {
            $sql = "UPDATE gs_promotion SET
                        pr_name = ?,
                        description = ?,
                        discount_type = ?,
                        discount_value = ?,
                        start_date = ?,
                        end_date = ?
                    WHERE pr_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pr_name, $description, $discount_type, $discount_value, $start_date, $end_date, $promo_id]);

            $success_message = "Promotion updated successfully!";

            // Re-fetch data to show updated values in the form
            $stmt_promo->execute([$promo_id]);
            $promotion = $stmt_promo->fetch(PDO::FETCH_ASSOC);
            $pr_name = $promotion['pr_name']; $description = $promotion['description']; $discount_type = $promotion['discount_type'];
            $discount_value = $promotion['discount_value']; $start_date = $promotion['start_date']; $end_date = $promotion['end_date'];

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errors[] = "Error: Promotion name might already exist.";
            } else {
                $errors[] = "Update error: " . $e->getMessage();
            }
        }
    }
}
// --- End Process Form Submission ---

// Handle status messages from redirects (e.g., after adding)
if(isset($_GET['status']) && $_GET['status'] == 'added') {
    $success_message = "New promotion added successfully!";
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
    <link rel="stylesheet" href="../../../assets/style/desktop_admin.css">
    <title>Edit Promotion #<?= htmlspecialchars($promo_id) ?> | GS MyAdmin Panel</title>
    <link rel="stylesheet" href="../../../assets/style/login_form.css">
    <link rel="stylesheet" href="../../inner.css">
    <style>
        main.myMain { max-width: 800px; margin: 20px auto; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="date"],
        .form-group textarea,
        .form-group select {
            width: 100%; padding: 10px; border: 1px solid #ccc;
            border-radius: 4px; box-sizing: border-box; height: auto; line-height: normal;
        }
        .form-group textarea { min-height: 100px; resize: vertical; }
        .form-group small { font-size: 0.85em; color: #6c757d; margin-top: 3px; display: block;}
        .error-list { color: red; margin-bottom: 15px; list-style-position: inside; }
        .success-message { color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .form-buttons { margin-top: 20px; }
        .form-buttons button, .form-buttons a { padding: 10px 20px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; text-decoration: none; display: inline-block; vertical-align: middle; }
        .form-buttons button:hover, .form-buttons a:hover { background-color: #0056b3; }
        .form-buttons a { background-color: #6c757d; margin-left: 10px; }
        .form-buttons a:hover { background-color: #5a6268; }
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
        <li><a href="../../products.html" class="nav-item-button"><span class="material-symbols-outlined">package_2</span><span>Products</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../../orders.html" class="nav-item-button"><span class="material-symbols-outlined">order_approve</span><span>Orders</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../../promotions.html" class="nav-item-button active"><span class="material-symbols-outlined">loyalty</span><span>Promotions</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
    </ul>
</div>

<main class="myMain">
    <h1>Edit Promotion #<?= htmlspecialchars($promo_id) ?></h1>

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

    <form action="edit.php?id=<?= htmlspecialchars($promo_id) ?>" method="POST">
        <div class="form-group">
            <label for="pr_name">Promotion Name:</label>
            <input type="text" id="pr_name" name="pr_name" value="<?= htmlspecialchars($pr_name) ?>" required>
        </div>

        <div class="form-group">
            <label for="description">Description:</label>
            <textarea id="description" name="description"><?= htmlspecialchars($description) ?></textarea>
        </div>

        <div class="form-group">
            <label for="discount_type">Discount Type:</label>
            <select id="discount_type" name="discount_type" required>
                <option value="fixed" <?= ($discount_type == 'fixed') ? 'selected' : '' ?>>Fixed (บาท)</option>
                <option value="percent" <?= ($discount_type == 'percent') ? 'selected' : '' ?>>Percent (%)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="discount_value">Discount Value:</label>
            <input type="number" id="discount_value" name="discount_value" value="<?= htmlspecialchars($discount_value) ?>" step="0.01" min="0.01" required>
            <small>If 'Percent', use 0.xx format (e.g., 0.15 for 15%). If 'Fixed', use amount (e.g., 100 for 100 บาท).</small>
        </div>

        <div class="form-group">
            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
        </div>

        <div class="form-group">
            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
        </div>

        <div class="form-buttons" style="display: inline-flex;">
            <button type="submit">Update Promotion</button>
            <a href="../../promotions.html">Cancel</a> </div>
    </form>
</main>

<script src="../../../scripts/index.js"></script>
<script src="../../scripts/login_req.js"></script>
</body>
</html>