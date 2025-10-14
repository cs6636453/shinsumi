<?php include "connect.php"?>

<?php
    $stmt = $pdo -> prepare("SELECT o.status, o.ord_id, p.pname, p.pid, p.price, oi.quantity, SUM(oi.quantity * p.price) AS 'total', m.first_name, m.last_name, m.address, m.sub_district, m.district, m.postal_code, m.phone, o.payment_method, m.province FROM gs_orders o JOIN gs_orders_item oi ON o.ord_id = oi.ord_id JOIN gs_product p ON p.pid = oi.pid JOIN gs_member m ON m.username = o.username WHERE m.username = ? AND o.ord_id = ? GROUP BY p.pid;");
    $stmt -> bindParam(1,$_GET['username']);
    $stmt -> bindParam(2, $_GET['id']);
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
    <title>Detail | We are Gadget Store</title>
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
    <p class="cart_item">

        <table border="1">
            <tr>
                <th>ชื่อสินค้า</th>
                <th>รหัสสินค้า</th>
                <th>ราคาต่อชิ้น</th>
                <th>จำนวน</th>
                <th>ราคารวม</th>
            </tr>
        <?php
        $total = 0;
        $order = $_GET['id'];
        $status = '';
        $address = '';
        $district = '';
        $subdistrict = '';
        $postal = '';
        $payment = '';
        $tel = '';
            while ($row = $stmt -> fetch()) {
                $order = $row['ord_id'];
                $name = $row['first_name'];
                $lastname = $row['last_name'];
                $status = $row['status'];
                $address = $row['address'];
                $district = $row['district'];
                $subdistrict = $row['sub_district'];
                $postal = $row['postal_code'];
                $payment = $row['payment_method'];
                $province = $row['province'];
                $tel = $row['phone'];
        ?>

                            <tr>
                                <td><?=$row['pname']?></td>
                                <td><?=$row['pid']?></td>
                                <td><?=$row['price']?></td>
                                <td><?=$row['quantity']?></td>

                                <td><?=$row['total']?></td>
                            </tr>

                <?php
                    $total += $row['total'];
                    }
                ?>
            </table>
            <p>Total: <?=$total?></p>
            <hr>
    <section style="display: flex;">
        <?php




        ?>
        <p>Order <?=$order?></p>
        <p style="background-color: black; color: white;"> <?=$status?></p>
    </section>
            <p>Order Information</p>
            <p>Shipping Address</p>
            <p><?=$name?> <?=$lastname?></p>
            <p><?=$address?></p>
        <p><?=$subdistrict?>, <?=$district?>, <?=$province?>, <?=$postal?></p>
        <p>Tel. <?=$tel?></p>
    <p>Payment method</p>
    <p><?=$payment?></p>
        </section>
</main>
<script src="../api/index.js"></script>
</body>
