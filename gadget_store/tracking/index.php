<?php
    include "../db/connect.php";
    if ($_SESSION['username'] == null) header("location: ../login_request/");

    $stmt = $pdo -> prepare("SELECT o.ord_id, o.ord_date, SUM(oi.price_each * oi.quantity)
                                   AS total, o.status, o.address FROM gs_orders o JOIN gs_orders_item oi ON o.ord_id = oi.ord_id
                                   JOIN gs_product p ON p.pid = oi.pid JOIN gs_member m ON m.username = o.username
                                   WHERE m.username = ? GROUP BY o.ord_id;");
    $stmt -> bindParam(1, $_SESSION['username']);
    $stmt -> execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <link rel="icon" href="../assets/favicon/xobazjr.ico" type="image/x-icon" />
    <link rel="stylesheet" href="../assets/style/nav.css" />
    <link rel="stylesheet" href="../assets/style/global.css" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@1" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/style/index.css">
    <title>คำสั่งซื้อทั้งหมด | We are Gadget Store</title>
    <link rel="stylesheet" href="../assets/style/login_form.css">
    <link rel="stylesheet" href="../assets/style/desktop_customer.css">
    <link rel="stylesheet" href="inner.css">
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
            <a href="../index.html">GADGET STORE</a>
        </section>

        <!--ปุ่ม Login-->
        <section id="login_btn">
            <a id="login"><img src="../assets/images/loading.gif" alt="loading"></a>
        </section>
    </div>

    <div id="bottom_row">
        <!--แถบค้นหา-->
        <section id="search_box">
            <form action="../search/" id="search_form">
                <label for="search_param"></label><input type="text" name="search" placeholder="ค้นหาสินค้า..." id="search_param">
                <button type="submit"><span class="material-symbols-outlined">search</span></button>
            </form>
        </section>
    </div>
</nav>

<div id="side-nav-overlay"></div>

<div id="side-nav-menu" class="side-nav">
    <ul class="side-nav-list">
        <li>
            <button class="nav-item-button">
                <span>โปรโมชัน</span>
                <span class="material-symbols-outlined plus-icon">add</span>
            </button>
            <ul class="sub-menu">
                <li><a href="/search/?search=promo1">โปรโมชัน 1</a></li>
                <li><a href="/search/?search=promo2">โปรโมชัน 2</a></li>
            </ul>
        </li>
        <li>
            <button class="nav-item-button">
                <span>เคส</span>
                <span class="material-symbols-outlined plus-icon">add</span>
            </button>
            <ul class="sub-menu">
                <li><a href="/search/?search=case_iphone">เคส iPhone</a></li>
                <li><a href="/search/?search=case_samsung">เคส Samsung</a></li>
            </ul>
        </li>
        <li>
            <button class="nav-item-button">
                <span>กระเป๋า</span>
                <span class="material-symbols-outlined plus-icon">add</span>
            </button>
            <ul class="sub-menu">
                <li><a href="/search/?search=bag_tote">Tote Bag</a></li>
                <li><a href="/search/?search=bag_sling">Sling Bag</a></li>
            </ul>
        </li>
        <li>
            <button class="nav-item-button">
                <span>อุปกรณ์เสริม</span>
                <span class="material-symbols-outlined plus-icon">add</span>
            </button>
            <ul class="sub-menu">
                <li><a href="/search/?search=acc_charger">ที่ชาร์จ</a></li>
                <li><a href="/search/?search=acc_film">ฟิล์ม</a></li>
            </ul>
        </li>
        <li>
            <button class="nav-item-button">
                <span>เพิ่มเติม</span>
                <span class="material-symbols-outlined plus-icon">add</span>
            </button>
            <ul class="sub-menu">
                <li><a href="/about_us">เกี่ยวกับเรา</a></li>
                <li><a href="/contact">ติดต่อเรา</a></li>
            </ul>
        </li>
    </ul>
</div>
<main class="myMain cart-page-container">
    <h1>คำสั่งซื้อทั้งหมดของคุณ</h1>
    <section class="my_orders">
        <?php
        // 1. (แก้ไข) ดึงข้อมูลทั้งหมดมาเก็บใน Array ก่อน
        $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. (แก้ไข) เช็กจำนวนแถวจาก Array
        if (count($all_orders) == 0) {
            echo "<p class='order-empty-message'>ไม่พบคำสั่งซื้อของท่าน</p>";
        } else {
            // 3. (แก้ไข) วนลูปจาก Array
            $i = 0;
            foreach ($all_orders as $row) {
                if ($i > 0) echo "<hr>";
                ?>
                <a href="detail.php?id=<?=$row['ord_id']?>" class="order-link">
                    <p><b>คำสั่งซื้อที่: </b><?=$row['ord_id']?></p>
                    <p><b>วันที่: </b><?=$row['ord_date']?></p>
                    <p><b>ชื่อและที่อยู่ผู้รับ: </b><?= nl2br(htmlspecialchars($row['address'])) // (ใหม่) เผื่อที่อยู่มีหลายบรรทัด ?></p>
                    <p><b>ราคารวม: </b><?=number_format($row['total'], 2) // (ใหม่) จัด format ราคา ?> บาท</p>
                    <p><b>สถานะ: </b><?= htmlspecialchars($row['status']) // (ใหม่) ป้องกัน XSS ?></p>
                </a>
                <?php
                $i++;
            }
        }
        ?>
    </section>
</main>
<footer>
    <h1>TEL 095-484-9802</h1>
    <p>9:00 - 17:00 จันทร์-ศุกร์</p>
    <p>คำสงวนสิทธิ์: หน้าเว็บนี้มีจุดประสงค์เพื่อส่งงานอาจารย์เท่านั้น</p>
    <br>
    <b>GADGET STORE</b>
</footer>
<script src="inner.js"></script>

<script src="../scripts/index.js"></script>
<script src="../scripts/login_req.js"></script>
</body>
</html>