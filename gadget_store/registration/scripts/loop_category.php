<?php
    include "../db/connect.php";

    $stmt = $pdo -> prepare("SELECT * FROM gs_category");
    $stmt -> execute();

    while ($row = $stmt -> fetch()) {
        echo '<a href="search/?search='.$row['category_name'].'"><img src="assets/images/category/'.$row["category_id"].'" alt="categories"><p>'.$row['category_name'].'</p></a>';
    }
?>