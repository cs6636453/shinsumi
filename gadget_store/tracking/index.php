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

        .btn-secondary {
            background-color: #ffffff;
            color: #888888;
            border: 1px solid #cccccc;
        }

        .btn-secondary:hover {
            background-color: #f5f5f5;
            border-color: #bbbbbb;
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

        /* --- (ใหม่) CSS สำหรับหน้ารายการออเดอร์ --- */
        .my_orders {
            /* (แก้ไข) เปลี่ยนจาก border เป็น flex */
            display: flex;
            flex-direction: column;
            gap: 15px; /* เว้นช่องไฟระหว่างการ์ด */
        }

        .order-link {
            display: block;
            padding: 15px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none !important; /* (แก้ไข) ทำให้ลิงก์ไม่มีเส้นใต้ */
            color: #333 !important; /* (แก้ไข) ทำให้ลิงก์เป็นสีดำ */
            transition: background-color 0.2s, box-shadow 0.2s;
        }

        .order-link:hover {
            background-color: #f9f9f9;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .order-link p {
            margin: 0 0 8px 0;
            line-height: 1.5;
            word-wrap: break-word; /* ตัดคำที่อยู่ยาวๆ */
        }

        .order-link p:last-child {
            margin-bottom: 0;
        }

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
<script>
    document.addEventListener("DOMContentLoaded", function() {

        // 1. หา Element ที่เราต้องใช้บ่อยๆ
        const grandTotalElement = document.getElementById('cart-grand-total');

        // 2. วนลูปหา .cart_item (แทน .quantity-input)
        document.querySelectorAll('.cart_item').forEach(function(cartItem) {

            // 3. หาค่าต่างๆ จาก data- attributes ที่เราเพิ่มใน HTML
            const pid = cartItem.dataset.pid;
            const unitPrice = parseFloat(cartItem.dataset.unitPrice);

            // 4. หาปุ่ม/ช่องตัวเลข/ปุ่มลบ "ภายใน" cartItem นี้
            const numberInput = cartItem.querySelector('.qt-number');
            const minusBtn = cartItem.querySelector('.qt-minus');
            const plusBtn = cartItem.querySelector('.qt-plus');
            const removeBtn = cartItem.querySelector('.remove-btn');
            const lineTotalElement = cartItem.querySelector('.cart-item-line-total');

            // 5. เมื่อคลิกปุ่มบวก
            plusBtn.addEventListener('click', function() {
                let newQuantity = parseInt(numberInput.value) + 1;
                numberInput.value = newQuantity;
                sendCartUpdate(pid, newQuantity); // ส่งอัปเดตไปเบื้องหลัง
                updatePrices(); // อัปเดตราคาบนหน้าเว็บทันที
            });

            // 6. เมื่อคลิกปุ่มลบ
            minusBtn.addEventListener('click', function() {
                let newQuantity = parseInt(numberInput.value) - 1;

                if (newQuantity >= 0) {
                    numberInput.value = newQuantity;
                    sendCartUpdate(pid, newQuantity); // ส่งอัปเดต
                    updatePrices(); // อัปเดตราคา
                }

                if (newQuantity === 0) {
                    // ถ้าเหลือ 0, ให้ลบแถวนั้นทิ้ง
                    cartItem.remove();
                }
            });

            // 7. เมื่อคลิกปุ่มถังขยะ (ลบ)
            removeBtn.addEventListener('click', function() {
                sendCartUpdate(pid, 0); // ส่งค่า 0 (ลบ) ไปเบื้องหลัง
                cartItem.remove(); // ลบแถวนี้ทิ้งทันที
                updatePrices(); // อัปเดตราคา
            });
        });

        // 8. (แก้ไข) ฟังก์ชันสำหรับ "คำนวณและอัปเดต" ยอดรวมทั้งหมด
        function updatePrices() {
            // (ใหม่) เพิ่มตัวแปรสำหรับราคารวม (เต็ม)
            let newGrandTotal_Final = 0;
            let newGrandTotal_Original = 0;

            // (ใหม่) หา Element ของแถวสรุปผลทั้งหมด
            const originalTotalElement = document.getElementById('cart-original-total');
            const discountTotalElement = document.getElementById('cart-discount-total');
            const finalTotalElement = document.getElementById('cart-grand-total');

            // วนลูป .cart_item "ทั้งหมด" ที่ยังเหลืออยู่
            document.querySelectorAll('.cart_item').forEach(function(item) {

                // (แก้ไข) อ่าน data attribute ทั้ง 2 ค่า
                const price_final = parseFloat(item.dataset.unitPriceFinal);
                const price_original = parseFloat(item.dataset.unitPriceOriginal);
                const quantity = parseInt(item.querySelector('.qt-number').value);

                // (แก้ไข) คำนวณราคารวม (หลังลด) ของแถว
                const lineTotal_Final = price_final * quantity;
                // (ใหม่) คำนวณราคารวม (เต็ม) ของแถว
                const lineTotal_Original = price_original * quantity;

                // (แก้ไข) อัปเดตราคารวมของ "แถว" นั้น
                item.querySelector('.cart-item-line-total').textContent = lineTotal_Final.toLocaleString() + ' บาท';

                // (แก้ไข) บวกยอดเข้ากับยอดรวมทั้งหมด
                newGrandTotal_Final += lineTotal_Final;
                newGrandTotal_Original += lineTotal_Original;
            });

            // (ใหม่) คำนวณส่วนลดรวม
            let newDiscountTotal = newGrandTotal_Original - newGrandTotal_Final;

            // (ใหม่) อัปเดต Element ทั้ง 3 ตัวในกล่องสรุป
            if (originalTotalElement) {
                originalTotalElement.textContent = newGrandTotal_Original.toLocaleString() + ' บาท';
            }
            if (discountTotalElement) {
                discountTotalElement.textContent = '- ' + newDiscountTotal.toLocaleString() + ' บาท';
            }
            if (finalTotalElement) {
                finalTotalElement.textContent = newGrandTotal_Final.toLocaleString() + ' บาท';
            }

            // (เดิม) ถ้าตะกร้าว่าง ให้ reload
            if (newGrandTotal_Final === 0 && document.querySelectorAll('.cart_item').length === 0) {
                window.location.reload();
            }
        }

        // 9. (เดิม) ฟังก์ชันสำหรับส่งข้อมูลไปอัปเดตตะกร้า (เบื้องหลัง)
        // (ฟังก์ชันนี้เหมือนเดิมเป๊ะ ไม่ต้องแก้)
        function sendCartUpdate(pid, quantity) {
            console.log(`กำลังอัปเดต: PID=${pid}, จำนวน=${quantity}`);

            fetch('../cart/update_cart_quantity.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `pid=${pid}&quantity=${quantity}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('อัปเดตตะกร้าสำเร็จ!');
                    } else {
                        console.error('อัปเดตล้มเหลว:', data.message);
                    }
                })
                .catch(error => {
                    console.error('เกิดข้อผิดพลาด:', error);
                });
        }
    });
</script>

<script src="../scripts/index.js"></script>
<script src="../scripts/login_req.js"></script>
</body>
</html>