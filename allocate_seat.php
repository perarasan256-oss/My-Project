<?php
// ============================================
// SEAT ALLOCATION PAGE
// Admin selects an exam and runs the auto-allocation algorithm
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$message = '';
$msg_type = '';

// Handle seat allocation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id'])) {
    $exam_id = (int)$_POST['exam_id'];
    $selected_exam = $conn->query("SELECT department, exam_date, exam_time, session FROM exams WHERE id = $exam_id")->fetch_assoc();
    $selected_department = $selected_exam['department'] ?? 'All';
    $selected_exam_date = $selected_exam['exam_date'] ?? '';
    $selected_exam_time = $selected_exam['exam_time'] ?? '';
    $selected_session = $selected_exam['session'] ?? '';

    $selected_exam_date_normalized = date('Y-m-d', strtotime((string)$selected_exam_date));
    $selected_exam_time_normalized = date('H:i:s', strtotime((string)$selected_exam_time));
    $selected_session_normalized = strtolower(trim((string)$selected_session));

    $safe_exam_date = $conn->real_escape_string($selected_exam_date_normalized);
    $safe_exam_time = $conn->real_escape_string($selected_exam_time_normalized);
    $safe_session = $conn->real_escape_string($selected_session_normalized);

    // Step 2: Get all exams in the same slot so students can be mixed across departments
    $slot_exams = [];
    $slot_exam_result = $conn->query("
        SELECT id, department
        FROM exams
        WHERE DATE(exam_date) = '$safe_exam_date'
          AND TIME(exam_time) = '$safe_exam_time'
          AND LOWER(TRIM(COALESCE(session, ''))) = '$safe_session'
        ORDER BY id ASC
    ");

    if ($slot_exam_result) {
        while ($slot_exam = $slot_exam_result->fetch_assoc()) {
            $slot_exams[] = $slot_exam;
        }
    }
    
    // Step 3: Get all exam halls
    $halls = $conn->query("SELECT id, total_seats FROM exam_halls ORDER BY hall_name ASC");
    
    // Store halls in array for easy access
    $hall_list = [];
    while ($hall = $halls->fetch_assoc()) {
        $hall_list[] = $hall;
    }

    // Check if we have halls and students
    if (count($hall_list) == 0) {
        $message = "No exam halls found! Please add halls first.";
        $msg_type = "error";
    } else {
        $students_by_department = [];
        $department_order = [];
        $slot_exam_ids = [];

        foreach ($slot_exams as $slot_exam) {
            $slot_exam_id = (int)$slot_exam['id'];
            $slot_exam_department = trim((string)($slot_exam['department'] ?? ''));
            $slot_exam_ids[] = $slot_exam_id;

            if ($slot_exam_department === '' || strcasecmp($slot_exam_department, 'All') === 0) {
                $students_result = $conn->query("SELECT id, department, register_no FROM students ORDER BY register_no ASC");
            } else {
                $safe_department = $conn->real_escape_string($slot_exam_department);
                $students_result = $conn->query("SELECT id, department, register_no FROM students WHERE department = '$safe_department' ORDER BY register_no ASC");
            }

            if (!$students_result || $students_result->num_rows === 0) {
                continue;
            }

            while ($student_row = $students_result->fetch_assoc()) {
                $dept_key = trim((string)($student_row['department'] ?? ''));
                if ($dept_key === '') {
                    $dept_key = 'UNKNOWN';
                }

                if (!isset($students_by_department[$dept_key])) {
                    $students_by_department[$dept_key] = [];
                    $department_order[] = $dept_key;
                }

                $students_by_department[$dept_key][] = [
                    'student_id' => (int)$student_row['id'],
                    'exam_id' => $slot_exam_id
                ];
            }
        }

        if (count($students_by_department) === 0) {
            $message = $selected_department === 'All'
                ? "No students found! Please add students first."
                : "No students found for the selected slot departments.";
            $msg_type = "error";
        } else {

            $student_count = 0;
            foreach ($students_by_department as $department_students) {
                $student_count += count($department_students);
            }

            $slot_usage_by_hall = [];
            $slot_usage_result = $conn->query("
                SELECT sa.hall_id, COUNT(*) AS used_count, MAX(sa.seat_number) AS max_seat_number
                FROM seat_allocation sa
                JOIN exams e ON sa.exam_id = e.id
                WHERE DATE(e.exam_date) = '$safe_exam_date'
                  AND TIME(e.exam_time) = '$safe_exam_time'
                  AND LOWER(TRIM(COALESCE(e.session, ''))) = '$safe_session'
                  AND sa.exam_id NOT IN (" . implode(',', array_map('intval', $slot_exam_ids)) . ")
                GROUP BY sa.hall_id
            ");

            if ($slot_usage_result) {
                while ($usage_row = $slot_usage_result->fetch_assoc()) {
                    $usage_hall_id = (int)$usage_row['hall_id'];
                    $slot_usage_by_hall[$usage_hall_id] = [
                        'used_count' => (int)$usage_row['used_count'],
                        'max_seat_number' => (int)($usage_row['max_seat_number'] ?? 0)
                    ];
                }
            }

            $hall_capacity_plan = [];
            $total_remaining_capacity = 0;

            foreach ($hall_list as $hall) {
                $hall_id = (int)$hall['id'];
                $hall_total = (int)$hall['total_seats'];
                $used_count = (int)($slot_usage_by_hall[$hall_id]['used_count'] ?? 0);
                $remaining_seats = max(0, $hall_total - $used_count);

                $hall_capacity_plan[] = [
                    'id' => $hall_id,
                    'remaining_seats' => $remaining_seats,
                    'starting_seat_number' => ((int)($slot_usage_by_hall[$hall_id]['max_seat_number'] ?? 0)) + 1
                ];
                $total_remaining_capacity += $remaining_seats;
            }

            if ($total_remaining_capacity < $student_count) {
                $message = "Available hall capacity is not enough for this exam slot! Students: $student_count, Total remaining seats: $total_remaining_capacity.";
                $msg_type = "error";
            } else {
                $conn->begin_transaction();

                try {
                    // Replace any previous allocation only after validation succeeds
                    $conn->query("DELETE FROM seat_allocation WHERE exam_id IN (" . implode(',', array_map('intval', $slot_exam_ids)) . ")");
                    $conn->query("DELETE FROM attendance WHERE exam_id IN (" . implode(',', array_map('intval', $slot_exam_ids)) . ")");

                    $allocated = 0;
                    $used_halls = [];
                    $remaining_students_by_department = $students_by_department;
                    $students_left = $student_count;

                    foreach ($hall_capacity_plan as $hall_plan) {
                        if ($students_left <= 0) {
                            break;
                        }

                        $hall_id = (int)$hall_plan['id'];
                        $remaining_seats = (int)$hall_plan['remaining_seats'];
                        $seat_number = (int)$hall_plan['starting_seat_number'];
                        $department_count = count($department_order);
                        $department_index = 0;

                        while ($remaining_seats > 0 && $students_left > 0) {
                            $student_id = null;
                            $student_exam_id = null;

                            for ($attempt = 0; $attempt < $department_count; $attempt++) {
                                $department_key = $department_order[$department_index];
                                if (!empty($remaining_students_by_department[$department_key])) {
                                    $student_entry = array_shift($remaining_students_by_department[$department_key]);
                                    $student_id = (int)$student_entry['student_id'];
                                    $student_exam_id = (int)$student_entry['exam_id'];
                                    $department_index = ($department_index + 1) % $department_count;
                                    break;
                                }

                                $department_index = ($department_index + 1) % $department_count;
                            }

                            if ($student_id === null || $student_exam_id === null) {
                                break;
                            }

                            $sql = "INSERT INTO seat_allocation (student_id, exam_id, hall_id, seat_number)
                                    VALUES ($student_id, $student_exam_id, $hall_id, $seat_number)";
                            if (!$conn->query($sql)) {
                                throw new Exception($conn->error);
                            }

                            $allocated++;
                            $students_left--;
                            $seat_number++;
                            $remaining_seats--;
                            $used_halls[$hall_id] = true;
                        }
                    }

                    if ($allocated !== $student_count) {
                        throw new Exception('Unable to allocate all students.');
                    }

                    // Also create attendance records (default Absent)
                    $alloc_result = $conn->query("SELECT sa.student_id, sa.exam_id, sa.hall_id FROM seat_allocation sa WHERE sa.exam_id IN (" . implode(',', array_map('intval', $slot_exam_ids)) . ")");
                    while ($a = $alloc_result->fetch_assoc()) {
                        $student_id = (int)$a['student_id'];
                        $attendance_exam_id = (int)$a['exam_id'];
                        $hall_id = (int)$a['hall_id'];
                        if (!$conn->query("INSERT INTO attendance (student_id, exam_id, hall_id, status) VALUES ($student_id, $attendance_exam_id, $hall_id, 'Absent')")) {
                            throw new Exception($conn->error);
                        }
                    }

                    // Keep seat numbers unique and continuous inside each used hall for this slot.
                    foreach (array_keys($used_halls) as $used_hall_id) {
                        $hall_slot_allocations = $conn->query("
                            SELECT sa.id
                            FROM seat_allocation sa
                            JOIN exams e ON sa.exam_id = e.id
                            WHERE sa.hall_id = $used_hall_id
                              AND DATE(e.exam_date) = '$safe_exam_date'
                              AND TIME(e.exam_time) = '$safe_exam_time'
                              AND LOWER(TRIM(COALESCE(e.session, ''))) = '$safe_session'
                            ORDER BY sa.seat_number ASC, sa.id ASC
                        ");

                        if (!$hall_slot_allocations) {
                            throw new Exception($conn->error);
                        }

                        $hall_seat_number = 1;
                        while ($hall_slot_row = $hall_slot_allocations->fetch_assoc()) {
                            $allocation_id = (int)$hall_slot_row['id'];
                            if (!$conn->query("UPDATE seat_allocation SET seat_number = $hall_seat_number WHERE id = $allocation_id")) {
                                throw new Exception($conn->error);
                            }
                            $hall_seat_number++;
                        }
                    }

                    $conn->commit();
                    $hall_count = count($used_halls);
                    $message = "Seats allocated successfully! $allocated students assigned across $hall_count hall(s) for this exam slot.";
                    $msg_type = "success";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Seat allocation failed. Please try again. Error: " . $e->getMessage();
                    $msg_type = "error";
                }
            }
        }
    }
}

// Fetch exams for dropdown
$exams = $conn->query("SELECT * FROM exams ORDER BY exam_date ASC");
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
                <li><a href="allocate_seat.php" class="active">Seat Allocation</a></li>
                <li><a href="view_allocation.php">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <h1>Seat Allocation</h1>
                <p>Automatically assign seats to students for an exam</p>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="content-box">
                <h2>Run Seat Allocation</h2>
                <p style="margin-bottom:15px; color:#718096;">
                    Select an exam below and click "Allocate Seats". The system will automatically 
                    assign matching department students across one or more halls based on available capacity for the same exam slot.
                    Any previous allocation for this exam will be replaced.
                </p>
                
                <form method="POST" onsubmit="return confirmAllocation()">
                    <div class="form-group">
                        <label for="exam_id">Select Exam</label>
                        <select id="exam_id" name="exam_id" required>
                            <option value="">-- Choose Exam --</option>
                            <?php while ($exam = $exams->fetch_assoc()): ?>
                                <option value="<?php echo $exam['id']; ?>">
                                    <?php echo htmlspecialchars($exam['exam_name'] . ' - ' . $exam['subject'] . ' (' . $exam['subject_code'] . ') - ' . ($exam['department'] ?? 'All') . ' - ' . $exam['exam_date'] . ' ' . $exam['exam_time'] . ' (' . $exam['session'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success">Allocate Seats</button>
                    <a href="view_allocation.php" class="btn btn-info btn-small" style="margin-left:10px;">View Allocation</a>
                </form>
            </div>

            <!-- Algorithm Info -->
            <div class="content-box">
                <h2>How Allocation Works</h2>
                <ol style="padding-left:20px; color:#4a5568; line-height:2;">
                    <li>All students are fetched for the selected exam department and kept in register-number order.</li>
                    <li>If the exam has a department, only that department students are considered.</li>
                    <li>Students are grouped by department and assigned in strict round-robin order using the department sequence found in the student list: Dept 1 student 1, Dept 2 student 1, Dept 3 student 1, then Dept 1 student 2, and so on.</li>
                    <li>All exam halls are fetched, sorted by name.</li>
                    <li>For the same date, time, and session, existing allocations are checked hall-wise.</li>
                    <li>Students are distributed across as many halls as needed using the remaining capacity in each hall.</li>
                    <li>Seat numbers continue correctly inside each hall and are renumbered continuously for that slot.</li>
                    <li>Each student gets exactly one seat per exam.</li>
                    <li>Attendance records are pre-created (default: Absent).</li>
                </ol>
            </div>
        </div>
    </div>
    <script src="../js/validation.js"></script>
</body>
</html>
