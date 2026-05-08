<?php
// ============================================
// MANAGE STUDENTS PAGE
// Admin can view, edit and delete students
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

$student_columns = [];
$columns_result = $conn->query("SHOW COLUMNS FROM students");
if ($columns_result) {
    while ($column = $columns_result->fetch_assoc()) {
        $student_columns[] = $column['Field'];
    }
}
$has_phone_column = in_array('phone', $student_columns, true);
$has_parent_phone_column = in_array('parent_phone', $student_columns, true);

$message = '';
$msg_type = '';
$edit_student = null;
$show_student_form = false;
$search_term = trim($_GET['search'] ?? '');
$department_filter = trim($_GET['department_filter'] ?? '');
$department_options = [];

// Load student details for edit
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_student = $edit_result->fetch_assoc();
        // Do not force the form to show automatically; user should click the button
        // to open the modal form. This avoids the form staying visible unexpectedly.
        // $show_student_form = true;
    } else {
        $message = "Student not found!";
        $msg_type = "error";
    }
    $edit_stmt->close();
}

// Handle update student form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_student') {
    // Keep form open for update flow; close only on successful update
    $show_student_form = true;
    $student_id = (int)$_POST['student_id'];
    $register_no = trim($_POST['register_no']);
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $year = (int)$_POST['year'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $parent_phone = isset($_POST['parent_phone']) ? trim($_POST['parent_phone']) : '';
    $password = trim($_POST['password']);

    $check_stmt = $conn->prepare("SELECT id FROM students WHERE register_no = ? AND id != ?");
    $check_stmt->bind_param("si", $register_no, $student_id);
    $check_stmt->execute();
    $check = $check_stmt->get_result();

    if ($check && $check->num_rows > 0) {
        $message = "Register number already exists!";
        $msg_type = "error";
        $edit_student = [
            'id' => $student_id,
            'register_no' => isset($_POST['register_no']) ? $_POST['register_no'] : '',
            'name' => isset($_POST['name']) ? $_POST['name'] : '',
            'department' => isset($_POST['department']) ? $_POST['department'] : '',
            'year' => isset($_POST['year']) ? $_POST['year'] : '',
            'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
            'parent_phone' => isset($_POST['parent_phone']) ? $_POST['parent_phone'] : ''
        ];
    } else {
        if ($password !== '') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            if ($has_phone_column && $has_parent_phone_column) {
                $stmt = $conn->prepare("UPDATE students SET register_no = ?, name = ?, department = ?, year = ?, phone = ?, parent_phone = ?, password = ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE students SET register_no = ?, name = ?, department = ?, year = ?, password = ? WHERE id = ?");
            }
        } else {
            if ($has_phone_column && $has_parent_phone_column) {
                $stmt = $conn->prepare("UPDATE students SET register_no = ?, name = ?, department = ?, year = ?, phone = ?, parent_phone = ? WHERE id = ?");
            } else {
                $stmt = $conn->prepare("UPDATE students SET register_no = ?, name = ?, department = ?, year = ? WHERE id = ?");
            }
        }

        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
            $msg_type = "error";
        } else {
            if ($password !== '') {
                if ($has_phone_column && $has_parent_phone_column) {
                    $stmt->bind_param("sssisssi", $register_no, $name, $department, $year, $phone, $parent_phone, $hashed_password, $student_id);
                } else {
                    $stmt->bind_param("sssisi", $register_no, $name, $department, $year, $hashed_password, $student_id);
                }
            } else {
                if ($has_phone_column && $has_parent_phone_column) {
                    $stmt->bind_param("sssissi", $register_no, $name, $department, $year, $phone, $parent_phone, $student_id);
                } else {
                    $stmt->bind_param("sssii", $register_no, $name, $department, $year, $student_id);
                }
            }
        }

        if (isset($stmt) && $stmt && $stmt->execute()) {
            $message = "Student updated successfully!";
            $msg_type = "success";
            $edit_student = null;
            // Hide form after successful update
            $show_student_form = false;
        } elseif (isset($stmt) && $stmt) {
            $message = "Error updating student: " . $stmt->error;
            $msg_type = "error";
            $show_student_form = true;
            $edit_student = [
                'id' => $student_id,
                'register_no' => isset($_POST['register_no']) ? $_POST['register_no'] : '',
                'name' => isset($_POST['name']) ? $_POST['name'] : '',
                'department' => isset($_POST['department']) ? $_POST['department'] : '',
                'year' => isset($_POST['year']) ? $_POST['year'] : '',
                'phone' => isset($_POST['phone']) ? $_POST['phone'] : '',
                'parent_phone' => isset($_POST['parent_phone']) ? $_POST['parent_phone'] : ''
            ];
        }

        if ($stmt) {
            $stmt->close();
        }
    }
    $check_stmt->close();
}

