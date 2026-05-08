<?php
// ============================================
// SUPERVISOR DASHBOARD
// Shows supervisor overview and assigned hall info
// ============================================

session_start();
if (!isset($_SESSION['supervisor_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$supervisor_id = $_SESSION['supervisor_id'];

// Get supervisor details including assigned hall
$supervisor = $conn->query("
    SELECT s.*, eh.hall_name, eh.total_seats 
    FROM supervisors s 
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id 
    WHERE s.id = $supervisor_id
")->fetch_assoc();

// Get counts for dashboard
$total_students = 0;
$present_count = 0;
$absent_count = 0;
$malpractice_count = 0;

if ($supervisor['assigned_hall']) {
    $hall_id = $supervisor['assigned_hall'];
    
    // Students in assigned hall (from latest allocation)
    $total_students = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM seat_allocation WHERE hall_id = $hall_id")->fetch_assoc()['count'];
    
    // Attendance counts
    $present_count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE hall_id = $hall_id AND status = 'Present'")->fetch_assoc()['count'];
    $absent_count = $conn->query("SELECT COUNT(*) as count FROM attendance WHERE hall_id = $hall_id AND status = 'Absent'")->fetch_assoc()['count'];
    
    // Malpractice reports
    $malpractice_count = $conn->query("SELECT COUNT(*) as count FROM malpractice WHERE reported_by = $supervisor_id")->fetch_assoc()['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Supervisor Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="myhall.php">My Hall</a></li>
                <li><a href="attendancemark.php">Mark Attendance</a></li>
                <li><a href="malpracticereport.php">Malpractice Report</a></li>
                <li><a href="viewsummary.php">View Summary</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Supervisor Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($supervisor['name']); ?>!</p>
            </div>

            <!-- Dashboard Cards -->
            <?php if ($supervisor['assigned_hall']): ?>
            <div class="dashboard-cards">
                <div class="card card-blue">
                    <h3>Students in Hall</h3>
                    <div class="card-number"><?php echo $total_students; ?></div>
                </div>
                <div class="card card-green">
                    <h3>Present</h3>
                    <div class="card-number"><?php echo $present_count; ?></div>
                </div>
                <div class="card card-orange">
                    <h3>Absent</h3>
                    <div class="card-number"><?php echo $absent_count; ?></div>
                </div>
                <div class="card card-red">
                    <h3>Malpractice Reports</h3>
                    <div class="card-number"><?php echo $malpractice_count; ?></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Links -->
            <div class="content-box">
                <h2>Quick Actions</h2>
                <a href="myhall.php" class="btn btn-info btn-small">View My Hall</a>
                <a href="attendancemark.php" class="btn btn-success btn-small" style="margin-left:10px;">Mark Attendance</a>
                <a href="malpracticereport.php" class="btn btn-danger btn-small" style="margin-left:10px;">Report Malpractice</a>
            </div>
        </div>
    </div>
</body>
</html>
