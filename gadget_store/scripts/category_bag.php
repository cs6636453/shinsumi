<?php
    include "../db/connect.php";

    $query = '%'.$_POST["query"].'%';

    $stmt = $pdo -> prepare("SELECT p.pid, p.pname, p.price FROM gs_product p WHERE (p.pname LIKE ? OR p.description LIKE ?) AND p.stock >= 1;");
    $stmt -> bindParam(1, $query);
    $stmt -> bindParam(2, $query);
    $stmt -> execute();

    while ($row = $stmt -> fetch()) {
        echo "<section class='bag_item'>";
        echo "<a href='prod/index.php?id=".$row["pid"]."'><img src='assets/images/products/".$row["pid"].".png' alt='my_case'></a>";
        echo "<p>".$row["pname"]."</p>";
        echo "<span class='price'><span class='price_tag'>ราคา</span> ".$row["price"]."</span>";
        echo "<hr>";
        echo "<a href='cart/add.php?id=".$row["pid"]."' class='add_to_cart'>ใส่ตะกร้า</a>";
        echo "</section>";
    }
?>
