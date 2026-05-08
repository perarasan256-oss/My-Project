<?php
// ============================================
// STUDENT PROFILE PAGE
// Displays logged-in student details
// ============================================

session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$student_id = (int)$_SESSION['student_id'];
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $register_no = trim($_POST['register_no'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = (int)($_POST['year'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');

    if ($register_no === '' || $name === '' || $department === '' || $year < 1 || $year > 10) {
        $message = 'Please fill in all required fields correctly.';
        $message_type = 'error';
    } else {
        $update_stmt = $conn->prepare("UPDATE students SET register_no = ?, name = ?, department = ?, year = ?, phone = ?, parent_phone = ? WHERE id = ?");
        $update_stmt->bind_param("sssissi", $register_no, $name, $department, $year, $phone, $parent_phone, $student_id);

        if ($update_stmt->execute()) {
            $message = 'Profile updated successfully.';
            $message_type = 'success';
        } else {
            $message = 'Unable to update profile right now. Please try again.';
            $message_type = 'error';
        }

        $update_stmt->close();
    }
}

$stmt = $conn->prepare("SELECT register_no, name, department, year, phone, parent_phone FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Student Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php" class="active">My Profile</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="hall_ticket.php">Hall Ticket</a></li>
                <li><a href="seat_allocation.php">Seat Allocation</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1>My Profile</h1>
                <p>View and update your personal and academic information</p>
            </div>

            <div class="content-box">
                <h2>Student Details</h2>
                <?php if ($message !== ''): ?>
                    <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <form method="POST" class="profile-edit-form">
                    <div class="form-group">
                        <label for="register_no">Register Number</label>
                        <input type="text" id="register_no" name="register_no" value="<?php echo htmlspecialchars($student['register_no']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Student Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($student['department']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" min="1" max="10" value="<?php echo htmlspecialchars((string)$student['year']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="parent_phone">Parent Phone</label>
                        <input type="text" id="parent_phone" name="parent_phone" value="<?php echo htmlspecialchars($student['parent_phone']); ?>">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-medium">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
