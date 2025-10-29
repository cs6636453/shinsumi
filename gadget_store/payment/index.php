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
                    <label for="search_param"></label><input type="text" name="search" placeholder="ค้นหาสินค้า"
                        id="search_param">
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
                   <span>โปรโมชั่น</span>
                   <span class="material-symbols-outlined plus-icon">add</span>
               </button>
               <ul class="sub-menu">
                   <li><a href="../search/?search=ฮาโลวีน">ฮาโลวีน</a></li>
                   <li><a href="../search/?search=ลอยกระทง">ลอยกระทง</a></li>
               </ul>
           </li>
           <li>
               <button class="nav-item-button">
                   <span>เคส</span>
                   <span class="material-symbols-outlined plus-icon">add</span>
               </button>
               <ul class="sub-menu">
                   <li><a href="../search/?search=iphone">iPhone</a></li>
                   <li><a href="../search/?search=samsung">Samsung</a></li>
               </ul>
           </li>
           <li>
               <button class="nav-item-button">
                   <span>กระเป๋า</span>
                   <span class="material-symbols-outlined plus-icon">add</span>
               </button>
               <ul class="sub-menu">
                   <li><a href="../search/?search=กระเป๋าผ้า">กระเป๋าผ้า</a></li>
                   <li><a href="../search/?search=กระเป๋า">อื่นๆ</a></li>
               </ul>
           </li>
           <li>
               <button class="nav-item-button">
                   <span>อุปกรณ์เสริม</span>
                   <span class="material-symbols-outlined plus-icon">add</span>
               </button>
               <ul class="sub-menu">
                   <li><a href="../search/?search=ที่ชาร์จ">ที่ชาร์จ</a></li>
                   <li><a href="../search/?search=ฟิล์ม">ฟิล์ม</a></li>
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
    <h1><label>TEL</label> 095-484-9802</h1>
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