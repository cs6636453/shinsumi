<?php
header('Content-Type: application/json');

// --- Set PHP Timezone (Still needed!) ---
date_default_timezone_set('Asia/Bangkok');
// ------------------------------------

include "../../../db/connect.php";

// 1. Get period (same as before)
$period = $_GET['query'] ?? 'day';

// 2. Define parameters (same as before - based on Bangkok time)
$sql_group_format = '';
$sql_start_condition_php_time = '';
$php_label_format = '';
$php_interval_string = '';
$php_start_modifier = '';
$intervals_to_generate = 7;

switch ($period) {
    case 'week':
        $sql_group_format = '%Y-%m-%d';
        $sql_start_condition_php_time = 'CURDATE() - INTERVAL 6 DAY';
        $php_label_format = 'd/m';
        $php_interval_string = 'P1D';
        $php_start_modifier = '-6 days';
        break;
    case 'month':
        $sql_group_format = '%Y-%m';
        $sql_start_condition_php_time = 'DATE_FORMAT(NOW() - INTERVAL 6 MONTH, \'%Y-%m-01\')';
        $php_label_format = 'M/y';
        $php_interval_string = 'P1M';
        $php_start_modifier = '-6 months';
        break;
    case 'year':
        $sql_group_format = '%Y';
        $sql_start_condition_php_time = 'DATE_FORMAT(NOW() - INTERVAL 6 YEAR, \'%Y-01-01\')';
        $php_label_format = 'Y';
        $php_interval_string = 'P1Y';
        $php_start_modifier = '-6 years';
        break;
    case 'day':
    default:
        $period = 'day';
        $sql_group_format = '%Y-%m-%d %H:00';
        $sql_start_condition_php_time = 'NOW() - INTERVAL 6 HOUR';
        $php_label_format = 'H:00';
        $php_interval_string = 'PT1H';
        $php_start_modifier = '-6 hours';
        break;
}

$results_map = [];
$dateX_data = [];
$priceY_data = [];

try {
    // --- 3. Prepare and execute SQL query WITHOUT CONVERT_TZ() ---
    $sql = "SELECT
                    -- Format the existing UTC+7 time for grouping
                    DATE_FORMAT(o.ord_date, ?) AS time_unit,
                    SUM(oi.price_each * oi.quantity) AS total_sales
                FROM gs_orders o
                JOIN gs_orders_item oi ON o.ord_id = oi.ord_id
                WHERE
                    -- Compare the existing UTC+7 time directly
                    o.ord_date >= ({$sql_start_condition_php_time})
                GROUP BY time_unit
                ORDER BY time_unit ASC";

    $stmt = $pdo->prepare($sql);
    // Bind the DATE_FORMAT string
    $stmt->execute([$sql_group_format]);
    // ---------------------------------------------------------

    // 4. Fetch results into map (Same as before)
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        try {
            if ($period === 'day') {
                $dt_key = DateTime::createFromFormat('Y-m-d H:i', $row['time_unit']);
            } elseif ($period === 'week') {
                $dt_key = DateTime::createFromFormat('Y-m-d', $row['time_unit']);
            } elseif ($period === 'month') {
                $dt_key = DateTime::createFromFormat('Y-m', $row['time_unit']);
            } elseif ($period === 'year') {
                $dt_key = DateTime::createFromFormat('Y', $row['time_unit']);
            }

            if ($dt_key) {
                $map_key = $dt_key->format($php_label_format);
                $results_map[$map_key] = (float)$row['total_sales'];
            }
        } catch (Exception $e) {
            error_log("Date parsing error for time_unit: " . $row['time_unit'] . " - " . $e->getMessage());
        }
    }

    // 5. Generate final data points (Same as before - uses PHP DateTime set to Asia/Bangkok)
    $end_dt = new DateTime();
    $start_dt = (new DateTime())->modify($php_start_modifier);

    if ($period === 'month') {
        $start_dt->modify('first day of this month');
    } elseif ($period === 'year') {
        $start_dt->modify('first day of January this year');
    }

    $interval_obj = new DateInterval($php_interval_string);
    $current_dt = clone $start_dt;

    for ($i = 0; $i < $intervals_to_generate; $i++) {
        $label = $current_dt->format($php_label_format);
        $dateX_data[] = $label;
        $priceY_data[] = $results_map[$label] ?? 0;

        $current_dt->add($interval_obj);
        if ($current_dt > (new DateTime())->modify('+1 day')) break;
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $dateX_data = ["Error"];
    $priceY_data = [0];
} catch (Exception $e) {
    error_log("Date/Time Error: " . $e->getMessage());
    $dateX_data = ["Error"];
    $priceY_data = [0];
}

// 6. Echo JSON output
echo json_encode([
    'dateX' => $dateX_data,
    'priceY' => $priceY_data
]);

?>