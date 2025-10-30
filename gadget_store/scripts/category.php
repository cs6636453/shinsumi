<?php
    include "../db/connect.php";

    $query = '%'.$_POST["query"].'%';

    $stmt = $pdo -> prepare("SELECT p.stock, p.pid, p.pname, p.price, pr.discount_type, pr.discount_value, pr.end_date FROM gs_product p LEFT JOIN gs_promotion pr ON pr.pr_id = p.pr_id WHERE (p.pname LIKE ? OR p.description LIKE ?) AND p.stock >= 1;");
    $stmt -> bindParam(1, $query);
    $stmt -> bindParam(2, $query);
    $stmt -> execute();

    while ($row = $stmt -> fetch()) {
        echo "<section class='bag_item'>";
        echo "<a href='prod/index.php?id=".$row["pid"]."'>
        <img src='assets/images/products/".
            $row["pid"].".png' alt='my_case'></a>";
        echo "<p>".$row["pname"]."</p>";
        if ($row["discount_type"] == "fixed") {
            $final_price = $row["price"]-$row["discount_value"];
            echo "<span class='price'><span class='price_tag'>ราคา</span> <s style='color: gray;'>".$row["price"]."</s><span style='color: red;'> ".round($final_price)."</span> บาท</span>";
        } else if ($row["discount_type"] == "percent") {
            $final_price = $row["price"]-($row["discount_value"]*$row["price"]);
            echo "<span class='price'><span class='price_tag'>ราคา</span> <s style='color: gray;'>".$row["price"]."</s><span style='color: red;'> ".round($final_price)."</span> บาท</span>";
        } else {
            echo "<span class='price'><span class='price_tag'>ราคา</span> ".$row["price"]." บาท</span>";
        }
        echo "<hr>";
        if ($row['stock'] > 0) {
            echo "<a href='cart/add.php?id=".$row["pid"]."&count=1' class='add_to_cart'>ใส่ตะกร้า</a>";
        }
        else {
            echo "<a class='add_to_cart' style='color: gray;'>ขออภัยสินค้าหมดแล้ว</a>";
        }
        echo "</section>";
    }
?>
