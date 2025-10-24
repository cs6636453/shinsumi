<?php
    include "../../db/connect.php";

    $stmt = $pdo -> prepare("SELECT first_name, last_name FROM gs_member WHERE username = ?");
    $stmt -> bindParam(1, $_SESSION['username']);
    $stmt -> execute();

    $row = $stmt -> fetch();
    $_SESSION['first_name'] = $row['first_name'];
    $_SESSION['last_name'] = $row['last_name'];

    if ($_SESSION['username'] != NULL) {
        echo '<div class="account-container">';
        echo '<a href="" id="account-button" class="top_right_nav">
                <span class="material-symbols-outlined top_right_nav">account_circle</span>
              </a>';
        echo '<div id="account-popup" class="account-menu">
                <a href="" class="account-header">'.$_SESSION['first_name'].' '.$_SESSION['last_name'].'</a>
                <a href="../tracking/">คำสั่งซื้อของคุณ</a>
                <div class="menu-divider"></div>
                <a href="../logout_request/">ออกจากระบบ</a>
              </div>';
        echo '</div>';
        echo '<a href="../cart/" id="my_cart"><span class="material-symbols-outlined top_right_nav">shopping_cart</span></a>';
    } else {
        echo '<a href="../login_request/" id="login">LOGIN</a>';
    }

?>

