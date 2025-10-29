<?php
header('Content-Type: application/json');
include "../../../db/connect.php";

date_default_timezone_set('Asia/Bangkok');

// --- 1. Define the canonical order of all possible statuses ---
$all_statuses_ordered = [
    "pending",
    "packing",
    "shipping",
    "completed",
    "failed",
    "cancelled",
    "refunded"
];
// -----------------------------------------------------------

$period = $_GET['query'] ?? 'day';
$date_condition = "";

switch ($period) {
    case 'week':
        $date_condition = "WHERE ord_date >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'month':
        $date_condition = "WHERE YEAR(ord_date) = YEAR(CURDATE()) AND MONTH(ord_date) = MONTH(CURDATE())";
        break;
    case 'year':
        $date_condition = "WHERE ord_date >= CURDATE() - INTERVAL 364 DAY";
        break;
    case 'day':
    default:
        $date_condition = "WHERE DATE(ord_date) = CURDATE()";
        break;
}

$status_counts_from_db = []; // Temporary map to store counts from DB query
$final_statusListX = [];   // Final array for labels
$final_orderListY = [];    // Final array for counts

try {
    // --- 2. Query DB for counts of EXISTING statuses in the period ---
    $sql = "SELECT status, COUNT(ord_id) AS status_count
                FROM gs_orders
                {$date_condition} -- Inject the date filter
                GROUP BY status"; // No need for ORDER BY here anymore

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // --- 3. Store the DB results in the map ---
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status_counts_from_db[$row['status']] = (int)$row['status_count'];
    }
    // ------------------------------------------

    // --- 4. Loop through the canonical list and build final arrays ---
    foreach ($all_statuses_ordered as $status) {
        $final_statusListX[] = $status; // Add the status name
        // Add the count from DB map, or 0 if it wasn't found in the query result
        $final_orderListY[] = $status_counts_from_db[$status] ?? 0;
    }
    // -------------------------------------------------------------

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    // Return error state if needed, ensuring structure matches
    $final_statusListX = ["Error"];
    $final_orderListY = [0];
}

// 5. Echo the final JSON output
echo json_encode([
    'statusListX' => $final_statusListX, // Always contains all statuses in order
    'orderListY' => $final_orderListY   // Counts corresponding to the ordered statuses
]);
?>