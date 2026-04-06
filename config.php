<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default Laragon password is empty
$dbname = "smart_budget_db"; // Ensure this matches your database name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>