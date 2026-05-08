<?php
// ============================================
// VIEW SEAT ALLOCATION PAGE
// Admin can view all seat allocations by exam
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

// Get selected exam (if any)
$selected_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

// Fetch exams for filter dropdown
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_date ASC");

// Fetch allocations
$allocations = null;
if ($selected_exam > 0) {
    $query = "
        SELECT sa.seat_number, s.register_no, s.name, s.department, s.year, 
               eh.hall_name, eh.hall_no, e.exam_name, e.subject, e.subject_code, e.exam_date, e.exam_time, e.session
        FROM seat_allocation sa
        JOIN students s ON sa.student_id = s.id
        JOIN exam_halls eh ON sa.hall_id = eh.id
        JOIN exams e ON sa.exam_id = e.id
        WHERE sa.exam_id = ?
        ORDER BY eh.hall_name ASC, sa.seat_number ASC
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    $stmt->bind_param("i", $selected_exam);
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    $allocations = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Allocation - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
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
                <li><a href="view_allocation.php" class="active">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1>View Seat Allocation</h1>
                <p>View allocated seats by exam</p>
            </div>

            <!-- Exam Filter -->
            <div class="content-box">
                <h2>Select Exam</h2>
                <form method="GET">
                    <div class="form-group">
                        <select name="exam_id" onchange="this.form.submit()">
                            <option value="">-- Choose Exam --</option>
                            <?php while ($exam = $exams->fetch_assoc()): ?>
                                <option value="<?php echo $exam['id']; ?>" 
                                    <?php echo ($selected_exam == $exam['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($exam['exam_name'] . ' - ' . $exam['subject'] . ' (' . $exam['subject_code'] . ') - ' . $exam['exam_date'] . ' ' . $exam['exam_time'] . ' (' . $exam['session'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Allocation Table -->
            <?php if ($allocations && $allocations->num_rows > 0): ?>
            <div class="content-box">
                <h2>Allocation Results (<?php echo $allocations->num_rows; ?> students)</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Register No</th>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Hall Name</th>
                            <th>Hall Number</th>
                            <th>Seat No</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; while ($row = $allocations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['hall_no']); ?></td>
                            <td><strong><?php echo $row['seat_number']; ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php elseif ($selected_exam > 0): ?>
                <div class="content-box">
                    <p style="color:#e53e3e;">No allocations found for this exam. <a href="allocate_seat.php">Run allocation now</a>.</p>
                </div>
            <?php else: ?>
                <div class="content-box">
                    <p style="color:#718096;">Select an exam from the dropdown above to view seat allocations.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="../js/validation.js"></script>
</body>
</html>
