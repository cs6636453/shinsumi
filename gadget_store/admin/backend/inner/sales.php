<?php
header('Content-Type: application/json'); // Set header for JSON response
include "../../../db/connect.php"; // Assuming this connects $pdo

// --- Set PHP Timezone ---
date_default_timezone_set('Asia/Bangkok');
// -------------------------

// 1. Get period, default to 'day'
$period = $_GET['query'] ?? 'day';

// 2. Define SQL date condition based on period
$date_condition = "";
switch ($period) {
    case 'week':
        // Last 7 days (including today)
        $date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'month':
        // Current calendar month
        $date_condition = "WHERE YEAR(o.ord_date) = YEAR(CURDATE()) AND MONTH(o.ord_date) = MONTH(CURDATE())";
        break;
    case 'year':
        // Last 365 days (including today)
        $date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 364 DAY";
        break;
    case 'day':
    default:
        // Today only
        $date_condition = "WHERE DATE(o.ord_date) = CURDATE()";
        break;
}

// Initialize arrays for JSON output
$productNames = [];
$quantitiesSold = [];
$currentStocks = [];

try {
    // 3. Prepare and execute the SQL query
    // - SUM(oi.quantity) calculates total sold in the period
    // - MAX(p.stock) gets the current stock (assuming stock doesn't change based on order date within the group)
    $sql = "SELECT
                    p.pname,
                    SUM(oi.quantity) AS quantity_sold,
                    MAX(p.stock) AS current_stock -- Get the current stock level
                FROM gs_product p
                JOIN gs_orders_item oi ON oi.pid = p.pid
                JOIN gs_orders o ON o.ord_id = oi.ord_id
                {$date_condition} -- Inject the date condition
                GROUP BY p.pid, p.pname -- Group by product ID and name
                ORDER BY quantity_sold DESC"; // Optional: Order by most sold

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    // 4. Fetch results and populate arrays
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productNames[] = $row['pname'];
        $quantitiesSold[] = (int)$row['quantity_sold']; // Ensure it's an integer
        $currentStocks[] = (int)$row['current_stock']; // Ensure it's an integer
    }

} catch (PDOException $e) {
    // Handle potential database errors
    error_log("Database Error: " . $e->getMessage()); // Log the actual error
    // Return empty arrays or an error indicator in JSON
    // $productNames = ["Error"];
    // $quantitiesSold = [0];
    // $currentStocks = [0];
}

// 5. Echo the final JSON output
echo json_encode([
    'productNames' => $productNames,
    'quantitiesSold' => $quantitiesSold,
    'currentStocks' => $currentStocks
]);

?>