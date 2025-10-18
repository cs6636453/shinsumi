<?php
    include "../db/connect.php";
    $stmt = $pdo -> prepare("SELECT password FROM gs_member WHERE username = ?");
    $stmt -> bindParam(1, $_GET['username']);
    $stmt -> execute();
    $row = $stmt -> fetch();

    if ($_GET['password'] == $row['password']) {
        $_SESSION['username'] = $_GET['username'];
        echo 'Success';
    } else {
        echo 'Error';
    }
?>

