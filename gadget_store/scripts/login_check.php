<?php
    include "../db/connect.php";
    if ($_SESSION['username'] != NULL) {
        echo '<a href="logout_request/" id="login">'.$_SESSION['username'].'</a>';
    } else {
        echo '<a href="login_request/" id="login">LOGIN</a>';
    }

?>

