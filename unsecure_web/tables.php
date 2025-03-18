<?php
// Required variables before connecting to database
$server_name = "localhost";
$user_name = "root";
$password = "Winterm11?";  // THIS IS DIFFERENT FOR EVERYONE
$db_name = "cp400s_proj";  

$conn = new mysqli($server_name, $user_name, $password);

if ($conn->connect_error) {
    die("Connection failed:" . $conn->connect_error);
}

// Creating the database from here
$table_create = "CREATE DATABASE IF NOT EXISTS $db_name";

if ($conn->query($table_create) === TRUE) {
    echo "Database created !!! <br>";
} else {
    echo "Error occured: " . $conn->error . "<br>";
}


// Select current database to work on it
$conn->select_db($db_name);



// THIS IS FOR USER TABLE {USERS CREDENTIALS ARE STORED IN HERE}
$user_table = "CREATE TABLE IF NOT EXISTS  USERS (
    user_id INT AUTO_INCREMENT PRIMARY KEY, # this would help on sql tables
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";





$conn->close();

?>
