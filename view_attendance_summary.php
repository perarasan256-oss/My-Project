<?php
// ============================================
// ADMIN - VIEW ATTENDANCE SUMMARY
// Shows all supervisors' attendance marking summary
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

// load supervisors for dropdown
$supervisors = $conn->query("SELECT s.id, s.name, eh.hall_name FROM supervisors s LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id");

// determine if a specific supervisor was selected
$selected_supervisor = isset($_GET['supervisor_id']) ? (int)$_GET['supervisor_id'] : null;

// build attendance query with optional filter
$where = "WHERE s.assigned_hall IS NOT NULL";
if ($selected_supervisor) {
    $where .= " AND s.id = $selected_supervisor";
}

$attendance_summary = $conn->query("
    SELECT s.id as supervisor_id, s.name as supervisor_name, eh.hall_name,
           st.name as student_name, st.register_no,
           e.exam_name, e.subject, e.exam_date,
           sa.seat_number, a.status, a.marked_by
    FROM supervisors s
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id
    LEFT JOIN seat_allocation sa ON sa.hall_id = s.assigned_hall
    LEFT JOIN students st ON sa.student_id = st.id
    LEFT JOIN exams e ON sa.exam_id = e.id
    LEFT JOIN attendance a ON a.student_id = st.id AND a.exam_id = e.id AND a.hall_id = s.assigned_hall
    $where
    ORDER BY s.name, e.exam_date DESC, sa.seat_number
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Summary - Smart Exam</title>
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
                <li><a href="view_attendance_summary.php" class="active">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Attendance Summary (By Supervisor)</h1>
                <p>Select a supervisor to view their hall's student attendance</p>
            </div>

            <!-- Supervisor Selection -->
            <div class="content-box mb-20">
                <h2>Select Supervisor</h2>
                <form method="get" action="">
                    <div class="form-group">
                        <label for="supervisor_id">Supervisor</label>
                        <select name="supervisor_id" id="supervisor_id" onchange="this.form.submit()" required>
                            <option value="">Choose Supervisor</option>
                            <?php if ($supervisors && $supervisors->num_rows > 0): 
                                while ($supervisor = $supervisors->fetch_assoc()): ?>
                                <option value="<?php echo $supervisor['id']; ?>" 
                                        <?php echo ($selected_supervisor == $supervisor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supervisor['name']); ?> 
                                    (<?php echo htmlspecialchars($supervisor['hall_name']); ?>)
                                </option>
                            <?php endwhile; 
                            endif; ?>
                        </select>
                    </div>
                </form>
            </div>

            <?php if ($selected_supervisor): ?>
            <div class="content-box">
                <?php if ($attendance_summary && $attendance_summary->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Supervisor Name</th>
                                    <th>Hall</th>
                                    <th>Student Reg No</th>
                                    <th>Student Name</th>
                                    <th>Seat No</th>
                                    <th>Exam Name</th>
                                    <th>Subject</th>
                                    <th>Exam Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $attendance_summary->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                        <td><?php echo $row['hall_name'] ? htmlspecialchars($row['hall_name']) : '<em>Not Assigned</em>'; ?></td>
                                        <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['seat_number']); ?></td>
                                        <td><?php echo $row['exam_name'] ? htmlspecialchars($row['exam_name']) : '-'; ?></td>
                                        <td><?php echo $row['subject'] ? htmlspecialchars($row['subject']) : '-'; ?></td>
                                        <td><?php echo $row['exam_date'] ? date('d-m-Y', strtotime($row['exam_date'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($row['status'] == 'Present'): ?>
                                                <span class="badge badge-success">Present</span>
                                            <?php elseif ($row['status'] == 'Absent'): ?>
                                                <span class="badge badge-danger">Absent</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Not Marked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No attendance records found for this supervisor.</div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
