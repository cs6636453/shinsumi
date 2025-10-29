<?php
include "../db/connect.php";
if (!isset($_SESSION['username'])) {
    header("location: ../login_request/");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: index.php?status=error&msg=" . urlencode("Invalid request method"));
    exit;
}

$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_new_password = $_POST['confirm_new_password'] ?? '';

// 1. เช็กว่ากรอกครบไหม
if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
    header("location: index.php?status=error&msg=" . urlencode("กรุณากรอกรหัสผ่านให้ครบถ้วน"));
    exit;
}

// 2. เช็กว่ารหัสผ่านใหม่ตรงกันไหม
if ($new_password !== $confirm_new_password) {
    header("location: index.php?status=error&msg=" . urlencode("รหัสผ่านใหม่และการยืนยันไม่ตรงกัน"));
    exit;
}

// (แนะนำ) อาจจะเพิ่มเงื่อนไขความยาวรหัสผ่านใหม่
// if (strlen($new_password) < 8) { ... }

try {
    // 3. ดึงรหัสผ่านปัจจุบัน (ที่ hash ไว้) จาก DB
    $stmt_check = $pdo->prepare("SELECT password FROM gs_member WHERE username = ?");
    $stmt_check->execute([$_SESSION['username']]);
    $user = $stmt_check->fetch();

    if (!$user) {
        // ไม่น่าเกิดขึ้นได้ถ้า login อยู่ แต่กันเหนียว
        header("location: index.php?status=error&msg=" . urlencode("ไม่พบข้อมูลผู้ใช้"));
        exit;
    }

    // 4. ตรวจสอบรหัสผ่านปัจจุบันที่กรอกมา กับ hash ใน DB
    if (!password_verify($current_password, $user['password'])) {
        header("location: index.php?status=error&msg=" . urlencode("รหัสผ่านปัจจุบันไม่ถูกต้อง"));
        exit;
    }

    // 5. ถ้าทุกอย่างถูกต้อง -> hash รหัสผ่านใหม่
    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

    // 6. อัปเดตรหัสผ่านใหม่ลง DB
    $stmt_update = $pdo->prepare("UPDATE gs_member SET password = ? WHERE username = ?");
    $stmt_update->execute([$new_password_hashed, $_SESSION['username']]);

    // สำเร็จ!
    header("location: index.php?status=success&msg=" . urlencode("เปลี่ยนรหัสผ่านเรียบร้อยแล้ว"));
    exit;

} catch (Exception $e) {
    header("location: index.php?status=error&msg=" . urlencode("เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน"));
    exit;
}
?>