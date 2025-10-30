<?php
include "../../../db/connect.php"; // Adjust path as needed
if (!isset($_SESSION['username'])) header("Location: ../../../login_request/"); // Check admin login if applicable

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = "error"; // Default status for redirect message
$message = "";

// Redirect if ID is missing or not a positive integer
if ($product_id === false || $product_id <= 0) {
    $message = "Invalid Product ID.";
    header("Location: ../../products.php?status=" . $status . "&msg=" . urlencode($message));
    exit;
}

try {
    // --- Update the product stock to -1 ---
    // We don't need a transaction for a single update, but it doesn't hurt.
    $pdo->beginTransaction();

    $stmt_update_stock = $pdo->prepare("UPDATE gs_product SET stock = -1 WHERE pid = ?");
    $updated = $stmt_update_stock->execute([$product_id]);

    if ($updated && $stmt_update_stock->rowCount() > 0) { // Check if a row was actually updated
        $pdo->commit(); // Commit the change
        $status = "success";
        $message = "Product #" . $product_id . " marked as out of stock (stock set to -1).";
    } else {
        // Check if product existed but wasn't updated (maybe already -1?)
        $stmt_check = $pdo->prepare("SELECT pid, stock FROM gs_product WHERE pid = ?");
        $stmt_check->execute([$product_id]);
        $product = $stmt_check->fetch();
        if (!$product) {
            $message = "Product not found (ID: " . $product_id . ").";
        } else if ($product['stock'] == -1) {
            $message = "Product #" . $product_id . " was already marked as out of stock.";
            $status = "info"; // Use 'info' or 'warning' instead of 'error'
        }
        else {
            $message = "Failed to update stock for product #" . $product_id . ".";
        }
        $pdo->rollBack(); // Rollback if no rows were affected (or if product not found)
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $message = "Database error: " . $e->getMessage();
    error_log("PDOException in setting stock to -1: " . $e->getMessage());
}

// --- Redirect back ---
header("Location: ../../products.html?status=" . $status . "&msg=" . urlencode($message));
exit;
?>