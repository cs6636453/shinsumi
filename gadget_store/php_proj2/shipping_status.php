<?php include "connect.php"?>

<?php
    $stmt = $pdo -> prepare("SELECT o.ord_id, o.ord_date, m.first_name, m.last_name, SUM(p.price*oi.quantity) AS total, o.status FROM gs_orders o
JOIN gs_orders_item oi ON o.ord_id = oi.ord_id 
JOIN gs_product p ON p.pid = oi.pid
JOIN gs_member m ON m.username = o.username
WHERE m.username = ?
GROUP BY o.ord_id;");
    $stmt -> bindParam(1,$_GET['username']);
    $stmt -> execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <link rel="icon" href="../assets/favicon/xobazjr.ico" type="image/x-icon" />
    <link rel="stylesheet" href="../assets/style/nav.css" />
    <link rel="stylesheet" href="../assets/style/global.css" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/style/index.css">
    <link rel="stylesheet" href="../assets/style/phpDemo.css">
    <title>Shipping | We are Gadget Store</title>
</head>
<body>
<nav>

    <div id="top_row">
        <!--ปุ่มเลือกเมนู-->
        <section class="container" id="menu_btn" onclick="animateMenuButton(this)">
            <div class="bar1"></div>
            <div class="bar2"></div>
            <div class="bar3"></div>
        </section>

        <!--ชื่อร้าน-->
        <section id="shop_name">
            <a href="#">GADGET STORE</a>
        </section>

        <!--ปุ่ม Login-->
        <section id="login_btn">
            <a href="login">
                <?=$_GET['username']?>
            </a>
        </section>
    </div>

    <div id="bottom_row">
        <!--แถบค้นหา-->
        <section id="search_box">
            <form action="search/" id="search_form">
                <label for="search_param"></label><input type="text" name="search" placeholder="ค้นหาสินค้า..." id="search_param">
                <button type="submit"><span class="material-symbols-outlined">search</span></button>
            </form>
        </section>
    </div>
</nav>

<main style="margin-left: 15px;
             margin-right: 15px;">
    <!--dev here-->
    <h2>คำสั่งซื้อของคุณ</h2>
        <?php
            $total = 0;
            while ($row = $stmt -> fetch()) {
        ?>
                <section class="cart_item">
                    <p><b>รายการสั่งซื้อ:</b> <?=$row['ord_id']?></p>
                    <p><b>วันที่:</b> <?=$row['ord_date']?></p>
                    <p><b>ชื่อผู้รับ:</b> <?=$row['first_name']?> <?=$row['last_name']?></p>
                    <p><b>รวมทั้งหมด:</b> <?=$row['total']?> บาท</p>
                    <p><b>สถานะ:</b> <?=$row['status']?></p>
                    <hr>
                </section>
        <?php
            }
        ?>
</main>
<script src="../api/index.js"></script>
</body>
