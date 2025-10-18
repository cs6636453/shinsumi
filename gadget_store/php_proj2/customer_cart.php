<?php include "connect.php"?>

<?php
    $stmt = $pdo -> prepare("SELECT p.pname as 'item', c.quantity AS 'quantity', p.price AS 'price',
                                    SUM(p.price*c.quantity) AS 'sum_price' FROM gs_product p
                                    JOIN gs_cart c ON c.pid = p.pid
                                    JOIN gs_member m ON m.username = c.username
                                    WHERE c.username = ?
                                    GROUP BY p.pid;");
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
    <title>Cart | We are Gadget Store</title>
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
    <h2>Cart</h2>
        <?php
            $total = 0;
            while ($row = $stmt -> fetch()) {
        ?>
                <section class="cart_item">
                    <p><?=$row['item']?></p>
                    <p><?=$row['price']?> บาท</p>
                    <?php
                        if ($row['quantity'] > 1) {
                            echo "<p>รวม: ".$row['sum_price']." บาท"."</p>";
                        }
                    ?>
                    <p style="font-weight: bold;
                              text-align: right;"><?=$row['quantity']?> ชิ้น</p>
                    <hr>
                </section>
        <?php
                $total += $row['sum_price'];
            }
        ?>
    <h4>ราคารวม: <?=$total?></h4>
</main>
<script src="../scripts/index.js"></script>
</body>
