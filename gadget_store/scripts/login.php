<?php
    include "../db/connect.php";
    $stmt = $pdo -> prepare("SELECT password FROM gs_member WHERE username = ?");
    $stmt -> bindParam(1, $_POST['username']);
    $stmt -> execute();
    $row = $stmt -> fetch();

    if (password_verify($_POST['password'], $row['password'])) {
        $_SESSION['username'] = $_POST['username'];
        echo 'Success';
    } else {
        echo 'Error';
    }
?>

