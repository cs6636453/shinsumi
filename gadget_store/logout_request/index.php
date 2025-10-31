<html><body><p>กำลังออกจากระบบ...</p></body></html>
<?php
    include "../db/connect.php";
    unset($_SESSION['username']);
    session_destroy();
    header("location: ../");
?>

