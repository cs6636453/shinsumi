<?php
    include "../db/connect.php";

    $stmt = $pdo -> prepare("select p.pid, p.pname from gs_product p join gs_recommend r on r.product_id = p.pid;");
    $stmt -> execute();

    while ($row = $stmt -> fetch()) {
        echo '<a href="search/?search='.$row['pname'].'"><img src="assets/images/products/'.$row["pid"].'" alt="categories"><p>'.$row['pname'].'</p></a>';
    }
?>