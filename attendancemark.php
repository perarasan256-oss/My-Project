<?php
// ============================================
// MARK ATTENDANCE PAGE
// Allows supervisor to mark attendance for students in their hall
// ============================================

session_start();
if (!isset($_SESSION['supervisor_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

function getSmsSettings(): array
{
    $defaults = [
        'provider' => '',
        'api_key' => '',
        'api_url' => '',
        'sender_id' => 'SMARTEXAM'
    ];

    $fileSettings = [];
    $configFile = __DIR__ . '/../db/sms_config.php';
    if (file_exists($configFile)) {
        $loaded = include $configFile;
        if (is_array($loaded)) {
            $fileSettings = $loaded;
        }
    }

    return [
        'provider' => strtolower(trim((string)(getenv('SMS_PROVIDER') ?: ($fileSettings['provider'] ?? $defaults['provider'])))),
        'api_key' => trim((string)(getenv('SMS_API_KEY') ?: ($fileSettings['api_key'] ?? $defaults['api_key']))),
        'api_url' => trim((string)(getenv('SMS_API_URL') ?: ($fileSettings['api_url'] ?? $defaults['api_url']))),
        'sender_id' => trim((string)(getenv('SMS_SENDER_ID') ?: ($fileSettings['sender_id'] ?? $defaults['sender_id']))),
    ];
}

function normalizeIndianPhone(string $phone): string
{
    $phone = trim($phone);
    if ($phone === '') {
        return '';
    }

    if (strpos($phone, '+') === 0) {
        $digits = preg_replace('/\D/', '', substr($phone, 1));
        return $digits !== '' ? '+' . $digits : '';
    }

    $digits = preg_replace('/\D/', '', $phone);
    if ($digits === '') {
        return '';
    }

    if (strlen($digits) === 10) {
        return '+91' . $digits;
    }

    if (strlen($digits) === 12 && strpos($digits, '91') === 0) {
        return '+' . $digits;
    }

    return '+' . $digits;
}

function sendAbsentSmsToParent(string $phone, string $smsText): array
{
    $settings = getSmsSettings();
    $provider = $settings['provider'];
    $apiUrl = $settings['api_url'];
    $apiKey = $settings['api_key'];
    $senderId = $settings['sender_id'] !== '' ? $settings['sender_id'] : 'SMARTEXAM';

    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'SMS API key is missing'];
    }

    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'cURL extension is not available'];
    }

    if ($provider === 'fast2sms') {
        $fast2smsUrl = 'https://www.fast2sms.com/dev/bulkV2';
        $tenDigit = preg_replace('/\D/', '', $phone);
        if (strlen($tenDigit) > 10) {
            $tenDigit = substr($tenDigit, -10);
        }
        if (strlen($tenDigit) !== 10) {
            return ['ok' => false, 'error' => 'Invalid phone format for Fast2SMS'];
        }

        $postData = http_build_query([
            'route' => 'v3',
            'sender_id' => $senderId,
            'message' => $smsText,
            'language' => 'english',
            'flash' => 0,
            'numbers' => $tenDigit
        ]);

        $ch = curl_init($fast2smsUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'authorization: ' . $apiKey,
                'cache-control: no-cache',
                'content-type: application/x-www-form-urlencoded'
            ],
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_TIMEOUT => 15
        ]);
    } else {
        if ($apiUrl === '') {
            return ['ok' => false, 'error' => 'SMS API URL is missing for custom provider'];
        }

        $payload = json_encode([
            'to' => $phone,
            'message' => $smsText,
            'sender' => $senderId
        ]);
        if ($payload === false) {
            return ['ok' => false, 'error' => 'Failed to create SMS payload'];
        }

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlError !== '') {
        return ['ok' => false, 'error' => 'cURL error: ' . $curlError];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => 'HTTP ' . $httpCode . ' - ' . substr((string)$response, 0, 180)];
    }

    return ['ok' => true, 'error' => ''];
}

function logSmsResult(string $text): void
{
    $logFile = __DIR__ . '/../tools/sms_notifications.log';
    @file_put_contents($logFile, date('Y-m-d H:i:s') . ' ' . $text . PHP_EOL, FILE_APPEND);
}

$supervisor_id = $_SESSION['supervisor_id'];

