<?php
// ============================================
// STUDENT DASHBOARD
// Shows welcome message and profile details
// ============================================

session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

// Fetch student details
$student_id = $_SESSION['student_id'];
$student = $conn->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();
$student_department = $conn->real_escape_string($student['department'] ?? '');

// Count upcoming exams
$exam_count = $conn->query("SELECT COUNT(*) as count FROM exams WHERE (department = 'All' OR department = '$student_department') AND exam_date >= CURDATE()")->fetch_assoc()['count'];

// Count completed exams
$completed_exam_count = $conn->query("SELECT COUNT(*) as count FROM exams WHERE (department = 'All' OR department = '$student_department') AND exam_date < CURDATE()")->fetch_assoc()['count'];

// Count seat allocations for this student
$alloc_count = $conn->query("SELECT COUNT(*) as count FROM seat_allocation WHERE student_id = $student_id")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Student Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="hall_ticket.php">Hall Ticket</a></li>
                <li><a href="seat_allocation.php">Seat Allocation</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Student Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($student['name']); ?>!</p>
            </div>

            <!-- Summary Cards -->
            <div class="dashboard-cards">
                <div class="card card-blue">
                    <h3>Upcoming Exams</h3>
                    <div class="card-number"><?php echo $exam_count; ?></div>
                </div>
                <div class="card card-orange">
                    <h3>Completed Exams</h3>
                    <div class="card-number"><?php echo $completed_exam_count; ?></div>
                </div>
                <div class="card card-green">
                    <h3>Seats Allocated</h3>
                    <div class="card-number"><?php echo $alloc_count; ?></div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="content-box">
                <h2>Quick Links</h2>
                <a href="my_exams.php" class="btn btn-info btn-small">View My Exams</a>
                <a href="hall_ticket.php" class="btn btn-success btn-small" style="margin-left:10px;">View Hall Ticket</a>
                <a href="seat_allocation.php" class="btn btn-warning btn-small" style="margin-left:10px;">View Seat Allocation</a>
            </div>
        </div>
    </div>
</body>
</html>
