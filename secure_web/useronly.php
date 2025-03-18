<?php
// include 'login.php';
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    $username = "new user";
} else {
    $username = $_SESSION['username'];
}


?>
<!DOCTYPE html>
<html>

<head>
    <title> Logged in User Only </title>
</head>
<body>
    <h4> Hi, 
        <?php echo htmlspecialchars($username) . " !!!" ; ?> </h4>
    <h5> <i> This page is for users that logged in ONLY </i> </h5>


    <h3> <center> X </center> </h3>



</body>
</html>
