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

$new_username = $_POST['new_username'] ?? '';
$confirm_password = $_POST['confirm_password_for_username'] ?? '';

// 1. เช็กว่ากรอกครบไหม และ username ใหม่ไม่เป็นค่าว่าง
if (empty($new_username) || empty($confirm_password)) {
    header("location: index.php?status=error&msg=" . urlencode("กรุณากรอก Username ใหม่ และยืนยันรหัสผ่าน"));
    exit;
}

// 2. เช็กว่า username ใหม่ ไม่ใช่ username เดิม
if ($new_username === $_SESSION['username']) {
    header("location: index.php?status=error&msg=" . urlencode("Username ใหม่ต้องไม่ซ้ำกับ Username ปัจจุบัน"));
    exit;
}

// (แนะนำ) เพิ่มเงื่อนไข validation username ใหม่ (เช่น ห้ามมีอักขระพิเศษ)

try {
    // 3. ดึงรหัสผ่านปัจจุบันจาก DB
    $stmt_check_pass = $pdo->prepare("SELECT password FROM gs_member WHERE username = ?");
    $stmt_check_pass->execute([$_SESSION['username']]);
    $user = $stmt_check_pass->fetch();

    if (!$user) {
        header("location: index.php?status=error&msg=" . urlencode("ไม่พบข้อมูลผู้ใช้"));
        exit;
    }

    // 4. ตรวจสอบรหัสผ่านที่กรอกมา
    if (!password_verify($confirm_password, $user['password'])) {
        header("location: index.php?status=error&msg=" . urlencode("รหัสผ่านปัจจุบันไม่ถูกต้อง"));
        exit;
    }

    // 5. เช็กว่า username ใหม่ มีคนใช้หรือยัง
    $stmt_check_user = $pdo->prepare("SELECT username FROM gs_member WHERE username = ?");
    $stmt_check_user->execute([$new_username]);
    if ($stmt_check_user->fetch()) {
        header("location: index.php?status=error&msg=" . urlencode("Username ใหม่นี้มีผู้ใช้งานแล้ว"));
        exit;
    }

    // --- ส่วนที่อันตราย ---
    // 6. ถ้าทุกอย่างผ่าน -> อัปเดต username ใน gs_member
    // (ย้ำ! ต้องมั่นใจว่า FK มี ON UPDATE CASCADE หรือเขียนโค้ด UPDATE ตารางอื่นเอง)
    $stmt_update_user = $pdo->prepare("UPDATE gs_member SET username = ? WHERE username = ?");
    $stmt_update_user->execute([$new_username, $_SESSION['username']]);

    // 7. อัปเดต username ใน Session ด้วย!
    $_SESSION['username'] = $new_username;

    // สำเร็จ!
    header("location: index.php?status=success&msg=" . urlencode("เปลี่ยน Username เรียบร้อยแล้ว"));
    exit;

} catch (Exception $e) {
    // ถ้าเกิด Error (เช่น FK ไม่มี CASCADE แล้วไปชน constraint)
    header("location: index.php?status=error&msg=" . urlencode("เกิดข้อผิดพลาดในการเปลี่ยน Username: " . $e->getMessage()));
    exit;
}
?>