$supervisor = $conn->query("
    SELECT s.*, eh.hall_name 
    FROM supervisors s 
    LEFT JOIN exam_halls eh ON s.assigned_hall = eh.id 
    WHERE s.id = $supervisor_id
")->fetch_assoc();

$message = "";
$exams = null;
$selected_exam = null;
$students = null;

if (!$supervisor['assigned_hall']) {
    $message = "You have not been assigned to any hall.";
} else {
    $hall_id = $supervisor['assigned_hall'];
    
    // Get exams for this hall
    $exams = $conn->query("
        SELECT DISTINCT e.id, e.exam_name, e.subject, e.subject_code, e.exam_date, e.exam_time, e.session 
        FROM exams e 
        JOIN seat_allocation sa ON e.id = sa.exam_id 
        WHERE sa.hall_id = $hall_id 
        ORDER BY e.exam_date DESC
    ");
    
    if (isset($_GET['exam_id'])) {
        $exam_id = (int)$_GET['exam_id'];
        $selected_exam = $conn->query("SELECT * FROM exams WHERE id = $exam_id")->fetch_assoc();
        
        if ($selected_exam) {
            // Get students for this exam in hall
            $students = $conn->query("
                SELECT s.id, s.name, s.register_no, sa.seat_number, 
                       COALESCE(a.status, 'Not Marked') as attendance_status
                FROM seat_allocation sa
                JOIN students s ON sa.student_id = s.id
                LEFT JOIN attendance a ON a.student_id = s.id AND a.exam_id = $exam_id AND a.hall_id = $hall_id
                WHERE sa.exam_id = $exam_id AND sa.hall_id = $hall_id
                ORDER BY sa.seat_number
            ");
        }
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['exam_id'])) {
        $exam_id = (int)$_POST['exam_id'];
        $sms_sent = 0;
        $sms_failed = 0;
        $last_sms_error = '';

        $exam_stmt = $conn->prepare("SELECT exam_name, subject, exam_date, exam_time FROM exams WHERE id = ?");
        $exam_stmt->bind_param("i", $exam_id);
        $exam_stmt->execute();
        $exam_result = $exam_stmt->get_result();
        $exam_details = $exam_result ? $exam_result->fetch_assoc() : null;
        $exam_stmt->close();

        foreach ($_POST as $key => $value) {
            if (strpos($key, 'attendance_') === 0) {
                $student_id = (int)str_replace('attendance_', '', $key);
                $status = $value;
                if (!in_array($status, ['Present', 'Absent'], true)) {
                    continue;
                }
                
                // Check if already exists
                $existing = $conn->query("SELECT id, status FROM attendance WHERE student_id = $student_id AND exam_id = $exam_id AND hall_id = $hall_id");
                $existing_row = ($existing && $existing->num_rows > 0) ? $existing->fetch_assoc() : null;
                
                if ($existing_row) {
                    $conn->query("UPDATE attendance SET status = '$status', marked_by = $supervisor_id WHERE student_id = $student_id AND exam_id = $exam_id AND hall_id = $hall_id");
                } else {
                    $conn->query("INSERT INTO attendance (student_id, exam_id, hall_id, status, marked_by) VALUES ($student_id, $exam_id, $hall_id, '$status', $supervisor_id)");
                }

                $should_send_sms = ($status === 'Absent') && (!$existing_row || $existing_row['status'] !== 'Absent');

                if ($should_send_sms) {
                    $student_stmt = $conn->prepare("SELECT name, register_no, parent_phone FROM students WHERE id = ?");
                    $student_stmt->bind_param("i", $student_id);
                    $student_stmt->execute();
                    $student_result = $student_stmt->get_result();
                    $student_data = $student_result ? $student_result->fetch_assoc() : null;
                    $student_stmt->close();

                    if ($student_data && !empty($student_data['parent_phone'])) {
                        $normalized_phone = normalizeIndianPhone($student_data['parent_phone']);
                        if ($normalized_phone !== '') {
                            $sms_text = "Smart Exam Alert: " . $student_data['name'] . " (" . $student_data['register_no'] . ") was marked Absent for "
                                . ($exam_details['subject'] ?? 'exam')
                                . " on " . date('d-m-Y', strtotime($exam_details['exam_date'] ?? 'now'))
                                . " at " . date('H:i', strtotime($exam_details['exam_time'] ?? '00:00:00')) . ".";

                            $sms_result = sendAbsentSmsToParent($normalized_phone, $sms_text);

                            if ($sms_result['ok']) {
                                $sms_sent++;
                                logSmsResult("[SUCCESS] To {$normalized_phone} for student {$student_data['register_no']}");
                            } else {
                                $sms_failed++;
                                $last_sms_error = $sms_result['error'];
                                logSmsResult("[FAILED] To {$normalized_phone} for student {$student_data['register_no']} - {$sms_result['error']}");
                            }
                        } else {
                            $sms_failed++;
                            $last_sms_error = 'Invalid parent phone number';
                            logSmsResult("[FAILED] Invalid parent phone for student {$student_data['register_no']}");
                        }
                    } else {
                        $sms_failed++;
                        $last_sms_error = 'Parent phone not available';
                        logSmsResult("[FAILED] Parent phone missing for student ID {$student_id}");
                    }
                }
            }
        }
        
        $message = "Attendance marked successfully!";
        // Redirect to refresh
        $errorParam = rawurlencode(substr($last_sms_error, 0, 120));
        header("Location: attendancemark.php?exam_id=$exam_id&success=1&sms_sent=$sms_sent&sms_failed=$sms_failed&sms_error=$errorParam");
        exit();
    }
    
    if (isset($_GET['success'])) {
        $sms_sent = isset($_GET['sms_sent']) ? (int)$_GET['sms_sent'] : 0;
        $sms_failed = isset($_GET['sms_failed']) ? (int)$_GET['sms_failed'] : 0;
        $message = "Attendance marked successfully! SMS sent: $sms_sent";
        if ($sms_failed > 0) {
            $message .= ", SMS failed: $sms_failed";
            if (!empty($_GET['sms_error'])) {
                $message .= " (" . htmlspecialchars((string)$_GET['sms_error']) . ")";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Smart Exam</title>
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
                <li><a href="attendancemark.php" class="active">Mark Attendance</a></li>
                <li><a href="malpracticereport.php">Malpractice Report</a></li>
                <li><a href="viewsummary.php">View Summary</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Mark Attendance</h1>
                <p>Mark attendance for students in your hall</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($message && strpos($message, 'assigned') !== false): ?>
                <div class="alert alert-warning"><?php echo $message; ?></div>
            <?php elseif (!$selected_exam): ?>
                <!-- Select Exam -->
                <div class="content-box">
                    <h2>Select Exam</h2>
                    <?php if ($exams && $exams->num_rows > 0): ?>
                        <div class="exam-list">
                            <?php while ($exam = $exams->fetch_assoc()): ?>
                                <div class="exam-item">
                                    <h3><?php echo htmlspecialchars($exam['exam_name']); ?> - <?php echo htmlspecialchars($exam['subject']); ?> (<?php echo htmlspecialchars($exam['subject_code']); ?>)</h3>
                                    <p>Date: <?php echo date('d-m-Y', strtotime($exam['exam_date'])); ?></p>
                                    <a href="?exam_id=<?php echo $exam['id']; ?>" class="btn btn-primary">Mark Attendance</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No exams scheduled for your hall.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Mark Attendance Form -->
                <div class="content-box">
                    <h2>Mark Attendance for <?php echo htmlspecialchars($selected_exam['exam_name']); ?> - <?php echo htmlspecialchars($selected_exam['subject']); ?> (<?php echo htmlspecialchars($selected_exam['subject_code']); ?>)</h2>
                    <p>Date: <?php echo date('d-m-Y', strtotime($selected_exam['exam_date'])); ?></p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="exam_id" value="<?php echo $selected_exam['id']; ?>">
                        
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Seat No</th>
                                        <th>Register No</th>
                                        <th>Name</th>
                                        <th>Attendance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $student['seat_number']; ?></td>
                                            <td><?php echo htmlspecialchars($student['register_no']); ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td>
                                                <label>
                                                    <input type="radio" name="attendance_<?php echo $student['id']; ?>" value="Present" <?php echo ($student['attendance_status'] == 'Present') ? 'checked' : ''; ?>>
                                                    Present
                                                </label>
                                                <label>
                                                    <input type="radio" name="attendance_<?php echo $student['id']; ?>" value="Absent" <?php echo ($student['attendance_status'] == 'Absent') ? 'checked' : ''; ?>>
                                                    Absent
                                                </label>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save Attendance</button>
                            <a href="attendancemark.php" class="btn btn-secondary">Back to Exam List</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
