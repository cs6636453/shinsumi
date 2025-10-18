<?php
    include "../db/connect.php";
    
    $stmt_chk = $pdo -> prepare("select username from gs_member where username = ?");
    $stmt_chk -> bindParam(1, $_GET['username']);
    $stmt_chk -> execute();
    $row_chk = $stmt_chk -> fetch();
    if ($row_chk['username'] != "") {
        echo 'username_existed';
    } else if ($_GET['password'] == $_GET['cf_password']) {
        $stmt = $pdo -> prepare("INSERT INTO `gs_member` (`username`,
                         `password`, `first_name`, `last_name`, `email`,
                         `phone`, `address`, `creation_date`, `coin`,
                         `province`, `postal_code`, `account_status`)
                         VALUES (?, ?, ?, ?, ?, ?, ?, current_timestamp(), '0', ?, ?, 'customer')");
        $stmt -> bindParam(1, $_GET['username']);
        $stmt -> bindParam(2, $_GET['password']);
        $stmt -> bindParam(3, $_GET['first_name']);
        $stmt -> bindParam(4, $_GET['last_name']);
        $stmt -> bindParam(5, $_GET['email']);
        $stmt -> bindParam(6, $_GET['tel']);
        $stmt -> bindParam(7, $_GET['address']);
        $stmt -> bindParam(8, $_GET['province']);
        $stmt -> bindParam(9, $_GET['postal']);
        $stmt -> execute();
        $row = $stmt -> fetch();

        $_SESSION['username'] = $_GET['username'];
        echo 'success';
    } else {
        echo 'password_mismatch';
    }
?>

