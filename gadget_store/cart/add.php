<?php
// 1. เชื่อมต่อ DB (session_start() อยู่ในนี้แล้ว)
include "../db/connect.php";

// 2. ตรวจสอบว่า login หรือยัง
if (!isset($_SESSION['username'])) {
    header("location: ../login_request/");
    exit;
}

// 3. (แก้ไข) ตรวจสอบว่าได้ ID สินค้ามาไหม
// เราต้องใช้ id นี้เพื่อ redirect กลับไปหน้าสินค้า ถ้าล้มเหลว
if (!isset($_GET['id'])) {
    // ถ้าไม่มี id เลย ก็ส่งกลับหน้าแรก
    header("location: ../");
    exit;
}

// 4. (ใหม่) สร้าง URL สำหรับ redirect กลับไปหน้าสินค้า
// เผื่อต้องใช้ในกรณีที่ข้อมูลไม่ครบ หรือ DB error
$product_id = $_GET['id'];
$fail_redirect_url = "../prod/index.php?id=" . urlencode($product_id);

// 5. (แก้ไข) ตรวจสอบว่าได้ "จำนวน" (count) มาครบไหม
if (!isset($_GET['count'])) {
    // ถ้าไม่มี 'count', ส่งกลับไปหน้าสินค้า (URL ที่เราสร้างไว้)
    header("location: " . $fail_redirect_url);
    exit;
}

// 6. รับค่าและป้องกันบั๊ก
$username = $_SESSION['username'];
$quantity_to_add = (int)$_GET['count']; // (int) เพื่อแปลงเป็นตัวเลข

if ($quantity_to_add <= 0) {
    $quantity_to_add = 1;
}

// 7. --- หัวใจหลัก (เหมือนเดิม) ---
$sql = "INSERT INTO gs_cart (username, pid, quantity) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        quantity = quantity + VALUES(quantity)";

try {
    $stmt = $pdo->prepare($sql);

    // ส่งค่า $product_id (ที่เราเช็กแล้ว) เข้าไป
    $stmt->execute([$username, $product_id, $quantity_to_add]);

    // 8. (แก้ไข) ถ้าสำเร็จ: ส่ง user ไปที่ './' (ซึ่งก็คือ ../cart/)
    header("location: ./");
    exit;

} catch (Exception $e) {
    // 9. (แก้ไข) ถ้า Error (เช่น DB พัง)
    // ส่งกลับไปหน้าสินค้า (URL ที่เราสร้างไว้)
    // เราสามารถส่ง error message กลับไปทาง URL ได้ด้วย (ถ้าอยากทำ)
    // header("location: " . $fail_redirect_url . "&error=" . urlencode("DB error"));

    header("location: " . $fail_redirect_url);
    exit;
}

?>