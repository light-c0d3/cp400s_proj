<?php
// secure_login.php
session_start();

// If already logged in, redirect
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'db_secure.php'; // secure DB connection

$errorBanner = ""; // Initialize error banner

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];

    // Prepare statement to select hashed password and role securely
    $stmt = $conn->prepare("SELECT password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password, $user_role);
        $stmt->fetch();

        if (!empty($hashed_password)) {
            if (password_verify($password, $hashed_password)) {
                $_SESSION['username'] = $username;
                $_SESSION['mode'] = 'secure';
                $_SESSION['role'] = $user_role;
                if ($user_role === 'teacher') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: student_dashboard.php");
                }
                exit();
            } else {
                $errorBanner = "<div class='error-banner'>Incorrect password. Please try again.</div>";
            }
        } else {
            $errorBanner = "<div class='error-banner'>Password record not found for this user.</div>";
        }
    } else {
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