<?php

$dsn = 'mysql:dbname=＊＊＊＊＊＊＊＊;host=localhost';
$user = '＊＊＊＊＊＊＊＊';
$password = '＊＊＊＊＊＊＊＊';

$pdo = new PDO(
    $dsn,
    $user,
    $password,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING)
);

?>