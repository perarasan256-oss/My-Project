<?php
// ============================================
// MANAGE SUPERVISORS PAGE
// ============================================

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$message = '';
$msg_type = '';
$edit_supervisor = null;
$show_supervisor_form = false;

function generateSupervisorUsername($conn, $name) {
    $base = preg_replace('/[^a-z0-9]/', '', strtolower(trim($name)));
    if ($base === '') {
        $base = 'supervisor';
    }

    $username = $base;
    $suffix = 1;

    while (true) {
        $escaped_username = mysqli_real_escape_string($conn, $username);
        $check = $conn->query("SELECT id FROM supervisors WHERE username = '$escaped_username'");
        if (!$check || $check->num_rows === 0) {
            break;
        }
        $username = $base . $suffix;
        $suffix++;
    }

    return $username;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM supervisors WHERE id = $id")) {
        $message = "Supervisor deleted successfully!";
        $msg_type = "success";
    } else {
        $message = "Error: " . $conn->error;
        $msg_type = "error";
    }
}

// Load supervisor details for edit
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $conn->query("SELECT * FROM supervisors WHERE id = $edit_id");

    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_supervisor = $edit_result->fetch_assoc();
        $show_supervisor_form = true;
    } else {
        $message = "Supervisor not found.";
        $msg_type = "error";
    }
}

// Handle hall assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_hall'])) {
    $sup_id = (int)$_POST['supervisor_id'];
    $hall_id = (int)$_POST['hall_id'];
    $conn->query("UPDATE supervisors SET assigned_hall = $hall_id WHERE id = $sup_id");
    $message = "Hall assigned successfully!";
    $msg_type = "success";
}

// Handle update supervisor form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_supervisor') {
    $supervisor_id = (int)$_POST['supervisor_id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = trim($_POST['password']);

    // Check if username already exists (excluding current supervisor)
    $check_username = $conn->query("SELECT id FROM supervisors WHERE username = '$username' AND id != $supervisor_id");
    if ($check_username && $check_username->num_rows > 0) {
        $message = "Username already exists. Please choose a different username.";
        $msg_type = "error";
        $edit_supervisor = [
            'id' => $supervisor_id,
            'name' => $_POST['name'],
            'username' => $_POST['username']
        ];
        $show_supervisor_form = true;
    } else {
        $sql = "UPDATE supervisors SET name = '$name', username = '$username'";

        if ($password !== '') {
            $safe_password = mysqli_real_escape_string($conn, password_hash($password, PASSWORD_DEFAULT));
            $sql .= ", password = '$safe_password'";
        }

        $sql .= " WHERE id = $supervisor_id";

        if ($conn->query($sql)) {
            $message = "Supervisor updated successfully!";
            $msg_type = "success";
            $edit_supervisor = null;
            $show_supervisor_form = false;
        } else {
            $message = "Error updating supervisor: " . $conn->error;
            $msg_type = "error";
            $edit_supervisor = [
                'id' => $supervisor_id,
                'name' => $_POST['name'],
                'username' => $_POST['username']
            ];
            $show_supervisor_form = true;
        }
    }
}

// Handle add supervisor form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_supervisor') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, password_hash($_POST['password'], PASSWORD_DEFAULT));

    // Check if username already exists
    $check_username = $conn->query("SELECT id FROM supervisors WHERE username = '$username'");
    if ($check_username && $check_username->num_rows > 0) {
        $message = "Username already exists. Please choose a different username.";
        $msg_type = "error";
        $show_supervisor_form = true;
    } else {
        $sql = "INSERT INTO supervisors (name, username, password) VALUES ('$name', '$username', '$password')";
        if ($conn->query($sql)) {
            $message = "Supervisor added successfully!";
            $msg_type = "success";
            $show_supervisor_form = false;
        } else {
            $message = "Error adding supervisor: " . $conn->error;
            $msg_type = "error";
            $show_supervisor_form = true;
        }
    }
}

