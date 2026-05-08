<?php
// ============================================
// MANAGE EXAMS PAGE
// Admin can view and delete exams
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$message = '';
$msg_type = '';
$edit_exam = null;
$show_exam_form = false;
$department_options = ['All', 'CS', 'BCA', 'BCOM', 'BBA', 'BBM', 'Physics'];

// Load exam details for edit
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM exams WHERE id = $edit_id");

    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_exam = $edit_result->fetch_assoc();
        $show_exam_form = true;
    } else {
        $message = "Exam not found!";
        $msg_type = "error";
    }
}

// Handle update exam form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_exam') {
    $exam_id = (int)$_POST['exam_id'];
    $exam_name = mysqli_real_escape_string($conn, trim($_POST['exam_name']));
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $subject_code = mysqli_real_escape_string($conn, trim($_POST['subject_code']));
    $department = mysqli_real_escape_string($conn, trim($_POST['department']));
    $exam_date = mysqli_real_escape_string($conn, trim($_POST['exam_date']));
    $exam_time = mysqli_real_escape_string($conn, trim($_POST['exam_time']));
    $session = mysqli_real_escape_string($conn, trim($_POST['session']));

    $sql = "UPDATE exams SET exam_name = '$exam_name', subject = '$subject', subject_code = '$subject_code', department = '$department', exam_date = '$exam_date', exam_time = '$exam_time', session = '$session' WHERE id = $exam_id";
    if ($conn->query($sql)) {
        $message = "Exam updated successfully!";
        $msg_type = "success";
        $edit_exam = null;
        $show_exam_form = false;
    } else {
        $message = "Error updating exam: " . $conn->error;
        $msg_type = "error";
        $edit_exam = [
            'id' => $exam_id,
            'exam_name' => $_POST['exam_name'],
            'subject' => $_POST['subject'],
            'subject_code' => $_POST['subject_code'],
            'department' => $_POST['department'],
            'exam_date' => $_POST['exam_date'],
            'exam_time' => $_POST['exam_time'],
            'session' => $_POST['session']
        ];
        $show_exam_form = true;
    }
}

