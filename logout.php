<?php

session_start();


// SESSIONの中身を空にする
$_SESSION = array();


// SESSIONを終了
session_destroy();


// ログイン画面へ
header("Location: login.php");
exit;

?>