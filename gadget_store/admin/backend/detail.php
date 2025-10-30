<?php
include "../../db/connect.php"; // (ปรับ path ตามตำแหน่งไฟล์นี้)
if (!isset($_SESSION['username'])) header("Location: ../../login_request/"); // (ควรเช็กว่าเป็น Admin ด้วย)

// 1. ตรวจสอบว่ามี ID ส่งมาไหม
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) {
    die("Invalid Order ID.");
}

$errors = [];
$success_message = "";

// --- 2. (ใหม่) ตรวจสอบถ้ามีการ POST เพื่ออัปเดตสถานะ ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_status = $_POST['new_status'] ?? '';
    $posted_order_id = $_POST['order_id'] ?? 0;

    // ป้องกันการยิง POST แปลกๆ
    if ($posted_order_id == $order_id && !empty($new_status)) {

        try {
            // ดึงสถานะปัจจุบันก่อน
            $stmt_check = $pdo->prepare("SELECT status FROM gs_orders WHERE ord_id = ?");
            $stmt_check->execute([$order_id]);
            $current_status = $stmt_check->fetchColumn();

            if ($current_status === 'refunded' || $current_status === 'completed') {
                // ถ้าเป็น refunded แล้ว ห้ามแก้
                $errors[] = "ไม่สามารถอัปเดตสถานะได้ เนื่องจากออเดอร์นี้ถูก Refunded หรือ Completed แล้ว";

            } else if ($current_status !== 'refunded' && $new_status === 'refunded') {
                // *** (สำคัญ) Logic การ Refund ***

                $pdo->beginTransaction();

                // 1. ดึงรายการสินค้าในออเดอร์
                $stmt_items = $pdo->prepare("SELECT pid, quantity FROM gs_orders_item WHERE ord_id = ?");
                $stmt_items->execute([$order_id]);
                $items_to_refund = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // 2. เตรียมคำสั่งคืนสต็อก
                $stmt_update_stock = $pdo->prepare("UPDATE gs_product SET stock = stock + ? WHERE pid = ?");

                // 3. วนลูปคืนสต็อก
                foreach ($items_to_refund as $item) {
                    $stmt_update_stock->execute([$item['quantity'], $item['pid']]);
                }

                // 4. อัปเดตสถานะออเดอร์เป็น 'refunded'
                $stmt_update_order = $pdo->prepare("UPDATE gs_orders SET status = 'refunded' WHERE ord_id = ?");
                $stmt_update_order->execute([$order_id]);

                $pdo->commit();
                $success_message = "อัปเดตสถานะเป็น 'Refunded' และคืนสต็อกสินค้าเรียบร้อยแล้ว";

            } else if ($new_status !== $current_status) {
                // *** Logic การอัปเดตสถานะปกติ (ที่ไม่ใช่ refunded) ***
                $stmt_update_order = $pdo->prepare("UPDATE gs_orders SET status = ? WHERE ord_id = ?");
                $stmt_update_order->execute([$new_status, $order_id]);
                $success_message = "อัปเดตสถานะเป็น '" . htmlspecialchars($new_status) . "' เรียบร้อยแล้ว";
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
// --- จบส่วน POST ---


// --- 3. (แก้ไข) ดึงข้อมูลออเดอร์ (สำหรับ Admin) ---
// (ลบ m.username = ? ออก)
$stmt = $pdo -> prepare("SELECT o.status, o.ord_id, o.ord_date, p.pname, p.pid, oi.price_each, oi.quantity, 
                                   (oi.quantity * oi.price_each) AS 'total', o.address, o.payment_method 
                            FROM gs_orders o
                            JOIN gs_orders_item oi ON o.ord_id = oi.ord_id
                            JOIN gs_product p ON p.pid = oi.pid
                            WHERE o.ord_id = ?
                            GROUP BY p.pid");
// (แก้ bindParam)
$stmt -> bindParam(1, $order_id);
$stmt -> execute();

// --- 4. (ใหม่) ดึงข้อมูลทั้งหมดมาเก็บใน Array ก่อน ---
$all_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
$grand_total = 0;

$summary = [];
if (count($all_items) > 0) {
    $summary = $all_items[0]; // ดึงข้อมูลสรุปจากแถวแรก
    $status = htmlspecialchars($summary['status']);
    $address = nl2br(htmlspecialchars($summary['address']));
    $payment = ($summary['payment_method'] == 'cod') ? 'ชำระเงินปลายทาง' : htmlspecialchars($summary['payment_method']);
}

// (ใหม่) รายการสถานะทั้งหมด
$all_statuses = ["pending", "packing", "shipping", "completed", "failed", "cancelled", "refunded"];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <link rel="icon" href="../../assets/favicon/xobazjr.ico" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/style/nav.css" />
    <link rel="stylesheet" href="../../assets/style/global.css" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:FILL@1" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/style/index.css">
    <title>Order Detail #<?=$order_id?> | GS MyAdmin Panel</title>
    <link rel="stylesheet" href="../../assets/style/login_form.css">
    <link rel="stylesheet" href="../../assets/style/desktop_admin.css">
    <link rel="stylesheet" href="../inner.css"> <style>
        main.myMain {
            border: none; padding: 0; max-width: 1200px; margin: 20px auto; padding: 0 20px;
        }
        main.myMain h1 {
            font-size: 1.5em; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px;
        }
        main.myMain h2 {
            font-size: 1.3em; margin-bottom: 15px; margin-top: 25px;
        }
        .btn {
            text-decoration: none; text-align: center; font-weight: bold; padding: 12px 20px;
            border-radius: 4px; cursor: pointer; transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }
        .btn-primary {
            background-color: #000000; color: #ffffff; border: 1px solid #000000;
        }
        .btn-primary:hover {
            background-color: #333333; border-color: #333333;
        }
        .order-summary-card {
            background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 20px;
        }
        .order-summary-card h2 {
            font-size: 1.2em; margin-top: 0; margin-bottom: 15px;
            border-bottom: 1px solid #ddd; padding-bottom: 10px;
        }
        .summary-grid {
            display: grid; grid-template-columns: 1fr; gap: 12px;
        }
        .summary-grid p {
            margin: 0; line-height: 1.5; word-wrap: break-word;
        }
        .status-badge {
            display: inline-block; padding: 3px 10px; border-radius: 15px;
            font-weight: bold; font-size: 0.9em; color: #fff;
        }
        .status-badge.pending,
        .status-badge.packing {
            background-color: #ffc107; color: #333;
        }
        .status-badge.shipping {
            background-color: #17a2b8;
        }
        .status-badge.completed {
            background-color: #28a745;
        }
        .status-badge.cancelled,
        .status-badge.refunded,
        .status-badge.failed {
            background-color: #d9534f;
        }
        .order-item-list {
            display: flex; flex-direction: column; margin-top: 10px;
            background: #fff; border: 1px solid #eee; border-radius: 8px; overflow: hidden;
        }
        .order-item {
            display: flex; align-items: center; gap: 15px;
            padding: 15px; border-bottom: 1px solid #eee;
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 70px; height: 70px; object-fit: cover;
            border-radius: 8px; border: 1px solid #f0f0f0;
        }
        .item-details {
            flex-grow: 1; min-width: 0;
        }
        .item-details h4 {
            margin: 0 0 5px 0; font-size: 1.1em;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .item-details p {
            margin: 0; font-size: 0.9em; color: #777;
        }
        .item-line-total {
            text-align: right; font-size: 1.1em; font-weight: bold; white-space: nowrap;
        }
        .order-total-summary {
            margin-top: 20px; padding: 15px 20px; background: #f9f9f9;
            border: 1px solid #eee; border-radius: 8px;
        }
        .total-row {
            display: flex; justify-content: space-between; align-items: center;
        }
        .total-row span {
            font-size: 1.2em; color: #555;
        }
        .total-row strong {
            font-size: 1.4em; font-weight: bold;
        }
        .order-empty-message {
            padding: 40px 20px; text-align: center;
            color: #777; border: 2px dashed #eee; border-radius: 8px;
        }

        /* (ใหม่) CSS สำหรับฟอร์มและข้อความแจ้งเตือน */
        .error-list { color: red; margin-bottom: 15px; list-style-position: inside; padding-left: 20px;}
        .success-message { color: green; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px; margin-bottom: 15px; }
        .status-update-form {
            background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 20px;
            display: flex; align-items: center; gap: 15px;
        }
        .status-update-form label { font-weight: bold; }
        .status-update-form select {
            flex-grow: 1; padding: 10px; border: 1px solid #ccc;
            border-radius: 4px; height: auto; line-height: normal; font-size: 1em;
        }
        .status-update-form button {
            padding: 10px 20px; background-color: #007bff; color: white;
            border: none; border-radius: 4px; cursor: pointer; font-size: 1em;
        }
        .status-update-form button:hover { background-color: #0056b3; }
        .status-update-form select:disabled,
        .status-update-form button:disabled {
            background-color: #eee; cursor: not-allowed; opacity: 0.7;
        }

        @media (min-width: 1024px) {
            span.inner {
                max-width: 700px !important;
                margin-left: 70px !important;
            }

            div.order-item {
                width: 500px; !important;
            }

            span.inner section.order-item-list {
                min-width: 600px !important;
            }
        }
    </style>
</head>
<body>
<nav>
    <div id="top_row">
        <section class="container" id="menu_btn" onclick="animateMenuButton(this)">
            <div class="bar1"></div> <div class="bar2"></div> <div class="bar3"></div>
        </section>
        <section id="shop_name">
            <a href="../">GS MyAdmin Dashboard</a>
        </section>
        <section id="login_btn">
            <a id="login"><img src="../../assets/images/loading.gif" alt="loading"></a>
        </section>
    </div>
</nav>

<div id="side-nav-overlay"></div>

<div id="side-nav-menu" class="side-nav">
    <ul class="side-nav-list">
        <li><a href="../" class="nav-item-button"><span class="material-symbols-outlined">home</span><span>Dashboard</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../products.html" class="nav-item-button"><span class="material-symbols-outlined">package_2</span><span>Products</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../orders.html" class="nav-item-button active"><span class="material-symbols-outlined">order_approve</span><span>Orders</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
        <li><a href="../promotions.html" class="nav-item-button"><span class="material-symbols-outlined">loyalty</span><span>Promotions</span><span class="material-symbols-outlined">arrow_forward_ios</span></a></li>
    </ul>
</div>

<main class="myMain cart-page-container">
    <h1><a href="../orders.html" style="text-decoration: none; color: #888;">Orders</a> > #<?=htmlspecialchars($_GET['id'])?></h1>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>


    <?php if (count($all_items) > 0): ?>
    <span class="flex-2">
    <span class="inner">
        <h2>Update Status</h2>
        <section class="status-update-form">
            <form action="detail.php?id=<?=$order_id?>" method="POST" style="display: contents;">
                <label for="new_status">Order Status:</label>
                <input type="hidden" name="order_id" value="<?=$order_id?>">
                <select id="new_status" name="new_status" <?= ($status === 'refunded') ? 'disabled' : '' ?>>
                    <?php foreach ($all_statuses as $s): ?>
                        <option value="<?= $s ?>" <?= ($status === $s) ? 'selected' : '' ?>>
                            <?= ucfirst($s) // ทำให้ตัวแรกเป็นตัวใหญ่ ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" <?= ($status === 'refunded' || $status === 'completed') ? 'disabled' : '' ?>>Update Status</button>
            </form>
        </section>

        <h2>รายการสินค้าในออเดอร์</h2>
        <section class="order-item-list">
            <?php
            foreach ($all_items as $item) {
                $grand_total += $item['total'];
                ?>
                <div class="order-item">
                    <img src="../../assets/images/products/<?=$item['pid']?>" alt="<?=htmlspecialchars($item['pname'])?>">
                    <div class="item-details">
                        <h4><?=htmlspecialchars($item['pname'])?></h4>
                        <p><?=number_format($item['price_each'], 2)?> บาท x <?=$item['quantity']?> ชิ้น</p>
                    </div>
                    <div class="item-line-total">
                        <?=number_format($item['total'], 2)?> บาท
                    </div>
                </div>
            <?php } ?>
        </section>

        <div class="order-total-summary" style="max-width: 400px;">
            <div class="total-row">
                <span>ยอดรวมทั้งหมด</span>
                <strong><?=number_format($grand_total, 2)?> บาท</strong>
            </div>
        </div>

        </span>
        <span style="width: 350px;">
        <h2>สรุปคำสั่งซื้อ</h2>
        <section class="order-summary-card">
            <div class="summary-grid">
                <p><strong>สถานะ:</strong>
                    <span class="status-badge <?=$status?>"><?=$status?></span>
                </p>
                <p><strong>วิธีชำระเงิน:</strong> <?=$payment?></p>
                <p class="address-full"><strong>ที่อยู่จัดส่ง:</strong><br><?=$address?></p>
            </div>
        </section>
        </span>
    <?php else: ?>
        <p class="order-empty-message">ไม่พบข้อมูลคำสั่งซื้อนี้</p>
    <?php endif; ?>
        </span>

</main>


<script src="../../scripts/index.js"></script>
<script src="../../scripts/login_req.js"></script>
</body>
</html>