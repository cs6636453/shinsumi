<?php
include "../db/connect.php"; // (นายบอกว่า session_start() อยู่ในนี้แล้ว)
if (!isset($_SESSION['username'])) {
    header("location: ../login_request/"); // ถ้ายังไม่ login
    exit;
}

// --- 1. ดึงข้อมูลปัจจุบันของ User ---
$stmt_user = $pdo->prepare("SELECT * FROM gs_member WHERE username = ?");
$stmt_user->execute([$_SESSION['username']]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

// ถ้าหา user ไม่เจอ (อาจจะโดนลบไปแล้ว?)
if (!$user) {
    // อาจจะ logout แล้ว redirect ไปหน้า login
    session_destroy();
    header("location: ../login_request/?error=User not found");
    exit;
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
    <title>บัญชีของฉัน | We are Gadget Store</title>
    <link rel="stylesheet" href="../assets/style/login_form.css">

    <style>
        select#province {
            height: 50px !important;
        }

        main { /* ใช้ main จาก global.css ได้เลย */
            max-width: 800px; /* จำกัดความกว้างหน่อย */
        }
        main h1 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .account-section {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .account-section h2 {
            margin-top: 0;
            font-size: 1.3em;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        /* Style สำหรับแสดงข้อมูล */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr; /* 1 คอลัมน์ */
            gap: 10px;
        }
        .info-grid p {
            margin: 0;
            line-height: 1.6;
        }
        .info-grid strong {
            display: inline-block;
            min-width: 100px; /* ทำให้ label กว้างเท่าๆ กัน */
            color: #555;
        }
        /* Style สำหรับฟอร์ม (คล้ายๆ register) */
        .account-section form label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        .account-section form input[type="text"],
        .account-section form input[type="email"],
        .account-section form input[type="password"],
        .account-section form textarea,
        .account-section form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* สำคัญมาก */
        }
        .account-section form textarea {
            min-height: 80px;
            resize: vertical;
        }
        .account-section form input[type="submit"] {
            background-color: #000;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.2s;
        }
        .account-section form input[type="submit"]:hover {
            background-color: #333;
        }
        /* ข้อความ error (ถ้ามี) */
        .error-message {
            color: red;
            font-size: 0.9em;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        /* ข้อความเตือน */
        .warning-message {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
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

<main>
    <h1>บัญชีของฉัน</h1>

    <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
        <div class="alert <?= $_GET['status'] == 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars(urldecode($_GET['msg'])) ?>
        </div>
    <?php endif; ?>

    <section class="account-section">
        <h2>ข้อมูลโปรไฟล์</h2>
        <div class="info-grid">
            <p><strong>Username:</strong> <?=htmlspecialchars($user['username'])?></p>
            <p><strong>ชื่อ-สกุล:</strong> <?=htmlspecialchars($user['first_name'])?> <?=htmlspecialchars($user['last_name'])?></p>
            <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
            <p><strong>เบอร์โทร:</strong> <?=htmlspecialchars($user['phone'])?></p>
            <p><strong>ที่อยู่จัดส่ง:</strong><br>
                <?=nl2br(htmlspecialchars($user['address']))?><br>
                <?=htmlspecialchars($user['province'])?><br>
                <?=htmlspecialchars($user['postal_code'])?>
            </p>
            <p><strong>วันที่สมัคร:</strong> <?=$user['creation_date']?></p>
        </div>
    </section>

    <section class="account-section">
        <h2>แก้ไขข้อมูลโปรไฟล์</h2>
        <form action="update_profile.php" method="POST">
            <label for="first_name">ชื่อ</label>
            <input type="text" id="first_name" name="first_name" value="<?=htmlspecialchars($user['first_name'])?>" required>

            <label for="last_name">นามสกุล</label>
            <input type="text" id="last_name" name="last_name" value="<?=htmlspecialchars($user['last_name'])?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?=htmlspecialchars($user['email'])?>" required>

            <label for="tel">Tel.</label>
            <input type="text" id="tel" name="tel" value="<?=htmlspecialchars($user['phone'])?>" required pattern="[0-9]{9,10}">

            <label for="address">Address</label>
            <textarea id="address" name="address" required><?=htmlspecialchars($user['address'])?></textarea>

            <label for="postal">Postal Code</label>
            <input type="text" id="postal" name="postal" value="<?=htmlspecialchars($user['postal_code'])?>"
                   onkeyup="getDataFromPostal()"
                   pattern="[0-9]{5}" required>

            <label for="province">Province / District / Sub-district</label>
            <select name="province" id="province" required>
                <option value="<?=htmlspecialchars($user['province'])?>" selected><?=htmlspecialchars($user['province'])?></option>
            </select>

            <input type="submit" value="บันทึกข้อมูลโปรไฟล์">
        </form>
    </section>

    <section class="account-section">
        <h2>เปลี่ยนรหัสผ่าน</h2>
        <form action="update_password.php" method="POST">
            <label for="current_password">รหัสผ่านปัจจุบัน</label>
            <input type="password" id="current_password" name="current_password" required>

            <label for="new_password">รหัสผ่านใหม่</label>
            <input type="password" id="new_password" name="new_password" required>

            <label for="confirm_new_password">ยืนยันรหัสผ่านใหม่</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" required>
            <div id="errorpass_change" class="error-message"></div>

            <input type="submit" value="เปลี่ยนรหัสผ่าน">
        </form>
    </section>

    <section class="account-section">
        <h2>เปลี่ยน Username</h2>
        <form action="update_username.php" method="POST">
            <label for="new_username">Username ใหม่</label>
            <input type="text" id="new_username" name="new_username" required>
            <div id="erroruse_change" class="error-message"></div>

            <label for="confirm_password_for_username">ยืนยันรหัสผ่านปัจจุบัน</label>
            <input type="password" id="confirm_password_for_username" name="confirm_password_for_username" required>

            <input type="submit" value="เปลี่ยน Username">
        </form>
    </section>

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

<script>
    let xmrReq;
    function getDataFromPostal() {
        let option = document.createElement("option");
        const postal = document.getElementById('postal').value;
        if (postal.length !== 5) {
            option.innerHTML = "กรุณากรอกรหัสไปรษณีย์ 5 หลัก";
            document.getElementById('province').innerHTML = ""; // ล้างค่าเก่า
            document.getElementById('province').appendChild(option);
            return;
        }
        option.innerHTML = "กำลังค้นหาข้อมูล...";
        document.getElementById('province').innerHTML = ""; // ล้างค่าเก่า
        document.getElementById('province').appendChild(option);

        xmrReq = new XMLHttpRequest();
        xmrReq.onreadystatechange = convertPostal;
        const url = 'https://raw.githubusercontent.com/kongvut/thai-province-data/refs/heads/master/api/latest/sub_district_with_district_and_province.json';
        xmrReq.open('GET', url);
        xmrReq.send();
    }

    function convertPostal() {
        if (xmrReq.readyState == 4 && xmrReq.status == 200) {
            const objectData = JSON.parse(this.responseText);
            const postal = document.getElementById('postal').value;
            let provinceSelect = document.getElementById('province');
            let option = document.createElement("option");
            let foundMatch = false;
            provinceSelect.innerHTML = ""; // ล้างค่า "กำลังค้นหา..."

            // (ใหม่) เพิ่ม option แรกให้เลือก
            let placeholderOption = document.createElement("option");
            placeholderOption.value = "";
            placeholderOption.innerHTML = "-- กรุณาเลือก --";
            provinceSelect.appendChild(placeholderOption);

            for (let i = 0; i < objectData.length; i++) {
                let zip_code = objectData[i].zip_code;
                let option2 = document.createElement("option");
                if (zip_code.toString() === postal) { // (แก้ไข) ใช้ === เพื่อเทียบตรงๆ
                    foundMatch = true;
                    // (แก้ไข) แสดงผลสวยงามขึ้น และ value เก็บข้อมูลที่ต้องการ
                    option2.innerHTML = objectData[i].name_th + " » " + objectData[i].district.name_th + " » " + objectData[i].district.province.name_th;
                    option2.value = objectData[i].district.province.name_th; // <-- เก็บเฉพาะจังหวัดใน value
                    provinceSelect.appendChild(option2);
                }
            }
            if (!foundMatch) {
                provinceSelect.innerHTML = ""; // ล้าง placeholder
                option.innerHTML = "ไม่พบข้อมูลรหัสไปรษณีย์";
                option.value = "";
                provinceSelect.appendChild(option);
            }
        } else if (xmrReq.readyState == 4) { // ถ้า Error
            let provinceSelect = document.getElementById('province');
            provinceSelect.innerHTML = "";
            let option = document.createElement("option");
            option.innerHTML = "เกิดข้อผิดพลาดในการดึงข้อมูล";
            provinceSelect.appendChild(option);
        }
    }

    // (ใหม่) เรียก getDataFromPostal() ครั้งแรก เผื่อรหัสไปรษณีย์มีค่าอยู่แล้ว
    // แต่รอแปปนึงให้หน้าเว็บโหลดเสร็จก่อน
    document.addEventListener("DOMContentLoaded", function() {
        if (document.getElementById('postal').value.length === 5) {
            // (สำคัญ) หน้านี้เราแค่ต้องการแสดงค่าเดิม ไม่ต้อง fetch ใหม่
            // เราแค่สร้าง <option> ที่มีค่าเดิมไว้ก็พอ
            let provinceSelect = document.getElementById('province');
            let currentProvinceValue = "<?=htmlspecialchars($user['province'])?>"; // ดึงค่าจาก PHP
            let currentProvinceText = "<?=htmlspecialchars($user['province'])?>"; // (อาจจะต้องปรับปรุง)

            // ลบ option ที่มีอยู่ (ถ้ามี)
            provinceSelect.innerHTML = '';

            // สร้าง option ที่มีค่าปัจจุบัน
            let currentOption = document.createElement("option");
            currentOption.value = currentProvinceValue;
            currentOption.text = currentProvinceText; // ควรจะแสดง ตำบล อำเภอ จังหวัด เต็มๆ
            currentOption.selected = true;
            provinceSelect.appendChild(currentOption);

            // เพิ่ม placeholder เผื่ออยากเลือกใหม่
            let placeholderOption = document.createElement("option");
            placeholderOption.value = "";
            placeholderOption.text = "-- กรุณากรอกรหัสไปรษณีย์เพื่อค้นหาใหม่ --";
            provinceSelect.appendChild(placeholderOption);
        }
    });

</script>

</body>
</html>