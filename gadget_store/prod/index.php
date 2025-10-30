<?php
    include "../db/connect.php";

    $stmt = $pdo -> prepare("select p.pname, p.description, p.price, p.stock, c.category_name, pr.discount_type, pr.discount_value, pr.end_date
                                    from gs_product p join gs_category c on c.category_id = p.category_id
                                    left join gs_promotion pr on pr.pr_id = p.pr_id
                                    where p.pid = ?;");
    $stmt -> bindParam(1, $_GET["id"]);
    $stmt -> execute();
    $row = $stmt -> fetch();
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
    <title><?=$row["pname"]?> | We are Gadget Store</title>
    <link rel="stylesheet" href="../assets/style/login_form.css">
    <link rel="stylesheet" href="../assets/style/desktop_customer.css">
    <link rel="stylesheet" href="inner.css">
    <link rel="stylesheet" href="../assets/style/desktop_nav.css">
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
<main class="myMain">
    <p><?=$row["category_name"]?> > <?=$row["pname"]?></p>
    <span class="outer_span">
    <section class="image">
        <img src="../assets/images/products/<?=$_GET['id']?>">
    </section>
    <span class="inner_span">
    <h3><?=$row["pname"]?></h3>
    <?php
        if ($row["stock"] >= 1) {
            echo "<span style='color: green;'>✅ มีสินค้า</span>";
        } else {
            echo "<span style='color: red;'>ขออภัย สินค้าหมดแล้ว</span>";
        }

    if ($row["discount_type"] == "fixed") {
        $final_price = $row["price"]-$row["discount_value"];
        echo '<h3><span style="color: gray;">ราคา </span><s>'.$row["price"].'</s><span style="color: red;"> '.round($final_price).' </span>บาท</h3>';
    } else if ($row["discount_type"] == "percent") {
        $final_price = $row["price"]-($row["discount_value"]*$row["price"]);
        echo '<h3><span style="color: gray;">ราคา </span><s>'.$row["price"].'</s><span style="color: red;"> '.round($final_price).' </span>บาท</h3>';
    } else {
        echo '<h3><span style="color: gray;">ราคา </span>'.$row["price"].' บาท</h3>';
    }
    ?>

    <?php
    if ($row["stock"] >= 1) {
    ?><label for="number">จำนวน</label><br>
    <div class="quantity-input">
        <input type="button" class="qt-minus" value="-">
        <input type="number" id="number" class="qt-number" value="1" min="1" readonly>
        <input type="button" class="qt-plus" value="+">
    </div>

    <div class="add-order">
        <a href="../cart/add.php?id=<?=$_GET['id']?>&count=1" id="add-to-cart-link" class="btn btn-secondary">
            ใส่ตะกร้า
        </a>
    </div>
    <?php
    }
    ?>
    </span>
    </span>
</main>
<footer>
    <h1><label>TEL</label> 095-484-9802</h1>
    <p>9:00 - 17:00 จันทร์-ศุกร์</p>
    <p>คำสงวนสิทธิ์: หน้าเว็บนี้มีจุดประสงค์เพื่อส่งงานอาจารย์เท่านั้น</p>
    <br>
    <b>GADGET STORE</b>
</footer>
<script>
    document.addEventListener("DOMContentLoaded", function() {

        const product_id = <?php echo json_encode($_GET['id']); ?>;

        const baseCartHref = `../cart/add.php?id=${product_id}`;
        // const basePaymentHref = `../cart/payment.php?id=${product_id}`; // (เผื่อปุ่ม "ซื้อเลย")

        const addToCartLink = document.getElementById('add-to-cart-link');
        // const buyNowLink = document.getElementById('buy-now-link'); // (เผื่อปุ่ม "ซื้อเลย")
        document.querySelectorAll('.quantity-input').forEach(function(wrapper) {

            const numberInput = wrapper.querySelector('.qt-number');
            const minusBtn = wrapper.querySelector('.qt-minus');
            const plusBtn = wrapper.querySelector('.qt-plus');
            const min = parseInt(numberInput.min) || 1;

            function updateLinks() {
                const count = numberInput.value;
                if (addToCartLink) {
                    addToCartLink.href = baseCartHref + '&count=' + count;
                }
                // if (buyNowLink) {
                //     buyNowLink.href = basePaymentHref + '&count=' + count;
                // }

                // (ใช้สำหรับ debug)
                // console.log("Updated Link:", addToCartLink.href);
            }

            plusBtn.addEventListener('click', function() {
                numberInput.value = parseInt(numberInput.value) + 1;
                updateLinks(); // <-- 5. (เพิ่ม) เรียกใช้ฟังก์ชัน
            });

            minusBtn.addEventListener('click', function() {
                let currentValue = parseInt(numberInput.value);
                if (currentValue > min) {
                    numberInput.value = currentValue - 1;
                }
                updateLinks(); // <-- 6. (เพิ่ม) เรียกใช้ฟังก์ชัน
            });
        });

    });
</script>
<script src="../scripts/index.js"></script>
<script src="../scripts/login_req.js"></script>
</body>
</html>