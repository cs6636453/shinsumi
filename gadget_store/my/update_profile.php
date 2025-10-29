<?php
include "../db/connect.php"; // (มี session_start() แล้ว)
if (!isset($_SESSION['username'])) {
    header("location: ../login_request/");
    exit;
}

// ตรวจสอบว่าข้อมูลถูกส่งมาแบบ POST หรือไม่
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: index.php?status=error&msg=" . urlencode("Invalid request method"));
    exit;
}

// รับค่าจากฟอร์ม (ควรมีการ validation เพิ่มเติม)
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['tel'] ?? ''; // ใช้ 'tel' ตาม id/name ใน HTML
$address = $_POST['address'] ?? '';
$postal_code = $_POST['postal'] ?? ''; // ใช้ 'postal' ตาม id/name ใน HTML
$province = $_POST['province'] ?? ''; // รับค่า value จาก <select>

// ตรวจสอบค่าว่างเบื้องต้น (อาจจะต้องละเอียดกว่านี้)
if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address) || empty($postal_code) || empty($province)) {
    header("location: index.php?status=error&msg=" . urlencode("กรุณากรอกข้อมูลให้ครบถ้วน"));
    exit;
}

// เตรียมคำสั่ง UPDATE
$sql = "UPDATE gs_member 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            postal_code = ?, 
            province = ? 
        WHERE username = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $address,
        $postal_code,
        $province,
        $_SESSION['username'] // ใช้ username จาก session เป็นเงื่อนไข
    ]);

    // ถ้าสำเร็จ กลับไปหน้า account พร้อมข้อความ success
    header("location: index.php?status=success&msg=" . urlencode("บันทึกข้อมูลโปรไฟล์เรียบร้อยแล้ว"));
    exit;

} catch (Exception $e) {
    // ถ้าเกิดข้อผิดพลาด (เช่น email ซ้ำ ถ้าตั้ง unique ไว้)
    // ควร log error ไว้ดู $e->getMessage()
    header("location: index.php?status=error&msg=" . urlencode("เกิดข้อผิดพลาดในการบันทึกข้อมูล"));
    exit;
}
?>