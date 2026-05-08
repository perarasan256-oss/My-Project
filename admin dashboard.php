<?php
// ============================================
// ADMIN DASHBOARD
// Shows summary cards with counts
// ============================================

// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

// Include database connection
include('../db/config.php');

// Get counts for dashboard cards
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$exam_count = $conn->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'];
$hall_count = $conn->query("SELECT COUNT(*) as count FROM exam_halls")->fetch_assoc()['count'];
$allocation_count = $conn->query("SELECT COUNT(*) as count FROM seat_allocation")->fetch_assoc()['count'];
$supervisor_count = $conn->query("SELECT COUNT(*) as count FROM supervisors")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="add_admin.php">Add Admin</a></li>
                <li><a href="manage_students.php">Students</a></li>
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
                <h1>Admin Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars(!empty($_SESSION['admin_full_name']) ? $_SESSION['admin_full_name'] : $_SESSION['admin_username']); ?>!</p>
            </div>

            <!-- Dashboard Summary Cards -->
            <div class="dashboard-cards">
                <div class="card card-blue">
                    <h3>Total Students</h3>
                    <div class="card-number"><?php echo $student_count; ?></div>
                </div>
                <div class="card card-green">
                    <h3>Total Exams</h3>
                    <div class="card-number"><?php echo $exam_count; ?></div>
                </div>
                <div class="card card-orange">
                    <h3>Exam Halls</h3>
                    <div class="card-number"><?php echo $hall_count; ?></div>
                </div>
                <div class="card card-red">
                    <h3>Seat Allocations</h3>
                    <div class="card-number"><?php echo $allocation_count; ?></div>
                </div>
                <div class="card card-blue">
                    <h3>Supervisors</h3>
                    <div class="card-number"><?php echo $supervisor_count; ?></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="content-box">
                <h2>Quick Actions</h2>
                <p style="margin-bottom:15px;">Use the sidebar to navigate or click below:</p>
                <a href="manage_students.php" class="btn btn-success btn-small">Students</a>
                <a href="manage_exams.php" class="btn btn-info btn-small">Exams</a>
                <a href="manage_halls.php" class="btn btn-warning btn-small">Halls</a>
                <a href="allocate_seat.php" class="btn btn-primary btn-small" style="width:auto;">Allocate Seats</a>
            </div>
        </div>

    </div>
</body>
</html>
