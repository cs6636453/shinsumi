<?php
    include "../db/connect.php";
    if ($_SESSION['username'] == null) header("location: ../login_request/");

    $stmt = $pdo -> prepare("select p.pid, p.pname, sum(c.quantity) as quantity, p.price, sum(p.price*quantity) as 'subtotal', pr.discount_type, pr.discount_value, pr.end_date, ca.category_name
                                    from gs_product p join gs_cart c on c.pid = p.pid
                                    join gs_member m on m.username = c.username
                                    left join gs_promotion pr on pr.pr_id = p.pr_id
                                    join gs_category ca on ca.category_id = p.category_id
                                    where c.username = ? group by p.pid;");
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
    <title>ตะกร้าของฉัน | We are Gadget Store</title>
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
    <h1>ตะกร้าของฉัน</h1>

    <div class="cart-layout">

        <div class="cart-items-list">
            <?php
            // 1. (แก้ไข) เพิ่มตัวแปรใหม่
            $grand_total_original = 0; // ยอดรวม (ราคาเต็ม)
            $grand_total_final = 0;    // ยอดรวม (หลังลด)
            $i = 0;

            while ($row = $stmt -> fetch()) {

                // 2. (ใหม่) คำนวณราคารวม "ราคาเต็ม" ของแถวนี้
                $original_line_total = $row['price'] * $row['quantity'];
                $grand_total_original += $original_line_total; // บวกเข้ายอดรวม (เต็ม)

                // 3. (เดิม) คำนวณราคาต่อชิ้น (หลังลด)
                $item_unit_price = 0;
                if ($row['discount_type'] == "fixed") {
                    $item_unit_price = $row["price"] - $row["discount_value"];
                } else if ($row['discount_type'] == "percent") {
                    $item_unit_price = $row["price"] - ($row["discount_value"] * $row["price"]);
                } else {
                    $item_unit_price = $row['price'];
                }
                $item_unit_price_rounded = round($item_unit_price);

                // 4. (แก้ไข) คำนวณราคารวม "หลังลด" ของแถวนี้
                $final_line_total = $item_unit_price_rounded * $row["quantity"];
                $grand_total_final += $final_line_total; // บวกเข้ายอดรวม (หลังลด)
                ?>

                <section class="cart_item"
                         data-pid="<?=$row['pid']?>"
                         data-unit-price-final="<?=$item_unit_price_rounded?>"
                         data-unit-price-original="<?=$row['price']?>">

                    <img src="../assets/images/products/<?=$row['pid']?>" alt="<?=$row["pname"]?>">

                    <div class="cart_detail">
                        <h4><?=$row["pname"]?></h4>
                        <p><?=$row["category_name"]?></p>
                        <p class="cart-item-unit-price">
                            <?php
                            if ($row['discount_type'] != null) {
                                echo "<s>".$row['price']."</s> <strong>".$item_unit_price_rounded."</strong> บาท";
                            } else {
                                echo "<strong>".$item_unit_price_rounded."</strong> บาท";
                            }
                            ?>
                        </p>
                    </div>

                    <div class="cart-controls">
                        <div class="quantity-input">
                            <input type="button" class="qt-minus" value="-">
                            <input type="number" class="qt-number" value="<?=$row["quantity"]?>" min="0" readonly>
                            <input type="button" class="qt-plus" value="+">
                        </div>
                        <span class="cart-item-line-total">
                            <?=number_format($final_line_total)?> บาท
                        </span>
                        <button class="remove-btn" title="ลบสินค้า">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>

                </section>

                <?php
                $i++;
            } // จบ while loop

            // 7. (ใหม่) คำนวณยอดส่วนลดรวมทั้งหมด
            $total_discount_saved = $grand_total_original - $grand_total_final;
            ?>

            <?php if ($i == 0): // <-- ถ้า i เป็น 0 (ไม่มีของ) ?>
                <section class="cart-empty">
                    <h3>ตะกร้าของคุณว่างเปล่า</h3>
                    <p>ดูเหมือนว่ายังไม่มีสินค้าในตะกร้าของคุณ</p>
                    <a href="../" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">
                        กลับไปเลือกซื้อสินค้า
                    </a>
                </section>
            <?php endif; // จบส่วนตะกร้าว่าง ?>

        </div> <?php if ($i > 0): // <-- ถ้า i มากกว่า 0 (มีของ) ?>
            <aside class="cart-summary">
                <h2>สรุปยอด</h2>

                <div class="summary-row">
                    <span>ราคารวม</span>
                    <span id="cart-original-total"><?=number_format($grand_total_original)?> บาท</span>
                </div>

                <div class="summary-row summary-discount">
                    <span>ส่วนลด</span>
                    <span id="cart-discount-total">- <?=number_format($total_discount_saved)?> บาท</span>
                </div>

                <div class="summary-row summary-final">
                    <span>ยอดรวมสุทธิ</span>
                    <span id="cart-grand-total"><?=number_format($grand_total_final)?> บาท</span>
                </div>

                <a href="../payment/" class="btn btn-primary btn-checkout">
                    สั่งซื้อสินค้า
                </a>
            </aside>
        <?php endif; // จบส่วนสรุปยอด ?>

    </div> </main>
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