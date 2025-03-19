<?php
// db_insecure.php
$server_name = "localhost";
$user_name   = "root";
$password    = "";  // Insert your real password if needed
$db_name     = "cp400s_proj";

$conn = new mysqli($server_name, $user_name, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed (insecure): " . $conn->connect_error);
}
?>