// Handle add exam form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_exam') {
    $exam_name = mysqli_real_escape_string($conn, $_POST['exam_name']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $subject_code = mysqli_real_escape_string($conn, $_POST['subject_code']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $exam_date = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $exam_time = mysqli_real_escape_string($conn, $_POST['exam_time']);
    $session = mysqli_real_escape_string($conn, $_POST['session']);

    $sql = "INSERT INTO exams (exam_name, subject, subject_code, department, exam_date, exam_time, session) VALUES ('$exam_name', '$subject', '$subject_code', '$department', '$exam_date', '$exam_time', '$session')";
    if ($conn->query($sql)) {
        $message = "Exam added successfully!";
        $msg_type = "success";
        $show_exam_form = false;
    } else {
        $message = "Error adding exam: " . $conn->error;
        $msg_type = "error";
        $show_exam_form = true;
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM exams WHERE id = $id")) {
        $message = "Exam deleted successfully!";
        $msg_type = "success";
    } else {
        $message = "Error: " . $conn->error;
        $msg_type = "error";
    }
}

// Fetch all exams
$result = $conn->query("SELECT * FROM exams ORDER BY exam_date ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Smart Exam</title>
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
                <li><a href="manage_exams.php" class="active">Exams</a></li>
                <li><a href="manage_halls.php">Halls</a></li>
                <li><a href="manage_supervisors.php">Supervisors</a></li>
                <li><a href="allocate_seat.php">Seat Allocation</a></li>
                <li><a href="view_allocation.php">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div class="page-title-info">
                    <h1>Manage Exams</h1>
                    <p>View and manage all exams</p>
                </div>
                <button id="toggle-exam-form" class="btn btn-primary btn-add" aria-expanded="false" aria-controls="exam-form-panel">
                    <span class="toggle-plus">+</span> <span class="toggle-text"><?php echo $edit_exam ? 'Update Exam' : 'Add New Exam'; ?></span>
                </button>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div id="exam-form-panel" class="exam-form-panel<?php echo ($show_exam_form || $edit_exam) ? ' is-open' : ''; ?>" aria-hidden="<?php echo ($show_exam_form || $edit_exam) ? 'false' : 'true'; ?>">
                <div class="exam-form-backdrop"></div>
                <div class="exam-form-container">
                    <button type="button" id="close-exam-form" class="modal-close">&times;</button>
                    <h3><?php echo $edit_exam ? 'Update Exam' : 'Add New Exam'; ?></h3>
                    <form method="POST" onsubmit="return validateExamForm()">
                        <input type="hidden" name="action" value="<?php echo $edit_exam ? 'update_exam' : 'add_exam'; ?>">
                        <?php if ($edit_exam): ?>
                            <input type="hidden" name="exam_id" value="<?php echo $edit_exam['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="exam_name">Exam Name</label>
                            <input type="text" id="exam_name" name="exam_name" value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['exam_name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['subject']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="subject_code">Subject Code</label>
                            <input type="text" id="subject_code" name="subject_code" value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['subject_code']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select id="department" name="department" required>
                                <?php foreach ($department_options as $department_option): ?>
                                    <option value="<?php echo htmlspecialchars($department_option); ?>" <?php echo ($edit_exam ? ($edit_exam['department'] ?? 'All') : 'All') === $department_option ? 'selected' : ''; ?>>
                                        <?php echo $department_option === 'All' ? 'All Departments' : htmlspecialchars($department_option); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exam_date">Exam Date</label>
                            <input type="date" id="exam_date" name="exam_date" value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['exam_date']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="exam_time">Exam Time</label>
                            <input type="time" id="exam_time" name="exam_time" value="<?php echo $edit_exam ? htmlspecialchars($edit_exam['exam_time']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="session">Session</label>
                            <select id="session" name="session" required>
                                <option value="">Select Session</option>
                                <option value="Morning" <?php echo ($edit_exam && $edit_exam['session'] == 'Morning') ? 'selected' : ''; ?>>FN</option>
                                <option value="Afternoon" <?php echo ($edit_exam && $edit_exam['session'] == 'Afternoon') ? 'selected' : ''; ?>>AN</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success"><?php echo $edit_exam ? 'Update Exam' : 'Add Exam'; ?></button>
                        <?php if ($edit_exam): ?>
                            <a href="manage_exams.php" class="btn btn-info btn-small" style="margin-left:10px;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="content-box">
                <h2>All Exams (<?php echo $result->num_rows; ?>)</h2>
                
                <?php if ($result->num_rows > 0): ?>
                <table class="data-table">
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
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['department'] ?? 'All'); ?></td>
                            <td><?php echo $row['exam_date']; ?></td>
                            <td><?php echo $row['exam_time']; ?></td>
                            <td><?php echo htmlspecialchars($row['session']); ?></td>
                            <td>
                                <a href="manage_exams.php?edit=<?php echo $row['id']; ?>" 
                                   class="btn btn-warning btn-small">Update</a>
                                <a href="manage_exams.php?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger btn-small"
                                   style="margin-left:5px;"
                                   onclick="return confirmDelete('exam')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No exams found. <a href="manage_exams.php">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../js/validation.js"></script>
    <script>
        (function () {
            var toggleButton = document.getElementById('toggle-exam-form');
            var formPanel = document.getElementById('exam-form-panel');

            if (!toggleButton || !formPanel) {
                return;
            }

            var icon = toggleButton.querySelector('.toggle-plus');
            var textNode = toggleButton.querySelector('.toggle-text');
            var baseLabel = '<?php echo $edit_exam ? 'Update Exam' : 'Add New Exam'; ?>';

            // If for some reason the static text span is missing, keep fallback in place.
            if (!textNode) {
                textNode = document.createElement('span');
                textNode.className = 'toggle-text';
                toggleButton.appendChild(textNode);
            }

            function updateToggleButton(isOpen) {
                toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                if (icon) {
                    icon.textContent = isOpen ? '−' : '+';
                }
                textNode.textContent = isOpen ? 'Close Form' : baseLabel;
            }

            var initialOpen = formPanel.classList.contains('is-open') || <?php echo ($show_exam_form || $edit_exam) ? 'true' : 'false'; ?>;
            if (initialOpen) {
                formPanel.classList.add('is-open');
            }
            updateToggleButton(initialOpen);

            function closeForm() {
                formPanel.classList.remove('is-open');
                updateToggleButton(false);
            }

            toggleButton.addEventListener('click', function () {
                var isOpen = formPanel.classList.toggle('is-open');
                updateToggleButton(isOpen);
            });

            var closeButton = document.getElementById('close-exam-form');
            if (closeButton) {
                closeButton.addEventListener('click', closeForm);
            }

            var backdrop = formPanel.querySelector('.exam-form-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', closeForm);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && formPanel.classList.contains('is-open')) {
                    closeForm();
                }
            });
        })();
    </script>
</body>
</html>
