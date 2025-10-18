<html><body><p>กำลังออกจากระบบ...</p></body></html>
<?php
    include "../db/connect.php";
    session_destroy();
    header("location: ../");
?>

