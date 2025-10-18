<?php
try {
    $pdo = new PDO("mysql:host=localhost;
                         dbname=168DB_59;
                         charset=utf8","168DB59","iprPhuxf");
    $pdo -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//    echo "Connection successfully";
}
catch (PDOException $e) {
    echo "Connection failed: (read below)<br>" . $e -> getMessage();
}
session_start();
?>