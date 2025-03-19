<?php
session_start();

// Ensure only logged-in students can access this page
if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: index.php");
    exit();
}

require_once 'db_secure.php'; // You can use db_secure.php or db_insecure.php as appropriate

// Fetch student user_id based on the username and ensure role is student
$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND role = 'student'");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($student_id);
$stmt->fetch();
$stmt->close();

if (!$student_id) {
    echo "<p class='error-banner'>Student record not found.</p>";
    exit();
}

// Fetch assignments with grades for this student
$stmt = $conn->prepare("
    SELECT a.assignment_id, a.title, a.description, a.due_date, g.grade
    FROM assignments a
    LEFT JOIN student_grades g ON a.assignment_id = g.assignment_id AND g.user_id = ?
    ORDER BY a.due_date ASC, a.assignment_id ASC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $newPassword = $_POST['new_password'];
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    // DB connection already established earlier; no need to require again
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $hashedPassword, $student_id);
    if ($update_stmt->execute()) {
        echo "<p class='success-banner'>Password updated successfully.</p>";
    } else {
        echo "<p class='error-banner'>Failed to update password. Please try again.</p>";
    }
    $update_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="dashboard-container">
    <h1>Welcome, <?php echo htmlspecialchars($username); ?> (Student)</h1>
    <a href="logout.php" class="logout">Logout</a>

    <h2>Your Assignments & Grades</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Assignment ID 🔑</th>
                <th>Title</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assignments as $a): ?>
            <tr>
                <td><?php echo $a['assignment_id']; ?></td>
                <td><?php echo htmlspecialchars($a['title']); ?></td>
                <td><?php echo htmlspecialchars($a['description']); ?></td>
                <td><?php echo htmlspecialchars($a['due_date']); ?></td>
                <td><?php echo ($a['grade'] !== null) ? htmlspecialchars($a['grade']) : 'N/A'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Assignment Statistics</h2>
    <?php
        // Calculate basic statistics from the student's assignment grades
        $gradesOnly = array_filter(array_column($assignments, 'grade'), fn($g) => is_numeric($g));
        if (count($gradesOnly) > 0) {
            sort($gradesOnly);
            $count = count($gradesOnly);
            $sum = array_sum($gradesOnly);
            $mean = round($sum / $count, 2);
            $high = max($gradesOnly);
            $low = min($gradesOnly);

            // Calculate Median
            if ($count % 2 === 0) {
                $median = round(($gradesOnly[$count / 2 - 1] + $gradesOnly[$count / 2]) / 2, 2);
            } else {
                $median = $gradesOnly[floor($count / 2)];
            }

            // Calculate Mode (convert numbers to string for counting)
            $roundedGrades = array_map(fn($v) => (string)round($v, 2), $gradesOnly);
            $mode_counts = array_count_values($roundedGrades);
            arsort($mode_counts);
            $mode_val = count($mode_counts) ? array_keys($mode_counts)[0] : "-";
            if (is_numeric($mode_val)) {
                $mode_val = number_format((float)$mode_val, 2);
            } else {
                $mode_val = "-";
            }

            echo "<div class='mini-form'>";
            echo "<p><strong>High:</strong> {$high}</p>";
            echo "<p><strong>Low:</strong> {$low}</p>";
            echo "<p><strong>Mean:</strong> {$mean}</p>";
            echo "<p><strong>Median:</strong> {$median}</p>";
            echo "<p><strong>Mode:</strong> {$mode_val}</p>";
            echo "</div>";
        } else {
            echo "<p>No grades available yet to calculate statistics.</p>";
        }
    ?>

    <h2>Update Your Password 🔒</h2>
    <div class="mini-form">
        <form method="POST" action="">
            <input type="password" name="new_password" placeholder="Enter New Password" required>
            <button type="submit" name="update_password">Update Password</button>
        </form>
    </div>
</div>
</body>
</html>