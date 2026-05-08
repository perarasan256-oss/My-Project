<?php
// ============================================
// MALPRACTICE REPORT PAGE
// Allows supervisor to report malpractice incidents
// ============================================

session_start();
if (!isset($_SESSION['supervisor_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$supervisor_id = $_SESSION['supervisor_id'];
$message = $_SESSION['malpractice_message'] ?? "";
unset($_SESSION['malpractice_message']);

$supervisor = $conn->query("
    SELECT s.*, eh.hall_name 
    FROM supervisors s 
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id 
    WHERE s.id = $supervisor_id
")->fetch_assoc();

$exams = null;
$students = null;

if (!$supervisor['assigned_hall']) {
    $message = "You have not been assigned to any hall.";
} else {
    $hall_id = $supervisor['assigned_hall'];
    
    // Get exams for this hall
    $exams = $conn->query("
        SELECT DISTINCT e.id, e.exam_name, e.subject, e.exam_date 
        FROM exams e 
        JOIN seat_allocation sa ON e.id = sa.exam_id 
        WHERE sa.hall_id = $hall_id 
        ORDER BY e.exam_date DESC
    ");
    
    // Get students for this hall
    $students = $conn->query("
        SELECT DISTINCT s.id, s.name, s.register_no 
        FROM students s 
        JOIN seat_allocation sa ON s.id = sa.student_id 
        WHERE sa.hall_id = $hall_id 
        ORDER BY s.register_no
    ");
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $student_id = (int)$_POST['student_id'];
        $exam_id = (int)$_POST['exam_id'];
        $description = trim($_POST['description']);
        
        if ($student_id && $exam_id && $description) {
            $stmt = $conn->prepare("INSERT INTO malpractice (student_id, exam_id, hall_id, description, reported_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisi", $student_id, $exam_id, $hall_id, $description, $supervisor_id);
            
            if ($stmt->execute()) {
                $_SESSION['malpractice_message'] = "Malpractice report submitted successfully!";
                $stmt->close();
                header("Location: malpracticereport.php");
                exit();
            } else {
                $message = "Error submitting report: " . $stmt->error;
                $stmt->close();
            }
        } else {
            $message = "Please fill all fields.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malpractice Report - Smart Exam</title>
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
                <li><a href="malpracticereport.php" class="active">Malpractice Report</a></li>
                <li><a href="viewsummary.php">View Summary</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Malpractice Report</h1>
                <p>Report malpractice incidents in your hall</p>
            </div>

            <?php if ($message): ?>
                <div class="alert <?php echo (strpos($message, 'successfully') !== false) ? 'alert-success' : (strpos($message, 'Error') !== false ? 'alert-error' : 'alert-warning'); ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($supervisor['assigned_hall']): ?>
                <div class="content-box">
                    <h2>Report Malpractice Incident</h2>
                    
                    <form method="post" action="">
                        <div class="form-group">
                            <label for="exam_id">Exam <span class="required">*</span></label>
                            <select name="exam_id" id="exam_id" required>
                                <option value="">Select Exam</option>
                                <?php if ($exams && $exams->num_rows > 0): 
                                    while ($exam = $exams->fetch_assoc()): ?>
                                    <option value="<?php echo $exam['id']; ?>">
                                        <?php echo htmlspecialchars($exam['exam_name'] . ' - ' . $exam['subject'] . ' (' . date('d-m-Y', strtotime($exam['exam_date'])) . ')'); ?>
                                    </option>
                                <?php endwhile; 
                                endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="student_id">Student <span class="required">*</span></label>
                            <select name="student_id" id="student_id" required>
                                <option value="">Select Student</option>
                                <?php if ($students && $students->num_rows > 0): 
                                    while ($student = $students->fetch_assoc()): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['register_no'] . ' - ' . $student['name']); ?>
                                    </option>
                                <?php endwhile; 
                                endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description <span class="required">*</span></label>
                            <textarea name="description" id="description" rows="6" required placeholder="Describe the malpractice incident in detail..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-medium">Submit Report</button>
                            <button type="reset" class="btn btn-secondary">Clear Form</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
