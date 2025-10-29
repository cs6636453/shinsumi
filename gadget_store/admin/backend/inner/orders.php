<?php
header('Content-Type: application/json'); // Set header for JSON response
include "../../../db/connect.php"; // Assuming this connects $pdo

// --- Set PHP Timezone ---
date_default_timezone_set('Asia/Bangkok');
// -------------------------

// Helper function to calculate "time ago"
function timeAgo($timestamp) {
    $datetime = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
    if ($datetime === false) {
        return 'Invalid date';
    }
    $now = time();
    $diff = $now - $datetime;

    if ($diff < 60) {
        return $diff . ' วินาทีที่แล้ว';
    } elseif ($diff < 3600) { // 60 * 60
        return floor($diff / 60) . ' นาทีที่แล้ว';
    } elseif ($diff < 86400) { // 60 * 60 * 24
        return floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
    } elseif ($diff < 604800) { // 60 * 60 * 24 * 7
        return floor($diff / 86400) . ' วันที่แล้ว';
    } elseif ($diff < 2592000) { // 60 * 60 * 24 * 30 (approx month)
        return floor($diff / 604800) . ' สัปดาห์ที่แล้ว';
    } elseif ($diff < 31536000) { // 60 * 60 * 24 * 365
        return floor($diff / 2592000) . ' เดือนที่แล้ว';
    } else {
        return floor($diff / 31536000) . ' ปีที่แล้ว';
    }
}

// 1. Get period, default to 'day'
$period = $_GET['query'] ?? 'day';

// 2. Define SQL date condition based on period
$date_condition = "";
switch ($period) {
    case 'week':
        $date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'month':
        $date_condition = "WHERE YEAR(o.ord_date) = YEAR(CURDATE()) AND MONTH(o.ord_date) = MONTH(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 364 DAY";
        break;
    case 'day':
    default:
        $date_condition = "WHERE DATE(o.ord_date) = CURDATE()";
        break;
}

$orders_data = []; // Array to hold the results

try {
    // 3. Prepare and execute the SQL query
    $sql = "SELECT
                    o.ord_id,
                    o.address,
                    SUM(oi.quantity * oi.price_each) AS total_price,
                    o.status,
                    o.ord_date
                FROM gs_orders o
                JOIN gs_orders_item oi ON o.ord_id = oi.ord_id
                {$date_condition} -- Inject the date condition
                GROUP BY o.ord_id -- Group by order ID to get total per order
                ORDER BY o.ord_date DESC"; // Order by most recent first

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 4. Fetch results and process data
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extract Name/Surname (assuming format "First Last (Phone)\n...")
        $name_part = $row['address'];
        $first_line = explode("\n", $name_part, 2)[0]; // Get the first line
        $name_only = trim(explode("(", $first_line)[0]); // Get text before '('

        // Calculate time ago
        $time_ago = timeAgo($row['ord_date']);

        // Add processed data to the result array
        $orders_data[] = [
            'id' => $row['ord_id'],
            'name' => htmlspecialchars($name_only), // Use extracted name
            'total' => (float)$row['total_price'],
            'status' => htmlspecialchars($row['status']),
            'timeAgo' => $time_ago
        ];
    }

} catch (PDOException $e) {
    // Handle potential database errors
    error_log("Database Error: " . $e->getMessage()); // Log the actual error
    // Return an empty array or an error indicator in JSON
    // $orders_data = [['error' => 'Database error']];
}

// 5. Echo the final JSON output
echo json_encode($orders_data); // Echo the array of order objects

?>