<?php

$host = "127.0.0.1";
$user = "root";
$password = "";
$database = "projectfcw";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
