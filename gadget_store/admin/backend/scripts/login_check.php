<?php
include "../../../db/connect.php";

$stmt = $pdo -> prepare("SELECT first_name, last_name FROM gs_member WHERE username = ?");
$stmt -> bindParam(1, $_SESSION['username']);
$stmt -> execute();
$row = $stmt -> fetch();
$_SESSION['first_name'] = $row['first_name'];
$_SESSION['last_name'] = $row['last_name'];

$cartStmt = $pdo->prepare("SELECT COUNT(pid) AS total_items FROM gs_cart WHERE username = ?");
$cartStmt->bindParam(1, $_SESSION['username']);
$cartStmt->execute();
$cartRow = $cartStmt->fetch();

$cartCount = $cartRow['total_items'];

if ($_SESSION['username'] != NULL) {
    echo '<a href="../logout_request/" id="my_cart"><span class="material-symbols-outlined top_right_nav">logout</span>';
    echo '</a>';
} else {
    echo "PLEASE LOGIN";
}

?>

