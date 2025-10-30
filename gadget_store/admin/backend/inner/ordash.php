<?php
    include "../../../db/connect.php";
    $stmt1 = $pdo -> prepare("SELECT COUNT(ord_id) FROM gs_orders WHERE status IN ('pending', 'packing');");
    $stmt2 = $pdo -> prepare("SELECT COUNT(ord_id) FROM gs_orders WHERE status = 'shipping';");
    $stmt3 = $pdo -> prepare("SELECT COUNT(ord_id) FROM gs_orders WHERE status = 'completed' AND DATE(ord_date) = CURDATE();");
    $stmt1 -> execute();
    $stmt2 -> execute();
    $stmt3 -> execute();
    $row1 = $stmt1 -> fetch();
    $row2 = $stmt2 -> fetch();
    $row3 = $stmt3 -> fetch();
    echo $row1["COUNT(ord_id)"] . "," . $row2["COUNT(ord_id)"] . "," . $row3["COUNT(ord_id)"];
?>