<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    // save the username + password from the form on index.php
    $username = $_POST['username']; // Removed filter input to make it insecure
    $password = $_POST['password']; // Password doesn't need to be sanitized, as it will be hashed later

    // check if username already in db 
    // *** Unsecured Code: Direct SQL Query ***
    $sql = "SELECT password FROM users WHERE username = '$username'"; // No prepared statement, vulnerable
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // user exists
        $row = $result->fetch_assoc();
        $passw_in_db = $row['password'];

        // password verify is the function to check the hash if it matches the string
        // we are checking if the password on db == password entered
        if (password_verify($password, $passw_in_db)) {
            $_SESSION['username'] = $username;  // when password matches correctly
            header("Location: useronly.php");  // if so we go to useronly file
            exit();
        } else {
            echo "<br> password not correct !";
        }
    } else {
        // User does not exist -> insert new user

        // we need to hash the passw before putting it in db
        // password hash also a function for making the hash for the password
        // PASSWORD_BCRYPT is the algo for hash uses CRYPT_BLOWFISH
        // it will be 60 char wide always
        $pasw_hashed = password_hash($password, PASSWORD_BCRYPT);

        // mysqli for INSERTING values in db 
        $sql = "INSERT INTO users (username, password) VALUES ('$username', '$pasw_hashed')"; // Vulnerable to SQL injection
        if ($conn->query($sql)) {
            $_SESSION['username'] = $username;
            header("Location: useronly.php");  // if successfully executed we move to useronly file 
            exit();
        } else {
            echo "User could not be registered...  " . $conn->error;  // could not execute
        }
    }

    $conn->close();
}

$conn->close();
?>