// Handle add student form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    // Keep hidden by default; reopen only on error
    $show_student_form = true;
    $register_no = isset($_POST['register_no']) ? $_POST['register_no'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    $year = isset($_POST['year']) ? (int)$_POST['year'] : 0;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $parent_phone = isset($_POST['parent_phone']) ? trim($_POST['parent_phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check duplicate using prepared statement
    $check_stmt = $conn->prepare("SELECT id FROM students WHERE register_no = ?");
    $check_stmt->bind_param("s", $register_no);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    
    if ($check && $check->num_rows > 0) {
        $message = "Register number already exists!";
        $msg_type = "error";
        $show_student_form = true;
    } else {
        if ($has_phone_column && $has_parent_phone_column) {
            $stmt = $conn->prepare("INSERT INTO students (register_no, name, department, year, phone, parent_phone, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        } else {
            $stmt = $conn->prepare("INSERT INTO students (register_no, name, department, year, password) VALUES (?, ?, ?, ?, ?)");
        }
        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
            $msg_type = "error";
            $show_student_form = true;
        } else {
            if ($has_phone_column && $has_parent_phone_column) {
                $stmt->bind_param("sssisss", $register_no, $name, $department, $year, $phone, $parent_phone, $hashed_password);
            } else {
                $stmt->bind_param("sssis", $register_no, $name, $department, $year, $hashed_password);
            }
            if ($stmt->execute()) {
                $message = "Student added successfully!";
                $msg_type = "success";
                $show_student_form = false; // hide after successful add
            } else {
                $message = "Error adding student: " . $stmt->error;
                $msg_type = "error";
                $show_student_form = true;
            }
            $stmt->close();
        }
    }
    $check_stmt->close();
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Delete student and related seat allocations (CASCADE handles this)
    $del_stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $del_stmt->bind_param("i", $id);
    if ($del_stmt->execute()) {
        $message = "Student deleted successfully!";
        $msg_type = "success";
    } else {
        $message = "Error deleting student: " . $del_stmt->error;
        $msg_type = "error";
    }
    $del_stmt->close();
}

$department_result = $conn->query("SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
if ($department_result) {
    while ($department_row = $department_result->fetch_assoc()) {
        $department_options[] = $department_row['department'];
    }
}

$student_query = "SELECT * FROM students WHERE 1 = 1";
$student_params = [];
$student_types = '';

if ($search_term !== '') {
    $student_query .= " AND (register_no LIKE ? OR name LIKE ?)";
    $like_search = '%' . $search_term . '%';
    $student_params[] = $like_search;
    $student_params[] = $like_search;
    $student_types .= 'ss';
}

if ($department_filter !== '') {
    $student_query .= " AND department = ?";
    $student_params[] = $department_filter;
    $student_types .= 's';
}

$student_query .= " ORDER BY register_no ASC";
$student_stmt = $conn->prepare($student_query);

if ($student_stmt && $student_types !== '') {
    $student_stmt->bind_param($student_types, ...$student_params);
}

if ($student_stmt) {
    $student_stmt->execute();
    $result = $student_stmt->get_result();
} else {
    $result = false;
    $message = "Error loading students: " . $conn->error;
    $msg_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Smart Exam</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Admin Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="add_admin.php">Add Admin</a></li>
                <li><a href="manage_students.php" class="active">Students</a></li>
                <li><a href="manage_exams.php">Exams</a></li>
                <li><a href="manage_halls.php">Halls</a></li>
                <li><a href="manage_supervisors.php">Supervisors</a></li>
                <li><a href="allocate_seat.php">Seat Allocation</a></li>
                <li><a href="view_allocation.php">View Allocation</a></li>
                <li><a href="view_attendance_summary.php">Attendance Summary</a></li>
                <li><a href="view_malpractice_summary.php">Malpractice Reports</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <div class="page-title-info">
                    <h1>Manage Students</h1>
                    <p>View and manage all registered students</p>
                </div>
                <button id="toggle-student-form" class="btn btn-primary btn-add" aria-expanded="false" aria-controls="student-form-panel">
                    <span class="toggle-plus">+</span> <span class="toggle-text"><?php echo $edit_student ? 'Update Student' : 'Add New Student'; ?></span>
                </button>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="content-box">
                <h2>Search and Filter</h2>
                <form method="get" action="manage_students.php" class="summary-filter-form">
                    <div class="form-group">
                        <label for="search">Search Student</label>
                        <input type="text" name="search" id="search" placeholder="Search by register number or name" value="<?php echo htmlspecialchars($search_term); ?>">
                    </div>
                    <div class="form-group">
                        <label for="department_filter">Department</label>
                        <select name="department_filter" id="department_filter">
                            <option value="">All Departments</option>
                            <?php foreach ($department_options as $department_option): ?>
                                <option value="<?php echo htmlspecialchars($department_option); ?>" <?php echo $department_filter === $department_option ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department_option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    <a href="manage_students.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>

            <div id="student-form-panel" class="student-form-panel<?php echo ($show_student_form || $edit_student) ? ' is-open' : ''; ?>" aria-hidden="<?php echo ($show_student_form || $edit_student) ? 'false' : 'true'; ?>">
                <div class="student-form-backdrop"></div>
                <div class="student-form-container">
                    <button type="button" id="close-student-form" class="modal-close">&times;</button>
                    <h3><?php echo $edit_student ? 'Update Student' : 'Add New Student'; ?></h3>
                    <form method="post" action="manage_students.php">
                        <input type="hidden" name="action" value="<?php echo $edit_student ? 'update_student' : 'add_student'; ?>">
                        <?php if ($edit_student): ?>
                            <input type="hidden" name="student_id" value="<?php echo $edit_student['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="register_no">Register Number</label>
                            <input type="text" name="register_no" id="register_no" value="<?php echo $edit_student ? htmlspecialchars($edit_student['register_no']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Student Name</label>
                            <input type="text" name="name" id="name" value="<?php echo $edit_student ? htmlspecialchars($edit_student['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="department">Department</label>
                            <select name="department" id="department" required>
                                <option value="">-- Select Department --</option>
                                <option value="CS" <?php echo ($edit_student && $edit_student['department'] === 'CS') ? 'selected' : ''; ?>>CS</option>
                                <option value="BCA" <?php echo ($edit_student && $edit_student['department'] === 'BCA') ? 'selected' : ''; ?>>BCA</option>
                                <option value="BCOM" <?php echo ($edit_student && $edit_student['department'] === 'BCOM') ? 'selected' : ''; ?>>BCOM</option>
                                <option value="BBA" <?php echo ($edit_student && $edit_student['department'] === 'BBA') ? 'selected' : ''; ?>>BBA</option>
                                <option value="BBM" <?php echo ($edit_student && $edit_student['department'] === 'BBM') ? 'selected' : ''; ?>>BBM</option>
                                <option value="Physics" <?php echo ($edit_student && $edit_student['department'] === 'Physics') ? 'selected' : ''; ?>>Physics</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="year">Year</label>
                            <select name="year" id="year" required>
                                <option value="">-- Select Year --</option>
                                <option value="1" <?php echo ($edit_student && $edit_student['year'] == 1) ? 'selected' : ''; ?>>1</option>
                                <option value="2" <?php echo ($edit_student && $edit_student['year'] == 2) ? 'selected' : ''; ?>>2</option>
                                <option value="3" <?php echo ($edit_student && $edit_student['year'] == 3) ? 'selected' : ''; ?>>3</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" name="phone" id="phone" value="<?php echo ($edit_student && isset($edit_student['phone'])) ? htmlspecialchars($edit_student['phone']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="parent_phone">Parent Phone Number</label>
                            <input type="text" name="parent_phone" id="parent_phone" value="<?php echo ($edit_student && isset($edit_student['parent_phone'])) ? htmlspecialchars($edit_student['parent_phone']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" name="password" id="password" placeholder="Enter new password" <?php echo $edit_student ? '' : 'required'; ?>>
                        </div>
                        <button type="submit" class="btn btn-success"><?php echo $edit_student ? 'Update Student' : 'Add Student'; ?></button>
                    </form>
                </div>
            </div>

            <?php if ($result && $result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Register No</th>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Year</th>
                            <th>Phone</th>
                            <th>Parent Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 1;
                        while ($row = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['register_no']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo $row['year']; ?></td>
                            <td><?php echo isset($row['phone']) ? htmlspecialchars($row['phone']) : ''; ?></td>
                            <td><?php echo isset($row['parent_phone']) ? htmlspecialchars($row['parent_phone']) : ''; ?></td>
                            <td>
                                <a href="manage_students.php?edit=<?php echo $row['id']; ?>" class="btn btn-warning btn-small">Update</a>
                                <a href="manage_students.php?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger btn-small"
                                   style="margin-left:5px;"
                                   onclick="return confirmDelete('student')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div class="alert alert-info">No students found for the selected search or department filter.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/validation.js"></script>
    <script>
        (function () {
            var toggleButton = document.getElementById('toggle-student-form');
            var formPanel = document.getElementById('student-form-panel');

            if (!toggleButton || !formPanel) {
                return;
            }

            var icon = toggleButton.querySelector('.toggle-plus');
            var textNode = toggleButton.querySelector('.toggle-text');
            var baseLabel = '<?php echo $edit_student ? 'Update Student' : 'Add New Student'; ?>';

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

            var initialOpen = formPanel.classList.contains('is-open') || <?php echo ($show_student_form || $edit_student) ? 'true' : 'false'; ?>;
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

            var closeButton = document.getElementById('close-student-form');
            if (closeButton) {
                closeButton.addEventListener('click', closeForm);
            }

            var backdrop = formPanel.querySelector('.student-form-backdrop');
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
