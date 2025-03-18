<?php
// Required variables before connecting to database
$server_name = "localhost";
$user_name = "root";
$password = "Winterm11?";  // THIS IS DIFFERENT FOR EVERYONE
$db_name = "cp400s_proj";  

$conn = new mysqli($server_name, $user_name, $password, $db_name);

if ($conn->connect_error) {
    die("<br> Connection failed:" . $conn->connect_error);
} else {
    // this is where we will be working
    // in the case when connection is successful
    echo "<br> Connected to database !! ";
}

?>