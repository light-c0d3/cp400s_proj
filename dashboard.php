<?php
// dashboard.php (Refactored: displays all tables; options for students, assignments, grades, student password updates, and editing rows)
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['mode'])) {
    header("Location: index.php");
    exit();
}

$mode = $_SESSION['mode'];
// Use secure or insecure DB connection based on mode
if ($mode === 'secure') {
    require_once 'db_secure.php';
} else {
    require_once 'db_insecure.php';
}

// Process form submissions for add, remove, edit operations

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ---- STUDENTS SECTION ----
    // Add student and for each existing assignment add grade row
    if (isset($_POST['add_student'])) {
        $newStudentName = $_POST['student_name'];
        // Check if the student already exists
        $check_sql = "SELECT user_id FROM users WHERE username = '" . $conn->real_escape_string($newStudentName) . "' AND role = 'student'";
        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            echo "<p style='color:red;'>Student with username '{$newStudentName}' already exists.</p>";
        } else {
            $studentPassword = $_POST['student_password'];
            if ($mode === 'secure') {
                $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'student')");
                $hashedPass = password_hash($studentPassword, PASSWORD_BCRYPT);
                $stmt->bind_param("ss", $newStudentName, $hashedPass);
                $stmt->execute();
                $new_student_id = $conn->insert_id;
                $stmt->close();
            } else {
                $hashed = password_hash($studentPassword, PASSWORD_BCRYPT);
                $sql = "INSERT INTO users (username, password, role) VALUES ('" . $conn->real_escape_string($newStudentName) . "', '" . $conn->real_escape_string($hashed) . "', 'student')";
                $conn->query($sql);
                $new_student_id = $conn->insert_id;
            }
            // For each assignment, insert a grade row if it doesn't exist.
            $result_assignments = $conn->query("SELECT assignment_id FROM assignments");
            if ($result_assignments) {
                while ($row = $result_assignments->fetch_assoc()) {
                    $assignment_id = $row['assignment_id'];
                    if ($mode === 'secure') {
                        $stmt2 = $conn->prepare("INSERT INTO student_grades (user_id, assignment_id, grade) VALUES (?, ?, ?)");
                        $default_grade = null;
                        $stmt2->bind_param("iid", $new_student_id, $assignment_id, $default_grade);
                        $stmt2->execute();
                        $stmt2->close();
                    } else {
                        $sql2 = "INSERT INTO student_grades (user_id, assignment_id, grade) VALUES ($new_student_id, $assignment_id, NULL)";
                        $conn->query($sql2);
                    }
                }
            }
        }
    }    
    // Remove student (first delete related student_grades then the student)
    if (isset($_POST['remove_student'])) {
        $studentId = $_POST['student_id'];
        if ($mode === 'secure') {
            // Delete related student_grades rows first to satisfy FK constraint
            $stmt = $conn->prepare("DELETE FROM student_grades WHERE user_id = ?");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $stmt->close();
            // Then delete the student record
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student'");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "DELETE FROM student_grades WHERE user_id = $studentId";
            $conn->query($sql);
            $sql = "DELETE FROM users WHERE user_id = $studentId AND role = 'student'";
            $conn->query($sql);
        }
    }
    // Edit student: update username and password
    if (isset($_POST['edit_student'])) {
        $studentId = $_POST['student_id'];
        $newUsername = trim($_POST['new_username']);
        $newPassword = trim($_POST['new_password']);
        if ($mode === 'secure') {
            $fields = [];
            $types = "";
            $params = [];
            if (!empty($newUsername)) {
                $fields[] = "username = ?";
                $types .= "s";
                $params[] = $newUsername;
            }
            if (!empty($newPassword)) {
                $fields[] = "password = ?";
                $types .= "s";
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $params[] = $hashed;
            }
            if (!empty($fields)) {
                $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = ? AND role = 'student'";
                $types .= "i";
                $params[] = $studentId;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $set = [];
            if (!empty($newUsername)) {
                $set[] = "username = '" . $conn->real_escape_string($newUsername) . "'";
            }
            if (!empty($newPassword)) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $set[] = "password = '" . $conn->real_escape_string($hashed) . "'";
            }
            if (!empty($set)) {
                $sql = "UPDATE users SET " . implode(", ", $set) . " WHERE user_id = $studentId AND role = 'student'";
                $conn->query($sql);
            }
        }
    }

    // ---- ASSIGNMENTS SECTION ----
    // Add assignment and auto-populate student_grades for each student
    if (isset($_POST['add_assignment'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        if ($mode === 'secure') {
            $stmt = $conn->prepare("INSERT INTO assignments (title, description, due_date) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $description, $due_date);
            $stmt->execute();
            $new_assignment_id = $conn->insert_id;
            $stmt->close();
        } else {
            $sql = "INSERT INTO assignments (title, description, due_date) VALUES ('$title', '$description', '$due_date')";
            $conn->query($sql);
            $new_assignment_id = $conn->insert_id;
        }
        // For each student, add a grade row if not already present.
        $result_students = $conn->query("SELECT user_id FROM users WHERE role = 'student'");
        if ($result_students) {
            while ($row = $result_students->fetch_assoc()) {
                $student_id = $row['user_id'];
                if ($mode === 'secure') {
                    $stmt2 = $conn->prepare("INSERT INTO student_grades (user_id, assignment_id, grade) VALUES (?, ?, ?)");
                    $default_grade = null;
                    $stmt2->bind_param("iid", $student_id, $new_assignment_id, $default_grade);
                    $stmt2->execute();
                    $stmt2->close();
                } else {
                    $sql2 = "INSERT INTO student_grades (user_id, assignment_id, grade) VALUES ($student_id, $new_assignment_id, NULL)";
                    $conn->query($sql2);
                }
            }
        }
    }
    // Remove assignment (delete related grades first)
    if (isset($_POST['remove_assignment'])) {
        $assignmentId = $_POST['assignment_id'];
        if ($mode === 'secure') {
            $stmt = $conn->prepare("DELETE FROM student_grades WHERE assignment_id = ?");
            $stmt->bind_param("i", $assignmentId);
            $stmt->execute();
            $stmt->close();
            $stmt = $conn->prepare("DELETE FROM assignments WHERE assignment_id = ?");
            $stmt->bind_param("i", $assignmentId);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "DELETE FROM student_grades WHERE assignment_id = $assignmentId";
            $conn->query($sql);
            $sql = "DELETE FROM assignments WHERE assignment_id = $assignmentId";
            $conn->query($sql);
        }
    }
    // Edit assignment: update title, description, due_date
    if (isset($_POST['edit_assignment'])) {
        $assignmentId = $_POST['assignment_id'];
        $newTitle = trim($_POST['new_title']);
        $newDescription = trim($_POST['new_description']);
        $newDueDate = trim($_POST['new_due_date']);
        if ($mode === 'secure') {
            $fields = [];
            $types = "";
            $params = [];
            if (!empty($newTitle)) {
                $fields[] = "title = ?";
                $types .= "s";
                $params[] = $newTitle;
            }
            if (!empty($newDescription)) {
                $fields[] = "description = ?";
                $types .= "s";
                $params[] = $newDescription;
            }
            if (!empty($newDueDate)) {
                $fields[] = "due_date = ?";
                $types .= "s";
                $params[] = $newDueDate;
            }
            if (!empty($fields)) {
                $sql = "UPDATE assignments SET " . implode(", ", $fields) . " WHERE assignment_id = ?";
                $types .= "i";
                $params[] = $assignmentId;
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $set = [];
            if (!empty($newTitle)) {
                $set[] = "title = '" . $conn->real_escape_string($newTitle) . "'";
            }
            if (!empty($newDescription)) {
                $set[] = "description = '" . $conn->real_escape_string($newDescription) . "'";
            }
            if (!empty($newDueDate)) {
                $set[] = "due_date = '" . $conn->real_escape_string($newDueDate) . "'";
            }
            if (!empty($set)) {
                $sql = "UPDATE assignments SET " . implode(", ", $set) . " WHERE assignment_id = $assignmentId";
                $conn->query($sql);
            }
        }
    }

    // ---- GRADES SECTION ----
    // Update/Insert grade for a specific student and assignment.
    if (isset($_POST['update_grade'])) {
        $studentId = $_POST['student_id'];
        $assignmentId = $_POST['assignment_id'];
        $grade     = $_POST['grade'];
        $check_sql = "SELECT grade_id FROM student_grades WHERE user_id = $studentId AND assignment_id = $assignmentId";
        $check_result = $conn->query($check_sql);
        if ($check_result && $check_result->num_rows > 0) {
            if ($mode === 'secure') {
                $stmt = $conn->prepare("UPDATE student_grades SET grade = ? WHERE user_id = ? AND assignment_id = ?");
                $stmt->bind_param("dii", $grade, $studentId, $assignmentId);
                $stmt->execute();
                $stmt->close();
            } else {
                $sql = "UPDATE student_grades SET grade = '$grade' WHERE user_id = $studentId AND assignment_id = $assignmentId";
                $conn->query($sql);
            }
        } else {
            if ($mode === 'secure') {
                $stmt = $conn->prepare("INSERT INTO student_grades (user_id, assignment_id, grade) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $studentId, $assignmentId, $grade);
                $stmt->execute();
                $stmt->close();
            } else {
                $sql = "INSERT INTO student_grades (user_id, assignment_id, grade) VALUES ($studentId, $assignmentId, '$grade')";
                $conn->query($sql);
            }
        }
    }
    // Edit grade row: update grade based on grade_id
    if (isset($_POST['edit_grade'])) {
        $gradeId = $_POST['grade_id'];
        $newGrade = $_POST['new_grade'];
        if ($mode === 'secure') {
            $stmt = $conn->prepare("UPDATE student_grades SET grade = ? WHERE grade_id = ?");
            $stmt->bind_param("di", $newGrade, $gradeId);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "UPDATE student_grades SET grade = '$newGrade' WHERE grade_id = $gradeId";
            $conn->query($sql);
        }
    }
    
    // Handle Student Password Updates (separate from edit_student)
    if (isset($_POST['update_student_password'])) {
        $studentId = $_POST['student_id'];
        $newPassword = $_POST['new_password'];
        if ($mode === 'secure') {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ? AND role = 'student'");
            $stmt->bind_param("si", $hashed, $studentId);
            $stmt->execute();
            $stmt->close();
        } else {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE users SET password = '$hashed' WHERE user_id = $studentId AND role = 'student'";
            $conn->query($sql);
        }
    }
    // Update teacher credentials
    if (isset($_POST['update_teacher_credentials'])) {
        $newUsername = $_POST['new_teacher_username'];
        $newPassword = $_POST['new_teacher_password'];
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        if ($mode === 'secure') {
            $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE role = 'teacher' AND username = ?");
            $stmt->bind_param("sss", $newUsername, $hashed, $_SESSION['username']);
            $stmt->execute();
            $stmt->close();
        } else {
            $sql = "UPDATE users SET username = '$newUsername', password = '$hashed' WHERE role = 'teacher' AND username = '" . $conn->real_escape_string($_SESSION['username']) . "'";
            $conn->query($sql);
        }
        $_SESSION['username'] = $newUsername;
    }
}

// Retrieve data for display
// 1. Students (users with role 'student')
$students = [];
$sql_students = "SELECT user_id, username, password FROM users WHERE role = 'student'";
$result = $conn->query($sql_students);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// 2. Assignments
$assignments = [];
$sql_assignments = "SELECT assignment_id, title, description, due_date FROM assignments";
$result = $conn->query($sql_assignments);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assignments[] = $row;
    }
}

// 3. Grades
$grades = [];
$sql_grades = "SELECT sg.grade_id, sg.user_id, u.username AS student_name, sg.assignment_id, a.title AS assignment_name, sg.grade
                 FROM student_grades sg
                 JOIN users u ON sg.user_id = u.user_id
                 JOIN assignments a ON sg.assignment_id = a.assignment_id
                 ORDER BY sg.assignment_id ASC, sg.user_id ASC";
$result = $conn->query($sql_grades);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard (<?php echo ucfirst($mode); ?> Mode)</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="dashboard-container">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst($mode); ?> Mode)</h1>
    <a href="logout.php" class="logout">Logout</a>
    
    <!-- Section: Students -->
    <h2>Students</h2>
    <div class="mini-form">
        <form method="POST" action="">
            <input type="text" name="student_name" placeholder="Student Username" required>
            <input type="password" name="student_password" placeholder="Student Password" required>
            <button type="submit" name="add_student">Add Student</button>
        </form>
    </div>
    <table border="1">
        <thead>
            <tr>
                <th>ID 🔑</th>
                <th>Username</th>
                <th>Password</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?php echo $student['user_id']; ?></td>
                <td><?php echo htmlspecialchars($student['username']); ?></td>
                <td><?php echo htmlspecialchars($student['password']); ?></td>
                <td>
                    <!-- Combined Remove and Edit Forms inline for Students -->
                    <div class="mini-form">
                        <form method="POST" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="student_id" value="<?php echo $student['user_id']; ?>">
                            <button type="submit" name="remove_student" class="remove-button">Remove</button>
                        </form>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="student_id" value="<?php echo $student['user_id']; ?>">
                            <input type="text" name="new_username" placeholder="New Username" required style="width:120px; margin-right:5px;">
                            <input type="password" name="new_password" placeholder="New Password" required style="width:120px; margin-right:5px;">
                            <button type="submit" name="edit_student">Update</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Section: Assignments -->
    <h2>Assignments</h2>
    <div class="mini-form">
        <form method="POST" action="">
            <input type="text" name="title" placeholder="Title" required>
            <input type="text" name="description" placeholder="Description" required>
            <input type="date" name="due_date" required>
            <button type="submit" name="add_assignment">Add Assignment</button>
        </form>
    </div>
    <table border="1">
        <thead>
            <tr>
                <th>ID 🔑</th>
                <th>Title</th>
                <th>Description</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($assignments as $assignment): ?>
            <tr>
                <td><?php echo $assignment['assignment_id']; ?></td>
                <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                <td><?php echo htmlspecialchars($assignment['description']); ?></td>
                <td><?php echo htmlspecialchars($assignment['due_date']); ?></td>
                <td>
                    <!-- Combined Remove and Edit Forms inline -->
                    <div class="mini-form">
                        <form method="POST" style="display:inline-block; margin-right:10px;">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                            <button type="submit" name="remove_assignment" class="remove-button">Remove</button>
                        </form>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">
                            <input type="text" name="new_title" placeholder="New Title" required style="width:120px; margin-right:5px;">
                            <input type="date" name="new_due_date" required style="width:120px; margin-right:5px;">
                            <input type="text" name="new_description" placeholder="New Description" required style="width:200px; margin-right:5px;">
                            <button type="submit" name="edit_assignment">Update</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Section: Grades -->
    <h2>Grades</h2>
    <div class="mini-form">
        <form method="POST" action="">
            <input type="number" name="student_id" placeholder="Student ID" required>
            <input type="number" name="assignment_id" placeholder="Assignment ID" required>
            <input type="text" name="grade" placeholder="Grade" required>
            <button type="submit" name="update_grade">Update/Insert Grade</button>
        </form>
    </div>
    <table border="1">
        <thead>
            <tr>
                <th>Grade ID 🔑</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Assignment ID</th>
                <th>Assignment Name</th>
                <th>Grade</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($grades as $g): ?>
            <tr>
                <td><?php echo $g['grade_id']; ?></td>
                <td><?php echo $g['user_id']; ?></td>
                <td><?php echo htmlspecialchars($g['student_name']); ?></td>
                <td><?php echo $g['assignment_id']; ?></td>
                <td><?php echo htmlspecialchars($g['assignment_name']); ?></td>
                <td><?php echo $g['grade']; ?></td>
                <td>
                    <div class="mini-form">
                        <form method="POST" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                            <input type="hidden" name="grade_id" value="<?php echo $g['grade_id']; ?>">
                            <input type="text" name="new_grade" placeholder="New Grade" required style="width:100px;">
                            <button type="submit" name="edit_grade">Update</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Section: Assignment Statistics -->
    <h2 style="margin-top: 80px;">Assignment Statistics</h2>
    <?php
    $assignment_stats = [];
    foreach ($assignments as $assignment) {
        $aid   = $assignment['assignment_id'];
        $title = $assignment['title'];
        $grades_arr = [];

        // Gather numeric grades for this assignment
        foreach ($grades as $g) {
            if ($g['assignment_id'] == $aid && is_numeric($g['grade'])) {
                $grades_arr[] = (float)$g['grade'];
            }
        }

        if (count($grades_arr) > 0) {
            // Basic stats
            $count    = count($grades_arr);
            $avg      = array_sum($grades_arr) / $count;
            $variance = array_sum(array_map(fn($val) => pow($val - $avg, 2), $grades_arr)) / $count;
            $std_dev  = sqrt($variance);
            sort($grades_arr);
            $median   = ($count % 2 === 0)
                ? ($grades_arr[$count/2 - 1] + $grades_arr[$count/2]) / 2
                : $grades_arr[floor($count/2)];
            $min_val  = min($grades_arr);
            $max_val  = max($grades_arr);

            // For mode, convert numeric to string so array_count_values won't complain
            $roundedGradesForMode = array_map(
                fn($val) => (string)round($val, 2),
                $grades_arr
            );
            $mode_val = "-";
            if (count($roundedGradesForMode) > 0) {
                $mode_counts = array_count_values($roundedGradesForMode);
                arsort($mode_counts);
                $mode_keys = array_keys($mode_counts);
                $mode_val  = $mode_keys[0] ?? "-";  // might be e.g. "46.50"
            }

            $assignment_stats[] = [
                'title'     => $title,
                'mean'      => number_format($avg, 2),
                'std_dev'   => number_format($std_dev, 2),
                'variance'  => number_format($variance, 2),
                'median'    => number_format($median, 2),
                'mode'      => (is_numeric($mode_val)) ? number_format((float)$mode_val, 2) : "-",
                'min'       => number_format($min_val, 2),
                'max'       => number_format($max_val, 2),
            ];
        }
    }
    ?>

    <?php if (count($assignment_stats)): ?>
    <table border="1">
        <thead>
            <tr>
                <th>Assignment</th>
                <th>Mean</th>
                <th>Std Dev</th>
                <th>Variance</th>
                <th>Median</th>
                <th>Mode</th>
                <th>Min</th>
                <th>Max</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($assignment_stats as $stat): ?>
            <tr>
                <td><?php echo htmlspecialchars($stat['title']); ?></td>
                <td><?php echo $stat['mean']; ?></td>
                <td><?php echo $stat['std_dev']; ?></td>
                <td><?php echo $stat['variance']; ?></td>
                <td><?php echo $stat['median']; ?></td>
                <td><?php echo $stat['mode']; ?></td>
                <td><?php echo $stat['min']; ?></td>
                <td><?php echo $stat['max']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Section: Overall Grade Statistics -->
    <h2 style="margin-top: 50px;">Overall Grade Statistics</h2>
    <?php
    // Gather numeric grades from $grades
    $all_grades = [];
    foreach ($grades as $g) {
        if (is_numeric($g['grade'])) {
            $all_grades[] = (float)$g['grade'];
        }
    }

    if (count($all_grades) > 0) {
        $count    = count($all_grades);
        $avg      = array_sum($all_grades) / $count;
        $variance = array_sum(array_map(fn($val) => pow($val - $avg, 2), $all_grades)) / $count;
        $std_dev  = sqrt($variance);
        sort($all_grades);
        $median   = ($count % 2 === 0)
            ? ($all_grades[$count/2 - 1] + $all_grades[$count/2]) / 2
            : $all_grades[floor($count/2)];
        $min_val  = min($all_grades);
        $max_val  = max($all_grades);

        // Convert numeric to string for mode calculation
        $roundedAllGradesForMode = array_map(
            fn($val) => (string)round($val, 2),
            $all_grades
        );
        $mode_val = "-";
        if (count($roundedAllGradesForMode) > 0) {
            $mode_counts = array_count_values($roundedAllGradesForMode);
            arsort($mode_counts);
            $mode_keys = array_keys($mode_counts);
            $mode_val  = $mode_keys[0] ?? "-";
        }
    ?>
    <table border="1">
        <thead>
            <tr>
                <th>Mean</th>
                <th>Std Dev</th>
                <th>Variance</th>
                <th>Median</th>
                <th>Mode</th>
                <th>Min</th>
                <th>Max</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo number_format($avg, 2); ?></td>
                <td><?php echo number_format($std_dev, 2); ?></td>
                <td><?php echo number_format($variance, 2); ?></td>
                <td><?php echo number_format($median, 2); ?></td>
                <td><?php echo (is_numeric($mode_val)) ? number_format((float)$mode_val, 2) : "-"; ?></td>
                <td><?php echo number_format($min_val, 2); ?></td>
                <td><?php echo number_format($max_val, 2); ?></td>
            </tr>
        </tbody>
    </table>
    <?php } ?>

    <!-- Section: Update Teacher Credentials -->
    <h2 style="margin-top: 80px;">Update Teacher Username/Password</h2>
    <div class="mini-form">
        <form method="POST" action="">
            <input type="text" name="new_teacher_username" placeholder="New Username" required>
            <input type="password" name="new_teacher_password" placeholder="New Password" required>
            <button type="submit" name="update_teacher_credentials">Update</button>
        </form>
    </div>

</div>
</body>
</html>
<?php
$conn->close();
?>