<?php
// ============================================
// MY EXAMS PAGE
// Student can view all exams from the database
// ============================================

session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$student_id = (int)$_SESSION['student_id'];
$student = $conn->query("SELECT department FROM students WHERE id = $student_id")->fetch_assoc();
$student_department = $conn->real_escape_string($student['department'] ?? '');

// Fetch only exams for this student's department or common exams
$exams = $conn->query("SELECT * FROM exams WHERE department = 'All' OR department = '$student_department' ORDER BY exam_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exams - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime('../css/style.css'); ?>">
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="my_exams.php" class="active">My Exams</a></li>
                <li><a href="hall_ticket.php">Hall Ticket</a></li>
                <li><a href="seat_allocation.php">Seat Allocation</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header exams-header">
                <h1>My Exams</h1>
                <a href="my_exams.php" class="exams-header-link">View your department exams</a>
            </div>

            <div class="content-box exams-box">
                <h2>Exam Schedule</h2>

                <?php if ($exams->num_rows > 0): ?>
                <div class="exams-table-wrap">
                <table class="data-table exams-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Subject Code</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Session</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($row = $exams->fetch_assoc()): 
                            // Determine if exam is upcoming or past
                            $today = date('Y-m-d');
                            $status = ($row['exam_date'] >= $today) ? 'Upcoming' : 'Completed';
                            $status_class = ($status == 'Upcoming') ? 'status-upcoming' : 'status-completed';
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['department'] ?? 'All'); ?></td>
                            <td><?php echo $row['exam_date']; ?></td>
                            <td><?php echo $row['exam_time']; ?></td>
                            <td><?php echo htmlspecialchars($row['session']); ?></td>
                            <td><span class="exam-status-pill <?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-info">No exams scheduled yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
