<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.html');
    exit();
}

include('../db/config.php');

$message = '';
$msg_type = 'info';
$edit_hall = null;
$show_hall_form = false;

if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM exam_halls WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) {
        $edit_hall = $res->fetch_assoc();
        $show_hall_form = true;
    } else {
        $message = 'Hall not found!';
        $msg_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_hall') {
        $hall_name = mysqli_real_escape_string($conn, trim($_POST['hall_name']));
        $hall_no = mysqli_real_escape_string($conn, trim($_POST['hall_no']));
        $total_seats = (int)$_POST['total_seats'];

        if ($hall_name === '' || $hall_no === '' || $total_seats < 1) {
            $message = 'Please provide a valid hall name, hall number, and total seats.';
            $msg_type = 'error';
            $show_hall_form = true;
        } else {
            if ($conn->query("INSERT INTO exam_halls (hall_name, hall_no, total_seats) VALUES ('$hall_name', '$hall_no', $total_seats)")) {
                $message = 'Hall added successfully!';
                $msg_type = 'success';
            } else {
                $message = 'Error adding hall: ' . $conn->error;
                $msg_type = 'error';
                $show_hall_form = true;
            }
        }
    }

    if ($action === 'update_hall') {
        $hall_id = (int)$_POST['hall_id'];
        $hall_name = mysqli_real_escape_string($conn, trim($_POST['hall_name']));
        $hall_no = mysqli_real_escape_string($conn, trim($_POST['hall_no']));
        $total_seats = (int)$_POST['total_seats'];

        if ($hall_name === '' || $hall_no === '' || $total_seats < 1) {
            $message = 'Please provide a valid hall name, hall number, and total seats.';
            $msg_type = 'error';
            $show_hall_form = true;
            $edit_hall = [
                'id' => $hall_id,
                'hall_name' => $_POST['hall_name'],
                'hall_no' => $_POST['hall_no'],
                'total_seats' => $_POST['total_seats'],
            ];
        } else {
            if ($conn->query("UPDATE exam_halls SET hall_name = '$hall_name', hall_no = '$hall_no', total_seats = $total_seats WHERE id = $hall_id")) {
                $message = 'Hall updated successfully!';
                $msg_type = 'success';
                $edit_hall = null;
            } else {
                $message = 'Error updating hall: ' . $conn->error;
                $msg_type = 'error';
                $show_hall_form = true;
                $edit_hall = [
                    'id' => $hall_id,
                    'hall_name' => $_POST['hall_name'],
                    'hall_no' => $_POST['hall_no'],
                    'total_seats' => $_POST['total_seats'],
                ];
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($conn->query("DELETE FROM exam_halls WHERE id = $id")) {
        $message = 'Hall deleted successfully!';
        $msg_type = 'success';
    } else {
        $message = 'Error deleting hall: ' . $conn->error;
        $msg_type = 'error';
    }
}

$result = $conn->query('SELECT * FROM exam_halls ORDER BY hall_name ASC');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Halls - Smart Exam</title>
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
                <li><a href="manage_halls.php" class="active">Halls</a></li>
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
                    <h1>Manage Exam Halls</h1>
                    <p>View and manage all exam halls</p>
                </div>
                <button type="button" id="toggle-hall-form" class="btn btn-primary btn-add" aria-expanded="false" aria-controls="hall-form-panel">
                    <span class="toggle-plus">+</span>
                    <span class="toggle-text"><?php echo $edit_hall ? 'Update Hall' : 'Add New Hall'; ?></span>
                </button>
            </div>

            <?php if ($message != ''): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div id="hall-form-panel" class="hall-form-panel<?php echo ($show_hall_form || $edit_hall) ? ' is-open' : ''; ?>" aria-hidden="<?php echo ($show_hall_form || $edit_hall) ? 'false' : 'true'; ?>">
                <div class="hall-form-backdrop"></div>
                <div class="hall-form-container">
                    <button type="button" id="close-hall-form" class="modal-close" onclick="closeHallForm()">&times;</button>
                    <h3><?php echo $edit_hall ? 'Update Hall' : 'Add New Hall'; ?></h3>
                    <form method="post" action="manage_halls.php" onsubmit="return validateHallForm();">
                        <input type="hidden" name="action" value="<?php echo $edit_hall ? 'update_hall' : 'add_hall'; ?>">
                        <?php if ($edit_hall): ?>
                            <input type="hidden" name="hall_id" value="<?php echo $edit_hall['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="hall_name">Hall Name</label>
                            <input type="text" id="hall_name" name="hall_name" value="<?php echo htmlspecialchars($edit_hall['hall_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="hall_no">Hall Number</label>
                            <input type="text" id="hall_no" name="hall_no" value="<?php echo htmlspecialchars($edit_hall['hall_no'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="total_seats">Total Seats</label>
                            <input type="number" id="total_seats" name="total_seats" min="1" value="<?php echo htmlspecialchars($edit_hall['total_seats'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success"><?php echo $edit_hall ? 'Update Hall' : 'Add Hall'; ?></button>
                        <?php if ($edit_hall): ?>
                            <a href="manage_halls.php" class="btn btn-info btn-small" style="margin-left:10px;">Cancel</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="content-box">
                <h2>All Halls (<?php echo $result->num_rows; ?>)</h2>
                <?php if ($result->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Hall Name</th>
                                <th>Hall Number</th>
                                <th>Total Seats</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($row['hall_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['hall_no']); ?></td>
                                    <td><?php echo $row['total_seats']; ?></td>
                                    <td>
                                        <a href="manage_halls.php?edit=<?php echo $row['id']; ?>" class="btn btn-warning btn-small">Update</a>
                                        <a href="manage_halls.php?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-small" style="margin-left:5px;" onclick="return confirmDelete('exam hall');">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No halls found. <a href="#" onclick="openHallForm(); return false;">Add one now</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../js/validation.js"></script>
    <script>
        (function () {
            var toggleButton = document.getElementById('toggle-hall-form');
            var formPanel = document.getElementById('hall-form-panel');
            if (!toggleButton || !formPanel) {
                return;
            }

            var icon = toggleButton.querySelector('.toggle-plus');
            var textNode = toggleButton.querySelector('.toggle-text');
            var baseLabel = '<?php echo $edit_hall ? 'Update Hall' : 'Add New Hall'; ?>';

            function updateToggleButton(isOpen) {
                toggleButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                if (icon) {
                    icon.textContent = isOpen ? '−' : '+';
                }
                if (textNode) {
                    textNode.textContent = isOpen ? 'Close Form' : baseLabel;
                }
            }

            var initialOpen = formPanel.classList.contains('is-open') || <?php echo ($show_hall_form || $edit_hall) ? 'true' : 'false'; ?>;
            if (initialOpen) {
                formPanel.classList.add('is-open');
            }
            updateToggleButton(initialOpen);

            window.openHallForm = function () {
                formPanel.classList.add('is-open');
                updateToggleButton(true);
            };

            window.closeHallForm = function () {
                formPanel.classList.remove('is-open');
                updateToggleButton(false);
            };

            toggleButton.addEventListener('click', function () {
                var isOpen = formPanel.classList.toggle('is-open');
                updateToggleButton(isOpen);
            });

            var closeButton = document.getElementById('close-hall-form');
            if (closeButton) {
                closeButton.addEventListener('click', closeHallForm);
            }

            var backdrop = formPanel.querySelector('.hall-form-backdrop');
            if (backdrop) {
                backdrop.addEventListener('click', closeHallForm);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && formPanel.classList.contains('is-open')) {
                    closeHallForm();
                }
            });
        })();
    </script>
</body>
</html>

