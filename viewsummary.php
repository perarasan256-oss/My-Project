<?php
// ============================================
// VIEW SUMMARY PAGE
// Shows supervisor's attendance marking and malpractice reports summary
// ============================================

session_start();
if (!isset($_SESSION['supervisor_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$supervisor_id = $_SESSION['supervisor_id'];

$supervisor = $conn->query("
    SELECT s.*, eh.hall_name 
    FROM supervisors s 
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id 
    WHERE s.id = $supervisor_id
")->fetch_assoc();

$summary_type = $_GET['type'] ?? 'attendance';
if (!in_array($summary_type, ['attendance', 'malpractice'], true)) {
    $summary_type = 'attendance';
}

$attendance_summary = null;
$malpractice_reports = null;

if ($supervisor['assigned_hall']) {
    $hall_id = $supervisor['assigned_hall'];
    
    if ($summary_type === 'attendance') {
        $attendance_summary = $conn->query("
            SELECT e.id, e.exam_name, e.subject, e.exam_date,
                   COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
                   COUNT(CASE WHEN a.status = 'Absent' THEN 1 END) as absent_count,
                   COUNT(a.id) as total_marked
            FROM exams e
            LEFT JOIN attendance a ON e.id = a.exam_id AND a.hall_id = $hall_id
            WHERE e.id IN (SELECT DISTINCT exam_id FROM seat_allocation WHERE hall_id = $hall_id)
            GROUP BY e.id
            ORDER BY e.exam_date DESC
        ");
    } else {
        $malpractice_reports = $conn->query("
            SELECT m.id, m.student_id, s.name, s.register_no, e.exam_name, e.subject,
                   m.description, m.report_date
            FROM malpractice m
            JOIN students s ON m.student_id = s.id
            JOIN exams e ON m.exam_id = e.id
            WHERE m.reported_by = $supervisor_id
            ORDER BY m.report_date DESC
        ");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Summary - Smart Exam</title>
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
                <li><a href="myhall.php">My Hall</a></li>
                <li><a href="attendancemark.php">Mark Attendance</a></li>
                <li><a href="malpracticereport.php">Malpractice Report</a></li>
                <li><a href="viewsummary.php" class="active">View Summary</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>View Summary</h1>
                <p>Select and view your attendance or malpractice summary</p>
            </div>

            <div class="content-box">
                <h2>Summary Filter</h2>
                <form method="get" class="summary-filter-form">
                    <div class="form-group">
                        <label for="type">Choose Summary Type</label>
                        <select name="type" id="type">
                            <option value="attendance" <?php echo $summary_type === 'attendance' ? 'selected' : ''; ?>>Attendance Summary</option>
                            <option value="malpractice" <?php echo $summary_type === 'malpractice' ? 'selected' : ''; ?>>Malpractice Summary</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-info">View Summary</button>
                </form>
            </div>

            <?php if (!$supervisor['assigned_hall']): ?>
                <div class="alert alert-warning">You have not been assigned to any hall.</div>
            <?php elseif ($summary_type === 'attendance'): ?>
                <div class="content-box">
                    <h2>Attendance Marking Summary</h2>
                    <?php if ($attendance_summary && $attendance_summary->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Exam Name</th>
                                        <th>Subject</th>
                                        <th>Exam Date</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Total Marked</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $attendance_summary->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                            <td><?php echo date('d-m-Y', strtotime($row['exam_date'])); ?></td>
                                            <td><span class="badge badge-present"><?php echo $row['present_count']; ?></span></td>
                                            <td><span class="badge badge-absent"><?php echo $row['absent_count']; ?></span></td>
                                            <td><strong><?php echo $row['total_marked']; ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No attendance records found.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="content-box">
                    <h2>Malpractice Reports Summary</h2>
                    <?php if ($malpractice_reports && $malpractice_reports->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Register No</th>
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
                                            <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : '')); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No malpractice reports found.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
