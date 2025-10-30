<?php
include "../../../db/connect.php"; // Adjust path as needed
if (!isset($_SESSION['username'])) header("Location: ../../../login_request/"); // Check admin login

$promo_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status = "error"; // Default status
$message = "";

// Redirect if ID is missing or not a positive integer
if ($promo_id === false || $promo_id <= 0) {
    $message = "Invalid Promotion ID.";
    header("Location: ../../promotions.php?status=" . $status . "&msg=" . urlencode($message));
    exit;
}

try {
    // --- Attempt to delete the promotion directly ---
    $stmt_delete = $pdo->prepare("DELETE FROM gs_promotion WHERE pr_id = ?");
    $deleted = $stmt_delete->execute([$promo_id]);

    if ($deleted && $stmt_delete->rowCount() > 0) { // Check if a row was actually deleted
        $status = "success";
        $message = "Promotion #" . $promo_id . " has been permanently deleted.";
    } else {
        // Check if it failed because it didn't exist
        $stmt_check = $pdo->prepare("SELECT pr_id FROM gs_promotion WHERE pr_id = ?");
        $stmt_check->execute([$promo_id]);
        if (!$stmt_check->fetch()) {
            $message = "Promotion not found (ID: " . $promo_id . ").";
        } else {
            $message = "Failed to delete promotion #" . $promo_id . ". (No rows affected)";
        }
    }
} catch (PDOException $e) {
    // --- Catch database errors, especially Foreign Key violations ---

    // MySQL error code 1451: Cannot delete or update a parent row (Foreign Key constraint fails)
    if ($e->errorInfo[1] == 1451) {
        $message = "Cannot delete promotion #" . $promo_id . ". It is still in use by one or more products.";
    } else {
        $message = "Database error: " . $e->getMessage();
        error_log("PDOException in del_promo.php: " . $e->getMessage());
    }
}

// --- Redirect back to the promotions list ---
// (Note: Your nav links use .html, but logic implies .php. I'm using .php)
header("Location: ../../promotions.html?status=" . $status . "&msg=" . urlencode($message));
exit;
?>