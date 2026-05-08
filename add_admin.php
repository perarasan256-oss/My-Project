<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$message = '';
$msg_type = '';
$show_admin_form = false;

// Keep older databases working by adding the column when this page is opened.
$full_name_column = $conn->query("SHOW COLUMNS FROM admin LIKE 'full_name'");
if ($full_name_column && $full_name_column->num_rows === 0) {
    $conn->query("ALTER TABLE admin ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER id");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_admin') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($full_name === '' || $username === '' || $password === '' || $confirm_password === '') {
        $message = 'Please fill in all fields.';
        $msg_type = 'error';
        $show_admin_form = true;
    } elseif ($password !== $confirm_password) {
        $message = 'Password and confirm password do not match.';
        $msg_type = 'error';
        $show_admin_form = true;
    } else {
        $check_stmt = $conn->prepare("SELECT id FROM admin WHERE username = ?");

        if (!$check_stmt) {
            $message = 'Error preparing username check: ' . $conn->error;
            $msg_type = 'error';
            $show_admin_form = true;
        } else {
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result && $check_result->num_rows > 0) {
                $message = 'Username already exists. Please choose a different username.';
                $msg_type = 'error';
                $show_admin_form = true;
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_stmt = $conn->prepare("INSERT INTO admin (full_name, username, password) VALUES (?, ?, ?)");

                if (!$insert_stmt) {
                    $message = 'Error preparing insert: ' . $conn->error;
                    $msg_type = 'error';
                    $show_admin_form = true;
                } else {
                    $insert_stmt->bind_param("sss", $full_name, $username, $hashed_password);

                    if ($insert_stmt->execute()) {
                        $message = 'New admin added successfully!';
                        $msg_type = 'success';
                        $show_admin_form = false;
                        $_POST = [];
                    } else {
                        $message = 'Error adding admin: ' . $insert_stmt->error;
                        $msg_type = 'error';
                        $show_admin_form = true;
                    }

                    $insert_stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    if ($delete_id === (int)$_SESSION['admin_id']) {
        $message = 'You cannot delete the currently logged-in admin account.';
        $msg_type = 'error';
    } else {
        $count_result = $conn->query("SELECT COUNT(*) AS total FROM admin");
        $admin_count = $count_result ? (int)$count_result->fetch_assoc()['total'] : 0;

        if ($admin_count <= 1) {
            $message = 'At least one admin account must remain in the system.';
            $msg_type = 'error';
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");

            if (!$delete_stmt) {
                $message = 'Error preparing delete: ' . $conn->error;
                $msg_type = 'error';
            } else {
                $delete_stmt->bind_param("i", $delete_id);

                if ($delete_stmt->execute()) {
                    if ($delete_stmt->affected_rows > 0) {
                        $message = 'Admin deleted successfully!';
                        $msg_type = 'success';
                    } else {
                        $message = 'Admin not found.';
                        $msg_type = 'error';
                    }
                } else {
                    $message = 'Error deleting admin: ' . $delete_stmt->error;
                    $msg_type = 'error';
                }

                $delete_stmt->close();
            }
        }
    }
}

$admins = $conn->query("SELECT id, full_name, username FROM admin ORDER BY full_name ASC, username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin - Smart Exam</title>
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
                <li><a href="add_admin.php" class="active">Add Admin</a></li>
                <li><a href="manage_students.php">Students</a></li>
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

        <div class="main-content">
            <div class="page-header">
                <div class="page-title-info">
                    <h1>Add Admin</h1>
                    <p>View and manage admin accounts</p>
                </div>
                <button id="toggle-admin-form" class="btn btn-primary btn-add" aria-expanded="false" aria-controls="admin-form-panel">
                    <span class="toggle-plus">+</span> <span class="toggle-text">Add New Admin</span>
                </button>
            </div>

            <?php if ($message !== ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div id="admin-form-panel" class="student-form-panel<?php echo $show_admin_form ? ' is-open' : ''; ?>" aria-hidden="<?php echo $show_admin_form ? 'false' : 'true'; ?>">
                <div class="student-form-backdrop"></div>
                <div class="student-form-container">
                    <button type="button" id="close-admin-form" class="modal-close">&times;</button>
                    <h3>Add New Admin</h3>
                    <form method="POST" action="add_admin.php">
                        <input type="hidden" name="action" value="add_admin">

                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>

                        <button type="submit" class="btn btn-success">Add Admin</button>
                    </form>
                </div>
            </div>

            <div class="content-box">
                <h2>All Admins (<?php echo $admins ? $admins->num_rows : 0; ?>)</h2>

                <?php if ($admins && $admins->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; while ($row = $admins->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo ((int)$row['id'] === (int)$_SESSION['admin_id']) ? 'Current Login' : 'Active'; ?></td>
                                    <td>
                                        <?php if ((int)$row['id'] === (int)$_SESSION['admin_id']): ?>
                                            <span>Current admin</span>
                                        <?php else: ?>
                                            <a href="add_admin.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-small" onclick="return confirm('Are you sure you want to delete this admin?');">Delete</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No admin accounts found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var toggleButton = document.getElementById('toggle-admin-form');
            var formPanel = document.getElementById('admin-form-panel');

            if (!toggleButton || !formPanel) {
                return;
            }

            var icon = toggleButton.querySelector('.toggle-plus');
            var textNode = toggleButton.querySelector('.toggle-text');
            var baseLabel = 'Add New Admin';

            function updateToggleButton(isOpen) {
                toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                if (icon) {
                    icon.textContent = isOpen ? '−' : '+';
                }
                if (textNode) {
                    textNode.textContent = isOpen ? 'Close Form' : baseLabel;
                }
            }

            var initialOpen = formPanel.classList.contains('is-open') || <?php echo $show_admin_form ? 'true' : 'false'; ?>;
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

            var closeButton = document.getElementById('close-admin-form');
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
