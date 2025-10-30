<?php
include "../../../db/connect.php"; // (ตรวจสอบ path)

// 1. (ใหม่) ตั้งค่า Timezone
date_default_timezone_set('Asia/Bangkok');
$now = new DateTime(); // วันที่และเวลาปัจจุบัน

// 2. (ใหม่) รับค่า query และแปลงเป็นตัวพิมพ์เล็ก
$search_query = isset($_GET['query']) ? strtolower(trim($_GET['query'])) : '';

// 3. (แก้ไข) ดึงข้อมูลโปรโมชัน *ทั้งหมด* (เราจะมากรองใน PHP)
$stmt = $pdo->prepare("SELECT pr_id, pr_name, discount_type, start_date, end_date 
                           FROM gs_promotion 
                           ORDER BY start_date DESC");

$stmt->execute();

// 4. (ใหม่) สร้างตัวนับแถวที่เจอ
$found_rows = 0;

// 5. วนลูปและสร้าง <tr>
while ($row = $stmt->fetch()) {

    // --- (ตรรกะสำหรับคำนวณสถานะ - เหมือนเดิม) ---
    $start_date = new DateTime($row['start_date']);
    $end_date = new DateTime($row['end_date']);

    $status_text = '';
    $status_class = '';

    if ($now < $start_date) {
        $status_text = 'Upcoming';
        $status_class = 'status-upcoming';
    } elseif ($now > $end_date) {
        $status_text = 'Expired';
        $status_class = 'status-expired';
    } else {
        $status_text = 'Active';
        $status_class = 'status-active';
    }

    $date_display = $start_date->format('d/m/Y') . ' - ' . $end_date->format('d/m/Y');
    // --- จบตรรกะสถานะ ---


    // --- 6. (ใหม่) ตรรกะการกรองด้วย PHP ---
    if ($search_query !== '') {
        // ตรวจสอบว่าคำค้นหา ตรงกับส่วนใดส่วนหนึ่งหรือไม่
        $name_match = str_contains(strtolower($row['pr_name']), $search_query);
        $type_match = str_contains(strtolower($row['discount_type']), $search_query);

        // ค้นหาได้ทั้งแบบ yyyy-mm-dd (จาก DB) หรือ dd/mm/yyyy (จากที่แสดงผล)
        $date_match = str_contains($row['start_date'], $search_query) ||
            str_contains($row['end_date'], $search_query) ||
            str_contains($date_display, $search_query);

        // ค้นหาสถานะที่คำนวณได้ (เช่น "active", "expired")
        $status_match = str_contains(strtolower($status_text), $search_query);

        // ถ้าไม่ตรงกันเลยสักอย่าง ให้ข้ามแถวนี้ไป
        if (!$name_match && !$type_match && !$date_match && !$status_match) {
            continue; // ข้ามไปวนลูปแถวถัดไป
        }
    }
    // --- จบตรรกะการกรอง ---

    $found_rows++; // ถ้าไม่ข้าม แสดงว่าเจอแถวที่ตรง

    echo "<tr>";

    // (แสดงผลคอลัมน์ - เหมือนเดิม)
    echo "<td>" . htmlspecialchars($row['pr_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['discount_type']) . "</td>";
    echo "<td>" . $date_display . "</td>";
    echo "<td><span class='status-badge " . $status_class . "'>" . $status_text . "</span></td>";

    // (สร้างปุ่ม - เหมือนเดิม)
    $safe_name = htmlspecialchars($row["pr_name"], ENT_QUOTES);

    echo '<td class="actions">';
    // ปุ่ม Edit
    echo '<button class="icon-btn edit-btn" onclick="edit(' . $row["pr_id"] . ')" title="แก้ไขโปรโมชัน">'; // (เปลี่ยนชื่อฟังก์ชัน)
    echo '<span class="material-symbols-outlined">edit</span>';
    echo '</button>';

    // ปุ่ม Delete
    echo '<button class="icon-btn del-btn" onclick="del(' . $row["pr_id"] . ', \'' . $safe_name . '\')" title="ลบโปรโมชัน">'; // (เปลี่ยนชื่อฟังก์ชัน)
    echo '<span class="material-symbols-outlined">delete</span>';
    echo '</button>';

    echo '</td>';
    echo "</tr>";
}

// --- 7. (ใหม่) แสดงผลถ้าไม่พบข้อมูล ---
if ($found_rows == 0) {
    $query_display = htmlspecialchars($search_query);
    if (empty($query_display)) {
        echo '<tr><td colspan="5" class="no-results">ยังไม่มีโปรโมชันในระบบ</td></tr>';
    } else {
        echo '<tr><td colspan="5" class="no-results">ไม่พบโปรโมชันที่ตรงกับคำค้นหา "' . $query_display . '"</td></tr>';
    }
}
?>