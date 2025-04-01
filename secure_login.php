<?php
// secure_login.php
// Start or resume the current session to manage user login state securely.
session_start();

// If the user is already logged in, redirect them to the dashboard to avoid duplicate login.
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

// Use a secure database connection with prepared statements to prevent SQL injection.
require_once 'db_secure.php'; // secure DB connection

$errorBanner = ""; // Initialize error banner

// Handle the form submission only when the request method is POST (i.e., form was submitted).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize username input to prevent XSS attacks.
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS); // ***************
    $password = $_POST['password'];

    // Use a prepared statement to securely fetch the hashed password and user role for the given username.
    $stmt = $conn->prepare("SELECT password, role FROM users WHERE username = ?"); // ***************
    // Bind the sanitized username to the SQL query.
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Check if the username exists in the database.
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password, $user_role);
        $stmt->fetch();

        if (!empty($hashed_password)) {
            // Securely verify the entered password against the hashed password from the database.
            if (password_verify($password, $hashed_password)) {
                // Set session variables to persist user information across pages.
                // Also set a 'mode' to indicate secure login and role for access control.
                $_SESSION['username'] = $username;
                $_SESSION['mode'] = 'secure';
                $_SESSION['role'] = $user_role;
                // Redirect the user based on their role after successful login.
                if ($user_role === 'teacher') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                // Show specific error messages for invalid login attempts.
                // Avoid overly detailed messages in production to prevent information leakage.
                $errorBanner = "<div class='error-banner'>Incorrect password. Please try again.</div>";
            }
        } else {
            // Show specific error messages for invalid login attempts.
            // Avoid overly detailed messages in production to prevent information leakage.
            $errorBanner = "<div class='error-banner'>Password record not found for this user.</div>";
        }
    } else {
        // Show specific error messages for invalid login attempts.
        // Avoid overly detailed messages in production to prevent information leakage.
        $errorBanner = "<div class='error-banner'>Username not found. Please check your input.</div>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure Login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .error-banner {
            background-color: #f44336;
            color: white;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="login-container">
    <?php if (!empty($errorBanner)) echo $errorBanner; ?>
    <h1>Secure Login</h1>
    <form action="" method="POST">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
    <p><a href="index.php">Back to Home</a></p>
</div>
</body>
</html>