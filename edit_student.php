<?php
// ============================================
// EDIT STUDENT PAGE
// Admin can update student details
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include('../db/config.php');

$message = '';
$msg_type = '';

// Get student ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission (update)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $register_no = $_POST['register_no'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $year = (int)$_POST['year'];
    $password = trim($_POST['password']);

    // Update student record using prepared statement
    if ($password !== '') {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE students SET register_no=?, name=?, department=?, year=?, password=? WHERE id=?");
        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
            $msg_type = "error";
        } else {
            $stmt->bind_param("sssisi", $register_no, $name, $department, $year, $hashed_password, $id);
            if ($stmt->execute()) {
                $message = "Student updated successfully!";
                $msg_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $msg_type = "error";
            }
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("UPDATE students SET register_no=?, name=?, department=?, year=? WHERE id=?");
        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
            $msg_type = "error";
        } else {
            $stmt->bind_param("sssii", $register_no, $name, $department, $year, $id);
            if ($stmt->execute()) {
                $message = "Student updated successfully!";
                $msg_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $msg_type = "error";
            }
            $stmt->close();
        }
    }
}

// Fetch student data using prepared statement
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

// If student not found, redirect
if (!$student) {
    header("Location: manage_students.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_admin.php">Add Admin</a></li>
                <li><a href="manage_students.php" class="active">Students</a></li>
                <li><a href="manage_exams.php">Exams</a></li>
                <li><a href="manage_halls.php">Halls</a></li>
                <li><a href="manage_supervisors.php">Supervisors</a></li>
                <li><a href="allocate_seat.php">Seat Allocation</a></li>
                <li><a href="view_allocation.php">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Edit Student</h1>
                <p>Update student information</p>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="content-box">
                <h2>Edit: <?php echo htmlspecialchars($student['name']); ?></h2>
                <form method="POST" onsubmit="return validateStudentForm()">
                    <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                    
                    <div class="form-group">
                        <label for="register_no">Register Number</label>
                        <input type="text" id="register_no" name="register_no" 
                               value="<?php echo htmlspecialchars($student['register_no']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="name">Student Name</label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="department">Department</label>
                        <select id="department" name="department" required>
                            <option value="Electronics" <?php echo ($student['department'] == 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                            <option value="Mechanical" <?php echo ($student['department'] == 'Mechanical') ? 'selected' : ''; ?>>Mechanical</option>
                            <option value="Civil" <?php echo ($student['department'] == 'Civil') ? 'selected' : ''; ?>>Civil</option>
                            <option value="Electrical" <?php echo ($student['department'] == 'Electrical') ? 'selected' : ''; ?>>Electrical</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" required>
                            <option value="1" <?php echo ($student['year'] == 1) ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo ($student['year'] == 2) ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo ($student['year'] == 3) ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo ($student['year'] == 4) ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password">Password (leave blank to keep current password)</label>
                        <input type="password" id="password" name="password">
                    </div>

                    <button type="submit" class="btn btn-success">Update Student</button>
                    <a href="manage_students.php" class="btn btn-info btn-small" style="margin-left:10px;">Back to List</a>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/validation.js"></script>
</body>
</html>
