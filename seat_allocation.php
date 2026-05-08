<?php
// ============================================
// STUDENT SEAT ALLOCATION PAGE
// Shows the student their allocated seats for all exams
// ============================================

session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$student_id = $_SESSION['student_id'];

// Fetch all seat allocations for this student
$allocations = $conn->query("
    SELECT sa.seat_number, eh.hall_name, eh.hall_no, e.exam_name, e.subject, e.subject_code, e.exam_date, e.exam_time, e.session
    FROM seat_allocation sa
    JOIN exam_halls eh ON sa.hall_id = eh.id
    JOIN exams e ON sa.exam_id = e.id
    WHERE sa.student_id = $student_id
    ORDER BY e.exam_date ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Allocation - Smart Exam</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="hall_ticket.php">Hall Ticket</a></li>
                <li><a href="seat_allocation.php" class="active">Seat Allocation</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>My Seat Allocation</h1>
                <p>View your assigned exam hall and seat number</p>
            </div>

            <div class="content-box">
                <h2>Allocated Seats</h2>

                <?php if ($allocations->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Exam Name</th>
                            <th>Subject</th>
                            <th>Subject Code</th>
                            <th>Exam Date</th>
                            <th>Time</th>
                            <th>Session</th>
                            <th>Hall Name</th>
                            <th>Hall Number</th>
                            <th>Seat Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; while ($row = $allocations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo $row['exam_date']; ?></td>
                            <td><?php echo $row['exam_time']; ?></td>
                            <td><?php echo htmlspecialchars($row['session']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['hall_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['hall_no']); ?></td>
                            <td><strong style="color:#2b6cb0; font-size:18px;"><?php echo $row['seat_number']; ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info">No seats have been allocated to you yet. Please check back later.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
