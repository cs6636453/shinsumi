<?php
    include "../../../db/connect.php";
    $query = '%'.$_GET['query'].'%';
    $stmt = $pdo -> prepare("select p.pid, p.pname, p.price, c.category_name, p.stock from gs_product p join gs_category c on c.category_id = p.category_id where p.pname like ?;");
    $stmt -> bindParam(1, $query);
    $stmt -> execute();
    while ($row = $stmt -> fetch()) {
        if ($row['stock'] > -1) {
        echo "<tr>";

            echo "<td>".$row['pname']."</td>";
            echo "<td>".$row['price']."</td>";
            echo "<td>".$row['category_name']."</td>";

        echo '<td><input type="button" class="add" onclick="edit('.$row["pid"].')" value="แก้ไขสินค้า"></td>
              <td><input type="button" class="del" onclick="del(' . $row["pid"] . ', \'' . htmlspecialchars($row["pname"], ENT_QUOTES) . '\')" value="ลบสินค้า"></td>';
        echo "</tr>";
        }
    }
?>