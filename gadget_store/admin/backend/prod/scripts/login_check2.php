<?php
include "../../../../db/connect.php";

$stmt = $pdo -> prepare("SELECT first_name FROM gs_member WHERE username = ?");
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
    echo 'ยินดีต้อนรับ '.$_SESSION['first_name'];
}

?>

