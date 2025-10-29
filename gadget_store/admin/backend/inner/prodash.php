<?php
    include "../../../db/connect.php";
    $stmt1 = $pdo -> prepare("select count(pid) from gs_product");
    $stmt2 = $pdo -> prepare("select count(pid) from gs_product
                                    where (stock <= 10 and stock > 0)");
    $stmt3 = $pdo -> prepare("select count(pid) from gs_product
                                    where stock = 0");
    $stmt1 -> execute();
    $stmt2 -> execute();
    $stmt3 -> execute();
    $row1 = $stmt1 -> fetch();
    $row2 = $stmt2 -> fetch();
    $row3 = $stmt3 -> fetch();
    echo $row1["count(pid)"] . "," . $row2["count(pid)"] . "," . $row3["count(pid)"];
?>