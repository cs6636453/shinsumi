<?php
    include "../db/connect.php";
    $stmt_chk = $pdo -> prepare("select username from gs_member where username = ?");
    $stmt_chk -> bindParam(1, $_POST['username']);
    $stmt_chk -> execute();
    $row_chk = $stmt_chk -> fetch();
    if ($row_chk['username'] != "") {
        echo 'username_existed';
    } else {
        echo '';
    }
?>