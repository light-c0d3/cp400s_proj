<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    // save the username + password from the form on index.php
    $username = filter_input(INPUT_POST, 'username', filter: FILTER_SANITIZE_SPECIAL_CHARS); // Sanitize input to prevent unwanted characters
    $password = $_POST['password']; // Password doesn't need to be sanitized, as it will be hashed later

    // check if username already in db 
    $stmt = $conn->prepare(query: "SELECT password FROM users WHERE username = ?"); # ADD PASW HERE AS WELL
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    #$stmt->bind_result($password);


    if ($stmt->num_rows > 0) {
        // user exists
        $stmt->bind_result( $passw_in_db);
        $stmt->fetch();
        
        // password verify is the function to check the hash if it matches the string
        // we are checking if the password on db == password entered
        if (password_verify( $password, $passw_in_db)) {
            $_SESSION['username'] = $username;  // when password matches correctly
            header(header: "Location: useronly.php");  // if so we go to useronly file
            exit();
        } else {
            echo "<br> password not correct !";
        }
    } else {
        // User does not exist -> insert new user
        $stmt->close();

        // we need to hash the passw before putting it in db
        // password hash also a function for making the hash for the password
        // PASSWORD_BCRYPT is the algo for hash uses CRYPT_BLOWFISH
            // it will be 60 char wide always
        $pasw_hashed = password_hash($password, PASSWORD_BCRYPT);

        // mysqli for INSERTING values in db 
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->bind_param( "ss", $username, $pasw_hashed);

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            header("Location: useronly.php");  // if successfully executed we move to useronly file 
            exit();
        } else {
            echo "User could not be registered...  " . $stmt->error;  // could not execute
        }
    }

    $stmt->close();
}

$conn->close();
?>
