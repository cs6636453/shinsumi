<?php include "connect.php"?>

<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
<table border="1">
    <tr>
        <th>วันที่</th>
        <th>ยอดรวม</th>
    </tr>
<?php
$stmt = $pdo -> prepare ("SELECT o.ord_date, SUM(p.price*oi.quantity) AS 'total' FROM gs_orders o JOIN gs_orders_item oi ON o.ord_id = oi.ord_id JOIN gs_product p ON p.pid = oi.pid GROUP BY o.ord_date;");
$stmt -> execute();
while ($row = $stmt -> fetch()) {
    ?>
        <tr>
            <td><?=$row['ord_date']?></td>
            <td><?=$row['total']?></td>
        </tr>
    <?php
}
?>
</table>
</body>
</html>
