<?php
include "../db/connect.php";
if ($_SESSION['username'] == null) header("location: ../login_request/");

$stmt = $pdo -> prepare("select o.status, o.ord_id, p.pname, p.pid, oi.price_each, oi.quantity, sum(oi.quantity * oi.price_each)
                               as 'total', o.address, o.payment_method from gs_orders o
                               join gs_orders_item oi on o.ord_id = oi.ord_id
                               join gs_product p on p.pid = oi.pid
                               join gs_member m on m.username = o.username
                               where m.username = ? and o.ord_id = ?
                               group by p.pid");
$stmt -> bindParam(1, $_SESSION['username']);
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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@1" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/style/index.css">
    <title>คำสั่งซื้อทั้งหมด | We are Gadget Store</title>
    <link rel="stylesheet" href="../assets/style/login_form.css">
    <link rel="stylesheet" href="../assets/style/desktop_customer.css">

    <style>
        /* --- Global --- */
        main.myMain {
            border: none;
            padding: 0;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        main.myMain h1 {
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px; /* (ใหม่) เพิ่มระยะห่าง */
        }

        main.myMain h2 {
            font-size: 1.3em;
            margin-bottom: 15px;
            margin-top: 25px;
        }

        /* --- (โค้ดเดิมของนาย) Button (เผื่อใช้) --- */
        .btn {
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }
        .btn-primary {
            background-color: #000000;
            color: #ffffff;
            border: 1px solid #000000;
        }
        .btn-primary:hover {
            background-color: #333333;
            border-color: #333333;
        }

        /* --- (ใหม่) CSS สำหรับหน้ารายละเอียดออเดอร์ --- */

        /* การ์ดสรุปข้อมูล */
        .order-summary-card {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
        }
        .order-summary-card h2 {
            font-size: 1.2em;
            margin-top: 0;
            margin-bottom: 15px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr; /* 1 คอลัมน์สำหรับ mobile */
            gap: 12px;
        }
        .summary-grid p {
            margin: 0;
            line-height: 1.5;
            word-wrap: break-word; /* ตัดคำ */
        }

        /* ป้ายสถานะ */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px; /* (ใหม่) ทำให้มนๆ */
            font-weight: bold;
            font-size: 0.9em;
            color: #fff;
        }
        .status-badge.pending,
        .status-badge.packing {
            background-color: #ffc107; /* สีเหลือง */
            color: #333;
        }
        .status-badge.shipping {
            background-color: #17a2b8; /* สีฟ้า */
        }
        .status-badge.completed {
            background-color: #28a745; /* สีเขียว */
        }
        .status-badge.cancelled,
        .status-badge.refunded,
        .status-badge.failed {
            background-color: #d9534f; /* สีแดง */
        }

        /* รายการสินค้า */
        .order-item-list {
            display: flex;
            flex-direction: column;
            margin-top: 10px;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden; /* (ใหม่) สำหรับขอบมน */
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }
        .item-details {
            flex-grow: 1;
            min-width: 0; /* Fix บั๊ก text-overflow */
        }
        .item-details h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .item-details p {
            margin: 0;
            font-size: 0.9em;
            color: #777;
        }
        .item-line-total {
            text-align: right; /* (ใหม่) ชิดขวา */
            font-size: 1.1em;
            font-weight: bold;
            white-space: nowrap;
        }

        /* สรุปยอดรวม */
        .order-total-summary {
            margin-top: 20px;
            padding: 15px 20px;
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-row span {
            font-size: 1.2em;
            color: #555;
        }
        .total-row strong {
            font-size: 1.4em;
            font-weight: bold;
        }

        /* ข้อความตอนไม่เจอออเดอร์ */
        .order-empty-message {
            padding: 40px 20px;
            text-align: center;
            color: #777;
            border: 2px dashed #eee;
            border-radius: 8px;
        }
    </style>
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
            <a href="../">GADGET STORE</a>
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
    <h1>คำสั่งซื้อที่ #<?=htmlspecialchars($_GET['id'])?></h1>

    <?php
    // --- 1. (ใหม่) ดึงข้อมูลทั้งหมดมาเก็บใน Array ก่อน ---
    $all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grand_total = 0;

    if (count($all_items) > 0) {
        // --- 2. (ใหม่) ดึงข้อมูลสรุปจากแถวแรก ---
        $summary = $all_items[0];
        $status = htmlspecialchars($summary['status']);
        $address = nl2br(htmlspecialchars($summary['address'])); // แปลง \n เป็น <br>
        $payment = ($summary['payment_method'] == 'cod') ? 'ชำระเงินปลายทาง' : htmlspecialchars($summary['payment_method']);
        ?>

        <h2>รายการสินค้าในออเดอร์</h2>
        <section class="order-item-list">
            <?php
            foreach ($all_items as $item) {
                $grand_total += $item['total']; // ใช้ 'total' จาก SQL
                ?>
                <div class="order-item">
                    <img src="../assets/images/products/<?=$item['pid']?>" alt="<?=htmlspecialchars($item['pname'])?>">
                    <div class="item-details">
                        <h4><?=htmlspecialchars($item['pname'])?></h4>
                        <p><?=number_format($item['price_each'], 2)?> บาท x <?=$item['quantity']?> ชิ้น</p>
                    </div>
                    <div class="item-line-total">
                        <?=number_format($item['total'], 2)?> บาท
                    </div>
                </div>
            <?php } ?>
        </section>

        <div class="order-total-summary">
            <div class="total-row">
                <span>ยอดรวมทั้งหมด</span>
                <strong><?=number_format($grand_total, 2)?> บาท</strong>
            </div>
        </div>

        <h2>สรุปคำสั่งซื้อ</h2>
        <section class="order-summary-card">
            <div class="summary-grid">
                <p><strong>สถานะ:</strong>
                    <span class="status-badge <?=$status?>"><?=$status?></span>
                </p>
                <p><strong>วิธีชำระเงิน:</strong> <?=$payment?></p>
                <p class="address-full"><strong>ที่อยู่จัดส่ง:</strong><br><?=$address?></p>
            </div>
        </section>

        <?php
    } else {
        // --- 3. (ใหม่) กรณีไม่พบออเดอร์ ---
        echo "<p class='order-empty-message'>ไม่พบข้อมูลคำสั่งซื้อนี้ หรือคำสั่งซื้อนี้ไม่ใช่ของคุณ</p>";
    }
    ?>

</main>
<footer>
    <h1>TEL 095-484-9802</h1>
    <p>9:00 - 17:00 จันทร์-ศุกร์</p>
    <p>คำสงวนสิทธิ์: หน้าเว็บนี้มีจุดประสงค์เพื่อส่งงานอาจารย์เท่านั้น</p>
    <br>
    <b>GADGET STORE</b>
</footer>

<script src="../scripts/index.js"></script>
<script src="../scripts/login_req.js"></script>
</body>
</html>