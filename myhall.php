<?php
// ============================================
// MY HALL PAGE
// Shows students allocated to supervisor's hall
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
    SELECT s.*, eh.hall_name, eh.hall_no, eh.total_seats 
    FROM supervisors s 
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id 
    WHERE s.id = $supervisor_id
")->fetch_assoc();

$message = "";
$allocations = null;

if (!$supervisor['assigned_hall']) {
    $message = "You have not been assigned to any hall yet. Please contact the admin.";
} else {
    $hall_id = $supervisor['assigned_hall'];
    
    // Get seat allocations for this hall
    $allocations = $conn->query("
        SELECT sa.seat_number, s.name, s.register_no, s.department, s.year, 
               e.exam_name, e.subject, e.exam_date
        FROM seat_allocation sa
        JOIN students s ON sa.student_id = s.id
        JOIN exams e ON sa.exam_id = e.id
        WHERE sa.hall_id = $hall_id
        ORDER BY sa.seat_number
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Hall - Smart Exam</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="myhall.php" class="active">My Hall</a></li>
                <li><a href="attendancemark.php">Mark Attendance</a></li>
                <li><a href="malpracticereport.php">Malpractice Report</a></li>
                <li><a href="viewsummary.php">View Summary</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>My Hall</h1>
                <p>Students allocated to your hall</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-warning"><?php echo $message; ?></div>
            <?php else: ?>
                <div class="content-box">
                    <h2><?php echo htmlspecialchars($supervisor['hall_name']); ?> - Student Allocation</h2>
                    <p>
                        Hall Name: <?php echo htmlspecialchars($supervisor['hall_name']); ?> |
                        Hall No: <?php echo htmlspecialchars($supervisor['hall_no']); ?> |
                        Total Seats: <?php echo $supervisor['total_seats']; ?>
                    </p>
                    
                    <?php if ($allocations && $allocations->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Seat No</th>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Department</th>
                                        <th>Year</th>
                                        <th>Exam</th>
                                        <th>Subject</th>
                                        <th>Exam Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $allocations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['seat_number']; ?></td>
                                            <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                                            <td><?php echo $row['year']; ?></td>
                                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['exam_date'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No students allocated to your hall yet.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
