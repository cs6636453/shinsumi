<?php
include "../db/connect.php";
if ($_SESSION['username'] == null) header("location: ../login_request/");

// --- ตัวแปรหลักสำหรับสถานะและ Log ---
$order_success = true;
$error_message = "";
$last_order_id = null; // ID ออเดอร์หลัก
$payment_method = 'ชำระเงินปลายทาง'; // Payment Method
$first_name = ''; // เตรียมตัวแปรชื่อ
$last_name = ''; // เตรียมตัวแปรสกุล

// --- กำหนดค่า Log ---
// ต้องมั่นใจว่า path นี้มีสิทธิ์เขียน (../log/orders.csv)
$log_header = 'เลขออเดอร์,วันที่,เวลา,ชื่อลูกค้า,นามสกุลลูกค้า,ชื่อสินค้า,จำนวน,ราคาต่อชิ้น,ส่วนลดต่อชิ้น,วิธีชำระเงิน';
$log_file = '../log/orders.csv';

$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

try {
    // 1. เริ่ม Transaction เพื่อรับประกันความสมบูรณ์ของข้อมูล
    $pdo->beginTransaction();

    // 2. ดึงข้อมูลตะกร้า "ทั้งหมด"
    $stmt_cart = $pdo->prepare("select p.pid, p.pname, sum(c.quantity) as quantity, p.price, pr.discount_type, pr.discount_value
                                    from gs_product p join gs_cart c on c.pid = p.pid
                                    left join gs_promotion pr on pr.pr_id = p.pr_id
                                    where c.username = ? group by p.pid;");
    $stmt_cart->bindParam(1, $_SESSION['username']);
    $stmt_cart->execute();
    $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

    // 3. ถ้าตะกร้าว่าง
    if (count($cart_items) == 0) {
        header("location: ../cart/");
        exit;
    }

    // 4. ตรวจสอบสต็อกสินค้าทั้งหมดก่อนเริ่มทำรายการ
    $stmt_check_stock = $pdo->prepare("SELECT stock, pname FROM gs_product WHERE pid = ?");
    foreach ($cart_items as $item) {
        $stmt_check_stock->execute([$item['pid']]);
        $product = $stmt_check_stock->fetch();

        if (!$product || $product['stock'] < $item['quantity']) {
            throw new Exception("ขออภัย สินค้า \"" . $item['pname'] . "\" มีไม่เพียงพอ (เหลือ " . $product['stock'] . " ชิ้น)");
        }
    }

    // 5. ดึงข้อมูลที่อยู่และชื่อลูกค้า
    $stmt_address = $pdo -> prepare("select first_name, last_name, phone, address, province, postal_code from gs_member where username = ?");
    $stmt_address -> execute([$_SESSION['username']]);
    $address = $stmt_address -> fetch();

    // **เก็บชื่อลูกค้าไว้ใช้ใน Log**
    $first_name = $address['first_name'];
    $last_name = $address['last_name'];

    $address_final = $first_name . " " . $last_name . " (" . $address['phone'] . ")\n" .
        $address['address'] . ", " . $address['province'] . ", " . $address['postal_code'];

    // 6. สร้างออเดอร์หลัก
    $stmt_ins_order = $pdo->prepare("INSERT INTO gs_orders (username, status, payment_method, address) 
                                        VALUES (?, 'pending', 'cod', ?)");
    $stmt_ins_order->execute([$_SESSION['username'], $address_final]);
    $last_order_id = $pdo->lastInsertId();

    // 7. เตรียมคำสั่ง insert item และ update stock
    $stmt_ins_item = $pdo->prepare("INSERT INTO gs_orders_item (ord_id, pid, quantity, price_each) 
                                        VALUES (?, ?, ?, ?)");
    $stmt_update_stock = $pdo -> prepare("UPDATE gs_product SET stock = stock - ? WHERE pid = ?");

    // 8. วนลูปเพื่อบันทึกรายการสินค้าและอัปเดตสต็อกใน DB
    foreach ($cart_items as $row) {

        if ($row['discount_type'] == "fixed") {
            $item_unit_price = $row["price"] - $row["discount_value"];
        } else if ($row['discount_type'] == "percent") {
            // คำนวณตาม Logic เดิม
            $item_unit_price = $row["price"] - ($row["discount_value"] * $row["price"]);
        } else {
            $item_unit_price = $row['price'];
        }
        $item_unit_price_rounded = round($item_unit_price);

        $stmt_update_stock -> execute([$row['quantity'], $row['pid']]);
        $stmt_ins_item->execute([$last_order_id, $row['pid'], $row['quantity'], $item_unit_price_rounded]);
    }

    // 9. ลบสินค้าออกจากตะกร้า
    $stmt_clear_cart = $pdo->prepare("DELETE FROM gs_cart WHERE username = ?");
    $stmt_clear_cart->execute([$_SESSION['username']]);

    // 10. ถ้าทุกอย่างสำเร็จ ให้ commit (ยืนยัน)
    $pdo->commit();


    // ----------------------------------------------------------------------
    // --- (สำคัญ!) ส่วน Log CSV ที่ทำงานเมื่อ Commit สำเร็จแล้ว ---
    // ----------------------------------------------------------------------

    // A. ตรวจสอบ/สร้าง Header ของไฟล์ (ทำแค่ครั้งแรก)
// A. ตรวจสอบ/สร้าง Header ของไฟล์ (ทำแค่ครั้งแรก)
    if (!file_exists($log_file) || filesize($log_file) == 0) {

        // --- (เพิ่ม) ---
        $bom = "\xEF\xBB\xBF"; // นี่คือโค้ดลับ (BOM) บอก Excel ว่าเป็น UTF-8
        // -------------

        error_log($bom . $log_header . "\n", 3, $log_file); // เอา BOM มาแปะหน้าสุด
    }

    // B. วนลูปบันทึกทีละรายการสินค้า
    foreach ($cart_items as $row) {
        // 1. คำนวณส่วนลดต่อชิ้นเพื่อใช้ใน Log
        $total_discount_log = 0;
        if ($row['discount_type'] == "fixed") {
            $total_discount_log = $row['discount_value'];
        } else if ($row['discount_type'] == "percent") {
            // ใช้ค่าเดียวกับที่ถูกบันทึกใน foreach ก่อนหน้า
            $total_discount_log = $row['discount_value'] * $row['price'];
        }

        // 2. จัดรูปแบบข้อความ CSV
        $log_message = $last_order_id
            . "," . $currentDate
            . "," . $currentTime
            . "," . $first_name
            . "," . $last_name
            . "," . $row['pname']
            . "," . $row['quantity']
            . "," . number_format($row['price'], 2, '.', '') // ราคาเดิม
            . "," . number_format(round($total_discount_log), 2, '.', '') // ส่วนลดต่อชิ้น (ปัดเศษ)
            . "," . $payment_method
            . "\n";

        // 3. บันทึก Log เข้าไฟล์
        error_log($log_message, 3, $log_file);
    }
    // ----------------------------------------------------------------------


} catch (Exception $e) {
    // 11. ถ้ามีอะไรพลาด ให้ rollBack (ยกเลิกทั้งหมด)
    $pdo->rollBack();
    $order_success = false;
    $error_message = $e->getMessage();
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
    <link rel="stylesheet" href="../assets/style/desktop_customer.css">
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
            min-width: 0; /* (แก้ไข) เพิ่มบรรทัดนี้เพื่อแก้บั๊ก text-overflow */
        }

        .cart_detail h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
            /* (แก้ไข) เพิ่ม 3 บรรทัดสำหรับย่อข้อความ */
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
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
            /* (แก้ไข) ลบ flex: 1 ที่ทำปุ่มยืดทิ้ง */
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
            color: #28a745; /* สีเขียวสำหรับส่วนลด */
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

        /* --- (CSS จากไฟล์ checkout) --- */
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

        .checkout-option-card.active {
            border-color: #000;
            background: #fdfaff;
        }

        .option-details {
            flex-grow: 1;
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
            border-color: #000;
        }

        .custom-radio::after {
            content: '';
            width: 12px;
            height: 12px;
            background: #000;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            transition: transform 0.2s;
        }
        .checkout-option-card.active .custom-radio::after {
            transform: translate(-50%, -50%) scale(1);
        }

        .order-summary-bar {
            /* (แก้ไข) ลบ position: fixed และ bottom: 0 */
            width: 100%;
            background: #ffffff;
            border-top: 1px solid #eee;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            box-sizing: border-box;
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
            padding: 12px 0;
            width: 50px;
            font-size: 1.1em;
            flex-shrink: 0;
            flex: none; /* (แก้ไข) เพิ่ม flex: none */
        }

        .footer-padding {
            height: 100px;
        }

        /* --- (CSS จากหน้า Success) --- */
        .order-status-wrapper {
            text-align: center;
            padding: 60px 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-top: 20px;
        }
        .order-status-wrapper .status-icon {
            font-size: 60px; /* ขนาดไอคอน */
            margin-bottom: 20px;
        }
        .order-status-wrapper h1 {
            font-size: 1.8em;
            margin-bottom: 10px;
            border-bottom: none; /* ลบเส้นใต้ h1 เดิม */
            padding-bottom: 0;
        }
        .order-status-wrapper p {
            font-size: 1.1em;
            color: #555;
            margin-bottom: 30px;
        }
        .order-status-wrapper .btn {
            flex: none; /* แก้บั๊กปุ่มยืด (ถ้ามี) */
            padding: 12px 30px;
        }

        /* สีเขียวตอนสำเร็จ */
        .order-status-wrapper.success .status-icon {
            color: #28a745;
        }
        .order-status-wrapper.success {
            border-color: #c3e6cb;
            background: #f8fdf9;
        }

        /* สีแดงตอนล้มเหลว */
        .order-status-wrapper.error .status-icon {
            color: #d9534f;
        }
        .order-status-wrapper.error {
            border-color: #f5c6cb;
            background: #fdf8f8;
        }
    </style>
</head>
<body>
<nav>

    <div id="top_row">
        <section class="container" id="menu_btn" onclick="animateMenuButton(this)">
            <div class="bar1"></div>
            <div class="bar2"></div>
            <div class="bar3"></div>
        </section>

        <section id="shop_name">
            <a href="../">GADGET STORE</a>
        </section>

        <section id="login_btn">
            <a id="login"><img src="../assets/images/loading.gif" alt="loading"></a>
        </section>
    </div>

    <div id="bottom_row">
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

    <?php if ($order_success): // --- ถ้าสั่งซื้อสำเร็จ --- ?>

        <div class="order-status-wrapper success">
            <span class="material-symbols-outlined status-icon">check_circle</span>
            <h1>สั่งซื้อสินค้าสำเร็จ!</h1>
            <p>คำสั่งซื้อของคุณหมายเลข #<?=$last_order_id?> ได้รับการยืนยันแล้ว</p>
            <a href="../" class="btn btn-primary">กลับไปหน้าแรก</a>
        </div>

    <?php else: // --- ถ้าเกิดข้อผิดพลาด --- ?>

        <div class="order-status-wrapper error">
            <span class="material-symbols-outlined status-icon">error</span>

            <h1>ออเดอร์ไม่สำเร็จ</h1>

            <p>
                <?php
                // เราใช้ htmlspecialchars() เพื่อความปลอดภัย
                echo htmlspecialchars($error_message);
                ?>
            </p>

            <a href="../cart/" class="btn btn-secondary">กลับไปหน้าตะกร้า</a>
        </div>

    <?php endif; ?>

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