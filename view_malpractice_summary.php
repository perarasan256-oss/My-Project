<?php
// ============================================
// ADMIN - VIEW MALPRACTICE SUMMARY
// Shows all malpractice reports submitted by supervisors
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

// Get all malpractice reports with supervisor and student details
$malpractice_reports = $conn->query("
    SELECT m.id, m.student_id, st.name as student_name, st.register_no,
           s.name as supervisor_name, eh.hall_name,
           e.exam_name, e.subject, e.exam_date,
           m.description, m.report_date
    FROM malpractice m
    JOIN students st ON m.student_id = st.id
    JOIN supervisors s ON m.reported_by = s.id
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id
    JOIN exams e ON m.exam_id = e.id
    ORDER BY m.report_date DESC
");

if (!$malpractice_reports) {
    die("Database query failed: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malpractice Reports - Smart Exam</title>
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
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_admin.php">Add Admin</a></li>
                <li><a href="manage_students.php">Students</a></li>
                <li><a href="manage_exams.php">Exams</a></li>
                <li><a href="manage_halls.php">Halls</a></li>
                <li><a href="manage_supervisors.php">Supervisors</a></li>
                <li><a href="allocate_seat.php">Seat Allocation</a></li>
                <li><a href="view_allocation.php">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php" class="active">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Malpractice Reports (All)</h1>
                <p>View all malpractice incidents reported by supervisors</p>
            </div>

            <div class="content-box">
                <?php if ($malpractice_reports && $malpractice_reports->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Report Date</th>
                                    <th>Supervisor</th>
                                    <th>Hall</th>
                                    <th>Student Register No</th>
                                    <th>Student Name</th>
                                    <th>Exam</th>
                                    <th>Subject</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $malpractice_reports->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y H:i', strtotime($row['report_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td title="<?php echo htmlspecialchars($row['description']); ?>">
                                            <?php echo htmlspecialchars(substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : '')); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No malpractice reports found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
