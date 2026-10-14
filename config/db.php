<?php

$host = "sql104.infinityfree.com";
$user = "if0_41221264";
$password = "Shangxi2024";
$database = "if0_41221264_faith_connect_db";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

?>