$result = $conn->query("SELECT s.*, eh.hall_name FROM supervisors s LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id ORDER BY s.name ASC");
$halls = $conn->query("SELECT * FROM exam_halls ORDER BY hall_name ASC");
$hall_options = [];
while ($h = $halls->fetch_assoc()) {
    $hall_options[] = $h;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Supervisors - Smart Exam</title>
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
                <li><a href="manage_supervisors.php" class="active">Supervisors</a></li>
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
                    <h1>Manage Supervisors</h1>
                    <p>View, assign halls, and manage supervisors</p>
                </div>
                <button id="toggle-supervisor-form" class="btn btn-primary btn-add" aria-expanded="false" aria-controls="supervisor-form-panel">
                    <span class="toggle-plus">+</span> <span class="toggle-text"><?php echo $edit_supervisor ? 'Update Supervisor' : 'Add New Supervisor'; ?></span>
                </button>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div id="supervisor-form-panel" class="supervisor-form-panel<?php echo ($show_supervisor_form || $edit_supervisor) ? ' is-open' : ''; ?>" aria-hidden="<?php echo ($show_supervisor_form || $edit_supervisor) ? 'false' : 'true'; ?>">
                <div class="supervisor-form-backdrop"></div>
                <div class="supervisor-form-container">
                    <button type="button" id="close-supervisor-form" class="modal-close">&times;</button>
                    <h3><?php echo $edit_supervisor ? 'Update Supervisor' : 'Add New Supervisor'; ?></h3>
                    <form method="POST" onsubmit="return validateSupervisorForm()">
                        <input type="hidden" name="action" value="<?php echo $edit_supervisor ? 'update_supervisor' : 'add_supervisor'; ?>">
                        <?php if ($edit_supervisor): ?>
                            <input type="hidden" name="supervisor_id" value="<?php echo $edit_supervisor['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo $edit_supervisor ? htmlspecialchars($edit_supervisor['name']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo $edit_supervisor ? htmlspecialchars($edit_supervisor['username']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password"><?php echo $edit_supervisor ? 'Password (leave blank to keep current password)' : 'Password'; ?></label>
                            <input type="password" id="password" name="password" <?php echo $edit_supervisor ? '' : 'required'; ?>>
                        </div>
                        <button type="submit" class="btn btn-success"><?php echo $edit_supervisor ? 'Update Supervisor' : 'Add Supervisor'; ?></button>
                        <?php if ($edit_supervisor): ?>
                            <a href="manage_supervisors.php" class="btn btn-info btn-small" style="margin-left:10px;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="content-box">
                <h2>All Supervisors (<?php echo $result->num_rows; ?>)</h2>
                
                <?php if ($result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Assigned Hall</th>
                            <th>Assign Hall</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo $row['hall_name'] ? htmlspecialchars($row['hall_name']) : '<em>Not assigned</em>'; ?></td>
                            <td>
                                <!-- Mini form to assign a hall -->
                                <form method="POST" style="display:flex; gap:5px; align-items:center;">
                                    <input type="hidden" name="supervisor_id" value="<?php echo $row['id']; ?>">
                                    <select name="hall_id" style="padding:5px; font-size:12px; border:1px solid #ccc; border-radius:4px;">
                                        <option value="0">-- None --</option>
                                        <?php foreach ($hall_options as $hall): ?>
                                            <option value="<?php echo $hall['id']; ?>" 
                                                <?php echo ($row['assigned_hall'] == $hall['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($hall['hall_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="assign_hall" class="btn btn-info btn-small">Assign</button>
                                </form>
                            </td>
                            <td>
                                <a href="manage_supervisors.php?edit=<?php echo $row['id']; ?>" 
                                   class="btn btn-warning btn-small">Update</a>
                                <a href="manage_supervisors.php?delete=<?php echo $row['id']; ?>" 
                                   class="btn btn-danger btn-small"
                                   style="margin-left:5px;"
                                   onclick="return confirmDelete('supervisor')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p>No supervisors found. <a href="manage_supervisors.php">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../js/validation.js"></script>
    <script>
        (function () {
            var toggleButton = document.getElementById('toggle-supervisor-form');
            var formPanel = document.getElementById('supervisor-form-panel');

            if (!toggleButton || !formPanel) {
                return;
            }

            var icon = toggleButton.querySelector('.toggle-plus');
            var textNode = toggleButton.querySelector('.toggle-text');
            var baseLabel = '<?php echo $edit_supervisor ? 'Update Supervisor' : 'Add New Supervisor'; ?>';

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

            var initialOpen = formPanel.classList.contains('is-open') || <?php echo ($show_supervisor_form || $edit_supervisor) ? 'true' : 'false'; ?>;
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

            var closeButton = document.getElementById('close-supervisor-form');
            if (closeButton) {
                closeButton.addEventListener('click', closeForm);
            }

            var backdrop = formPanel.querySelector('.supervisor-form-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', closeForm);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeForm();
                }
            });
        })();
    </script>
</body>
</html>
