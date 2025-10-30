<?php
include "../../../db/connect.php"; // Ensure this connects $pdo and starts session if needed

// 1. Get and Prepare Search Query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$search_param = '%' . $search_query . '%'; // Add wildcards for LIKE

// 2. Prepare SQL Statement
// Selects order info, calculates total quantity and price per order
// Joins orders and items
// Filters based on search query against ord_id, address (for name/phone), date, and status
// Groups results by order to get totals per order
$sql = "SELECT
                o.ord_id,
                o.address,
                o.ord_date,
                o.status,
                SUM(oi.quantity) AS total_quantity,
                SUM(oi.price_each * oi.quantity) AS total_price
            FROM
                gs_orders o
            JOIN
                gs_orders_item oi ON o.ord_id = oi.ord_id
            WHERE (
                o.ord_id LIKE ? OR      -- Search by Order ID
                o.address LIKE ? OR     -- Search by Address (contains name, phone etc.)
                o.ord_date LIKE ? OR    -- Search by Date (as string, e.g., '2025-10-%')
                o.status LIKE ?         -- Search by Status
                -- Note: Searching directly by item quantity/price in this main query is complex
                -- because it requires checking individual items *before* grouping.
                -- This search focuses on order-level details.
            )
            GROUP BY
                o.ord_id -- Group by order ID is sufficient if other selected o.* columns are functionally dependent
            ORDER BY
                o.ord_date DESC"; // Show most recent orders first

$stmt = $pdo->prepare($sql);

// 3. Bind Parameters (bind the same search param to all placeholders)
$stmt->bindParam(1, $search_param);
$stmt->bindParam(2, $search_param);
$stmt->bindParam(3, $search_param);
$stmt->bindParam(4, $search_param);

// 4. Execute and Fetch Results
$stmt->execute();

// 5. Loop Through Results and Generate HTML Table Rows
if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Extract Name/Phone from address (Simple approach)
        $name_part = $row['address'];
        $first_line = explode("\n", $name_part, 2)[0]; // Get the first line
        $customer_info = htmlspecialchars(trim($first_line)); // Name (Phone)

        echo "<tr>";
        // Order ID - Clickable Link
        echo '<td><a href="backend/detail.php?id=' . htmlspecialchars($row['ord_id']) . '">#' . htmlspecialchars($row['ord_id']) . '</a></td>';
        // Customer Info (Name (Phone))
        echo '<td>' . $customer_info . '</td>';
        // Order Date
        echo '<td>' . htmlspecialchars($row['ord_date']) . '</td>';
        // Status
        echo '<td class="' . htmlspecialchars($row['status']) . '">' . htmlspecialchars($row['status']) . '</td>'; // Add class for potential styling
        // Total Quantity
        echo '<td>' . htmlspecialchars($row['total_quantity']) . '</td>';
        // Total Price - Formatted
        echo '<td class="price">฿ ' . number_format($row['total_price'], 2) . '</td>';
        echo "</tr>";
    }
} else {
    // Optional: Display a message if no orders match the search
    echo '<tr><td colspan="6" style="text-align:center; color: #777; padding: 20px;">No orders found matching your query.</td></tr>';
}

?>