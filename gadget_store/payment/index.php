<?php
include "../db/connect.php";
if ($_SESSION['username'] == null) header("location: ../login_request/");

$stmt = $pdo -> prepare("select first_name, last_name, phone, address, province, postal_code, account_status
from gs_member
where username = ?;");
$stmt -> bindParam(1, $_SESSION['username']);
$stmt -> execute();
$row = $stmt -> fetch();

$stmt2 = $pdo -> prepare("select p.pid, p.pname, sum(c.quantity) as quantity, p.price, sum(p.price*quantity) as 'subtotal', pr.discount_type, pr.discount_value, pr.end_date, ca.category_name
                                    from gs_product p join gs_cart c on c.pid = p.pid
                                    join gs_member m on m.username = c.username
                                    left join gs_promotion pr on pr.pr_id = p.pr_id
                                    join gs_category ca on ca.category_id = p.category_id
                                    where c.username = ? group by p.pid;");
$stmt2 -> bindParam(1, $_SESSION['username']);
$stmt2 -> execute();

$total = 0;
while ($cart_row = $stmt2 -> fetch()) { // 2. วนลูป $stmt2 (ตะกร้า) และใช้ตัวแปรใหม่ $cart_row

    // --- 3. เอา Logic คำนวณส่วนลดจากหน้าตะกร้ามาใช้ ---
    $item_unit_price = 0;
    $original_price = (float)$cart_row['price'];
    $quantity = (int)$cart_row['quantity'];
    $discount_type = $cart_row['discount_type'];
    $discount_value = (float)$cart_row['discount_value'];

    if ($discount_type == "fixed") {
        $item_unit_price = $original_price - $discount_value;
    } else if ($discount_type == "percent") {
        // (สมมติว่า percent เก็บเป็น 0.xx เช่น 0.15)
        $item_unit_price = $original_price - ($original_price * $discount_value);
    } else {
        $item_unit_price = $original_price;
    }

    $item_unit_price_rounded = round($item_unit_price);

    // 4. คำนวณราคารวมของแถวนี้ (ราคาต่อชิ้น * จำนวน)
    $final_line_total = $item_unit_price_rounded * $quantity;

    // 5. บวกเข้ายอดรวมทั้งหมด
    $grand_total_final += $final_line_total;
}

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
    <title>ยืนยันคำสั่งซื้อ | We are Gadget Store</title>
    <link rel="stylesheet" href="../assets/style/login_form.css">

    <style>
        /* --- Global --- */
        main.myMain {
            border: none;
            padding: 0;
            /* (ใหม่) ปรับ layout หลักสำหรับ mobile */
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        main.myMain h1 {
            font-size: 1.5em; /* (ใหม่) ปรับขนาดสำหรับ mobile */
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        s {
            color: gray;
        }

        /* --- Layout (Mobile-Only) --- */
        .cart-layout {
            display: flex;
            flex-direction: column; /* (ใหม่) บังคับเป็นแนวตั้ง */
            gap: 30px;
        }

        .cart-items-list {
            width: 100%;
        }

        .cart-summary {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 20px;
            height: fit-content;
            position: static; /* (ใหม่) ไม่ต้อง sticky */
            width: 100%;
            box-sizing: border-box;
        }

        /* --- Cart Item (Mobile-Only) --- */
        .cart_item {
            display: flex;
            align-items: center;
            gap: 15px; /* (ใหม่) ลดช่องไฟ */
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap; /* (ใหม่) บังคับให้ wrap ตลอด */
        }

        .cart_item img {
            width: 80px; /* (ใหม่) ปรับขนาดรูป */
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }

        .cart_detail {
            flex-grow: 1;
        }

        .cart_detail h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
        }

        .cart_detail p {
            margin: 0;
            font-size: 0.9em;
            color: #777;
        }

        .cart-item-unit-price {
            font-size: 1em;
            margin-top: 8px !important;
            color: #000 !important;
        }

        .cart-item-unit-price s {
            font-weight: normal;
            color: #999;
            margin-right: 5px;
        }

        /* --- Cart Controls (Mobile-Only) --- */
        .cart-controls {
            display: flex;
            width: 100%; /* (ใหม่) เต็มความกว้าง */
            flex-direction: row; /* (ใหม่) จัดแนวนอน */
            align-items: center;
            justify-content: space-between;
            margin-top: 10px; /* (ใหม่) เว้นระยะจาก detail */
        }

        .cart-item-line-total {
            font-size: 1.1em;
            font-weight: bold;
            color: #000;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #aaa;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .remove-btn:hover {
            color: #d9534f;
        }

        /* --- Summary --- */
        .cart-summary h2 {
            margin-top: 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 1.1em;
            margin-bottom: 20px;
        }

        .summary-row span:first-child {
            color: #555;
        }

        .summary-row span:last-child {
            font-size: 1.2em;
            font-weight: bold;
        }

        /* --- Cart Empty --- */
        .cart-empty {
            text-align: center;
            padding: 50px;
            border: 2px dashed #eee;
            border-radius: 8px;
        }

        /* --- (โค้ดเดิมของนาย) Quantity Input --- */
        .quantity-input {
            display: flex;
            width: fit-content;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }

        .quantity-input input {
            border: none;
            background-color: transparent;
            padding: 8px;
        }

        .quantity-input .qt-minus,
        .quantity-input .qt-plus {
            background-color: #f5f5f5;
            cursor: pointer;
            width: 35px;
            font-weight: bold;
            font-size: 1.1em;
            color: #555;
        }

        .quantity-input .qt-minus:hover,
        .quantity-input .qt-plus:hover {
            background-color: #e0e0e0;
        }

        .quantity-input .qt-number {
            width: 40px; /* (ใหม่) ลดขนาดช่องตัวเลข */
            text-align: center;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            font-size: 1em;
        }

        .quantity-input .qt-number {
            -moz-appearance: textfield;
        }

        .quantity-input .qt-number::-webkit-outer-spin-button,
        .quantity-input .qt-number::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* --- (โค้ดเดิมของนาย) Button --- */
        .btn {
            flex: 1;
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

        /* (ใหม่) ปรับ .btn-checkout ให้เข้ากับ mobile */
        .btn-checkout {
            width: 100%;
            box-sizing: border-box;
            padding: 15px;
            font-size: 1.1em;
        }

        /* ... (ใส่ต่อจาก CSS เดิม) ... */

        .summary-row.summary-discount span:last-child {
            color: #28a745; /* สีเขียวสำหรับส่วนลด (หรือ #d9534f ถ้าอยากได้สีแดง) */
            font-weight: bold;
        }

        .summary-row.summary-final {
            border-top: 1px solid #ddd;
            padding-top: 15px;
            margin-top: 10px;
        }

        .summary-row.summary-final span {
            font-size: 1.3em; /* เน้นยอดสุดท้าย */
            font-weight: bold;
        }

        /* ... (CSS เดิมของนาย) ... */

        /* --- (ใหม่) Layout หน้า Checkout --- */
        main.myMain h1 {
            font-size: 1.5em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .checkout-section {
            margin-bottom: 30px;
        }
        .checkout-section h2 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .section-header h2 {
            margin-bottom: 0;
        }
        .edit-link {
            font-size: 0.9em;
            color: #555;
            text-decoration: none;
        }
        .edit-link:hover {
            text-decoration: underline;
        }

        /* --- (ใหม่) สไตล์กล่อง (Card) --- */
        .checkout-card {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
        }
        .address-card strong {
            font-size: 1.1em;
        }
        .address-card p {
            font-size: 0.95em;
            color: #555;
            margin: 5px 0 0 0;
            line-height: 1.5;
        }

        /* --- (ใหม่) สไตล์กล่องตัวเลือก (Shipping/Payment) --- */
        .checkout-option-card {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        /* (ใหม่) สไตล์ตอนที่ถูกเลือก (active) */
        .checkout-option-card.active {
            border-color: #000; /* หรือสีม่วงแบบในรูป (border-color: #7a00ff;) */
            background: #fdfaff; /* สีม่วงอ่อนๆ (ถ้าต้องการ) */
        }

        .option-details {
            flex-grow: 1; /* ดันให้ขยายเต็ม */
        }
        .option-details h3 {
            margin: 0 0 3px 0;
            font-size: 1.1em;
        }
        .option-details p {
            margin: 0;
            font-size: 0.9em;
            color: #777;
        }
        .option-price {
            font-size: 1.1em;
            font-weight: bold;
            margin: 0 15px;
        }

        /* --- (ใหม่) สไตล์ Custom Radio Button --- */
        .custom-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            display: block;
            position: relative;
            transition: border-color 0.2s;
        }
        .checkout-option-card.active .custom-radio {
            border-color: #000; /* หรือสีม่วง (border-color: #7a00ff;) */
        }

        /* วงกลมด้านใน (ตอนถูกเลือก) */
        .custom-radio::after {
            content: '';
            width: 12px;
            height: 12px;
            background: #000; /* หรือสีม่วง (background: #7a00ff;) */
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0); /* ซ่อนไว้ก่อน */
            transition: transform 0.2s;
        }
        .checkout-option-card.active .custom-radio::after {
            transform: translate(-50%, -50%) scale(1); /* แสดงตอน active */
        }

        /* --- (ใหม่) สไตล์แถบสรุปยอดด้านล่าง (Footer Bar) --- */
        .order-summary-bar {
            left: 0;
            width: 100%;
            background: #ffffff;
            border-top: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            box-sizing: border-box; /* กัน padding ทะลุ */
            gap: 20px;
        }
        .total-price {
            display: flex;
            flex-direction: column;
        }
        .total-price span {
            font-size: 0.9em;
            color: #555;
        }
        .total-price strong {
            font-size: 1.4em;
            font-weight: bold;
        }
        .order-summary-bar .btn-checkout {
            padding: 12px 0; /* ปรับขนาดปุ่มตามชอบ */
            width: 50px;
            font-size: 1.1em;
            flex-shrink: 0; /* กันปุ่มหด */
        }

        /* (ใหม่) ตัวดันเนื้อหา ไม่ให้โดนแถบ footer บัง */
        .footer-padding {
            height: 100px; /* ความสูงเท่ากับ .order-summary-bar + 20px */
        }

        /* --- (จากโค้ดเดิม) สไตล์ Button (จำเป็นสำหรับปุ่ม 'สั่งซื้อ') --- */
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
    <h1>ชำระเงิน</h1>

    <div class="checkout-content">

        <section class="checkout-section">
            <div class="section-header">
                <h2>ที่อยู่จัดส่ง</h2>
                <a href="#" class="edit-link">แก้ไข</a>
            </div>
            <div class="checkout-card address-card">
                <strong><?=$row['first_name']?> <?=$row['last_name']?> (<?=$row['phone']?>)</strong>
                <p>
                    <?=$row['address']?>, <?=$row['province']?>, <?=$row['postal_code']?>
                </p>
            </div>
        </section>

        <section class="checkout-section">
            <h2>วิธีจัดส่ง</h2>

            <label class="checkout-option-card active">
                <input type="radio" name="shipping" value="standard" checked hidden>

                <div class="option-details">
                    <h3>จัดส่งปกติ</h3>
                    <p>ได้รับสินค้าภายใน 1-3 วัน</p>
                </div>

                <strong class="option-price">ฟรี</strong>

                <span class="custom-radio"></span>
            </label>
        </section>

        <section class="checkout-section">
            <h2>วิธีชำระเงิน</h2>

            <label class="checkout-option-card active">
                <input type="radio" name="payment" value="cod" checked hidden>

                <div class="option-details">
                    <h3>ชำระเงินปลายทาง</h3>
                </div>

                <span class="custom-radio"></span>
            </label>
        </section>

    </div> <div class="order-summary-bar">
        <div class="total-price">
            <span>ยอดสุทธิ</span>
            <strong><?=number_format($grand_total_final)?> บาท</strong>
        </div>
        <a href="checkout_process.php" class="btn btn-primary btn-checkout">
            สั่งซื้อสินค้า
        </a>
    </div>

    <div class="footer-padding"></div>
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