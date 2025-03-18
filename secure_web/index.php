<!DOCTYPE html>
<html>
<head>
    <title> Login Page </title>
    <link href="style.css"
        type = "text/css"
        rel= "stylesheet">

</head>

<body style="background-image: url('dark.png'); background-size: cover; background-position: center; background-repeat: no-repeat;">
</body>


<body>
    <?php
    // php code starts from here
    
    echo '<p> Log in  </p>';
    
    function login_form($usern = '', $pasw= '') {
    ?>
    <form action="login.php" method="post">
        <label> Username: <input type= "text" name="username" required ="required" value="<?php echo $usern?>" >

        </label> <br>

        <label> Password: <input type="password" name="password" required ="required" value="<?php echo $pasw ?>" >

        </label> <br>

        <input type="submit" name="submit" value="Submit">
        <input type="reset">

    </form>

    <?php
    }
    login_form();  // call the function to display the html form on page
    ?>

</body>
</html>