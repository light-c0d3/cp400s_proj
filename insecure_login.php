<?php
// insecure_login.php
// This insecure login page is intentionally vulnerable (direct SQL concatenation)
// to demonstrate SQL injection. It uses password_verify() for password checking.
// DO NOT USE IN PRODUCTION.

session_start();
// Clear any old session data to ensure a fresh start.
session_destroy();
session_start();

require_once 'db_insecure.php'; // insecure DB connection

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // No sanitization here to demonstrate injection vulnerabilities.
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Insecure direct SQL query with LIMIT 1 (vulnerable to SQL injection)
    $sql = "SELECT password, role FROM users WHERE username = '$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['password'];
        $user_role       = $row['role'];

        if (!empty($hashed_password)) {
            if (password_verify($password, $hashed_password)) {
                // Set session variables based on the fetched role.
                $_SESSION['username'] = $username;
                $_SESSION['mode']     = 'insecure';
                $_SESSION['role']     = $user_role;

                if ($user_role === 'teacher') {
                    header("Location: dashboard.php");
                    exit();
                } elseif ($user_role === 'student') {
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $error_message = "Unknown user role: " . htmlspecialchars($user_role);
                }
            } else {
                $error_message = "Incorrect password. Please try again.";
            }
        } else {
            $error_message = "Password record not found for this user.";
        }
    } else {
        $error_message = "Username '$username' not found. Please check your input.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Insecure Login</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        /* Error banner style (if not in style.css) */
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
    <?php if (!empty($error_message)): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h1>Insecure Login</h1>
    <p style="color:red; font-weight:bold;">
        This page is intentionally <strong>INSECURE</strong> to demonstrate SQL injection vulnerabilities.
    </p>

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