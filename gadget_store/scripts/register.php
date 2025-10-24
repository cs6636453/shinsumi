<?php
    include "../db/connect.php";
    
    $stmt_chk = $pdo -> prepare("select username from gs_member where username = ?");
    $stmt_chk -> bindParam(1, $_POST['username']);
    $stmt_chk -> execute();
    $row_chk = $stmt_chk -> fetch();
    if ($row_chk['username'] != "") {
        echo 'username_existed';
    } else if ($_POST['password'] == $_POST['cf_password']) {
        $hash_post = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo -> prepare("INSERT INTO `gs_member` (`username`,
                         `password`, `first_name`, `last_name`, `email`,
                         `phone`, `address`, `creation_date`, `coin`,
                         `province`, `postal_code`, `account_status`)
                         VALUES (?, ?, ?, ?, ?, ?, ?, current_timestamp(), '0', ?, ?, 'customer')");
        $stmt -> bindParam(1, $_POST['username']);
        $stmt -> bindParam(2, $hash_post);
        $stmt -> bindParam(3, $_POST['first_name']);
        $stmt -> bindParam(4, $_POST['last_name']);
        $stmt -> bindParam(5, $_POST['email']);
        $stmt -> bindParam(6, $_POST['tel']);
        $stmt -> bindParam(7, $_POST['address']);
        $stmt -> bindParam(8, $_POST['province']);
        $stmt -> bindParam(9, $_POST['postal']);
        $stmt -> execute();
        $_SESSION['username'] = $_POST['username'];
        echo 'success';
    } else {
        echo 'password_mismatch';
    }
?>

