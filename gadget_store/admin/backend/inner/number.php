<?php
include "../../../db/connect.php"; // Assuming this has session_start() and connects $pdo

// 1. Get the requested period from query parameter, default to 'day'
$period = $_GET['query'] ?? 'day';

// 2. Define SQL date conditions based on the period
$order_date_condition = "";
$member_date_condition = "";

switch ($period) {
    case 'week':
        // Last 7 days (including today)
        $order_date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 6 DAY";
        $member_date_condition = "WHERE m.creation_date >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'month':
        // Current calendar month
        $order_date_condition = "WHERE YEAR(o.ord_date) = YEAR(CURDATE()) AND MONTH(o.ord_date) = MONTH(CURDATE())";
        $member_date_condition = "WHERE YEAR(m.creation_date) = YEAR(CURDATE()) AND MONTH(m.creation_date) = MONTH(CURDATE())";
        break;
    case 'year':
        // Last 365 days (including today)
        $order_date_condition = "WHERE o.ord_date >= CURDATE() - INTERVAL 364 DAY";
        $member_date_condition = "WHERE m.creation_date >= CURDATE() - INTERVAL 364 DAY";
        break;
    case 'day':
    default:
        // Today only
        $order_date_condition = "WHERE DATE(o.ord_date) = CURDATE()";
        $member_date_condition = "WHERE DATE(m.creation_date) = CURDATE()";
        break;
}

// Initialize results
$total_sales = 0;
$order_count = 0;
$member_count = 0;

try {
    // --- Query 1: Total Sales ---
    $sql_sales = "SELECT SUM(oi.price_each * oi.quantity) AS total_sales
                      FROM gs_orders_item oi
                      JOIN gs_orders o ON o.ord_id = oi.ord_id
                      {$order_date_condition}"; // Inject date condition
    $stmt_sales = $pdo->prepare($sql_sales);
    $stmt_sales->execute();
    $total_sales = $stmt_sales->fetchColumn() ?: 0;

    // --- Query 2: Order Count ---
    $sql_orders = "SELECT COUNT(o.ord_id) AS order_count
                       FROM gs_orders o
                       {$order_date_condition}"; // Inject date condition
    $stmt_orders = $pdo->prepare($sql_orders);
    $stmt_orders->execute();
    $order_count = $stmt_orders->fetchColumn() ?: 0;

    // --- Query 3: New Member Count ---
    $sql_members = "SELECT COUNT(m.username) AS member_count
                        FROM gs_member m
                        {$member_date_condition}"; // Inject date condition
    $stmt_members = $pdo->prepare($sql_members);
    $stmt_members->execute();
    $member_count = $stmt_members->fetchColumn() ?: 0;

} catch (PDOException $e) {
    // Handle errors - Outputting 0 for all might be safer for AJAX
    // You might want to log the actual error: error_log($e->getMessage());
    $total_sales = 0;
    $order_count = 0;
    $member_count = 0;
    // Optionally, you could output an error indicator recognizable by AJAX
    // echo "ERROR";
    // exit;
}

// 4. Echo the results separated by a comma (easy for AJAX to split)
echo $total_sales . "," . $order_count . "," . $member_count;

?>