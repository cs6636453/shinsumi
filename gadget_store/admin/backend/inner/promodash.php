<?php
    include "../../../db/connect.php";
    $stmt1 = $pdo -> prepare("select count(pr_id) from gs_promotion");
    $stmt1 -> execute();
    $row1 = $stmt1 -> fetch();
    echo $row1["count(pr_id)"];
?>