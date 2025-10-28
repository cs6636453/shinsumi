<?php
// 1. เริ่ม session และเชื่อมต่อ DB
include "../db/connect.php";

// 2. ตั้งค่า header ว่าจะตอบกลับเป็น JSON
header('Content-Type: application/json');

// 3. ตรวจสอบว่า login หรือยัง
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// 4. ตรวจสอบว่าได้ข้อมูล POST มาครบไหม
if (!isset($_POST['pid']) || !isset($_POST['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$username = $_SESSION['username'];
$pid = $_POST['pid'];
$quantity = (int)$_POST['quantity']; // แปลงเป็นตัวเลข

try {
    // 5. ถ้าจำนวนน้อยกว่า 1, ให้ลบสินค้าออกจากตะกร้า
    if ($quantity < 1) {
        $stmt = $pdo->prepare("DELETE FROM gs_cart WHERE username = ? AND pid = ?");
        $stmt->execute([$username, $pid]);
    } else {
        // 6. ถ้าจำนวน 1 ขึ้นไป, ให้อัปเดตจำนวน
        $stmt = $pdo->prepare("UPDATE gs_cart SET quantity = ? WHERE username = ? AND pid = ?");
        $stmt->execute([$quantity, $username, $pid]);
    }

    // 7. ส่ง "success" กลับไปให้ JavaScript
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // 8. ถ้ามีปัญหา DB
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>