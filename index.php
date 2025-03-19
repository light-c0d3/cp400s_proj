<?php
// index.php
session_start();

// If user is already logged in, redirect based on role
if (isset($_SESSION['username']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'teacher') {
        header("Location: dashboard.php");
    } else {
        header("Location: student_dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Class Management Tool - Home</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .info-banner {
            background: #f4f4f4;
            padding: 15px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h1>Class Management Tool (To Demonstrate SQL Injection Attacks & Countermeasures)</h1>
    <div class="info-banner">
        <h2>📚 Welcome to the Class Management Tool</h2>
        <p>This system is designed for managing classes for <strong>teachers</strong> and <strong>students</strong>.</p>
        <p>✨ <strong>Features include:</strong></p>
        <div style="text-align: left; max-width: 700px; margin: 0 auto;">
            <p>🔒 Secure login using modern best practices</p>
            <p>⚠️ Insecure login to demonstrate SQL injection attacks (educational use only)</p>
            <p>👩‍🏫 <strong>Teacher Dashboard</strong> for managing students, assignments, and grades</p>
            <p>🎓 <strong>Student Dashboard</strong> to view assignment grades and statistics</p>
        </div>
        <p style="margin-top: 15px;"><strong>🚨 Warning:</strong> The insecure login option is <span style="color: red; font-weight: bold;">deliberately vulnerable</span> to SQL injection attacks. It is intended for <em>classroom demonstration only</em>.</p>
    </div>
    <p>Please choose your login method below:</p>
    <div style="margin-top: 20px;">
        <a href="secure_login.php" style="display:inline-block; margin:10px; padding:10px 20px; background:#4CAF50; color:white; border-radius:5px; text-decoration:none; font-weight:bold;">🔒 Secure Login</a>
        <a href="insecure_login.php" style="display:inline-block; margin:10px; padding:10px 20px; background:#f44336; color:white; border-radius:5px; text-decoration:none; font-weight:bold;">⚠️ Insecure Login</a>
    </div>
</div>
</body>
</html>