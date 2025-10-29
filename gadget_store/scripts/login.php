<?php
    include "../db/connect.php";
    $stmt = $pdo -> prepare("SELECT account_status, password FROM gs_member WHERE username = ?");
    $stmt -> bindParam(1, $_POST['username']);
    $stmt -> execute();
    $row = $stmt -> fetch();

    if (password_verify($_POST['password'], $row['password'])) {
        $_SESSION['username'] = $_POST['username'];
        if ($row['account_status'] == 'customer') {
            echo 'Success';
        } else if ($row['account_status'] == 'admin') {
            echo 'Admin';
        }
    } else {
        echo 'Error';
    }
?>

