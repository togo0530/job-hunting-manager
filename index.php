<?php

session_start();


// ログイン済み
if (isset($_SESSION["user_id"])) {

    header("Location: main.php");
    exit;
}


// 未ログイン
header("Location: login.php");
exit;

?>