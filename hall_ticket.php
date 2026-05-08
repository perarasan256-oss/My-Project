<?php
// ============================================
// HALL TICKET PAGE
// Shows student exam details in a printable format
// Displays all exams in a single consolidated hall ticket
// ============================================

session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: ../index.html");
    exit();
}

include('../db/config.php');

$student_id = $_SESSION['student_id'];

// Get student details
$student = $conn->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();

// Fetch all hall ticket details for this student (all exams)
$tickets = $conn->query("
    SELECT sa.seat_number, eh.hall_name, eh.hall_no, e.exam_name, e.subject, e.subject_code, e.exam_date, e.exam_time, e.session
    FROM seat_allocation sa
    JOIN exam_halls eh ON sa.hall_id = eh.id
    JOIN exams e ON sa.exam_id = e.id
    WHERE sa.student_id = $student_id
    ORDER BY e.exam_date ASC, e.exam_time ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall Ticket</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        @media print {
            html, body {
                margin: 0;
                padding: 0;
                background: white;
                color: #000;
                width: 100%;
            }
            .sidebar, .page-header, .alert, .print-ticket-btn, .open-ticket-btn {
                display: none !important;
            }
            .page-wrapper, .main-content, .content-box, .hall-ticket {
                display: block !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
                background: white !important;
            }
            .hall-ticket {
                padding: 6mm !important;
                box-sizing: border-box !important;
                overflow: visible !important;
                max-width: none !important;
            }

            .hall-ticket h1 {
                font-size: 24px !important;
                margin-bottom: 10px !important;
                text-align: center !important;
            }

            .hall-ticket h3 {
                font-size: 18px !important;
                margin: 8px 0 10px 0 !important;
                text-align: center !important;
            }

            .hall-ticket table {
                width: 100% !important;
                border-collapse: collapse !important;
                table-layout: fixed !important;
                font-size: 11px !important;
            }

            .hall-ticket th,
            .hall-ticket td {
                border: 1px solid #000 !important;
                padding: 5px 6px !important;
                vertical-align: middle !important;
                word-break: break-word !important;
                overflow-wrap: anywhere !important;
                white-space: normal !important;
            }

            .hall-ticket tr, .hall-ticket td, .hall-ticket th {
                page-break-inside: avoid !important;
            }

            .hall-ticket > div {
                margin-bottom: 10px !important;
            }
        }
        
        @media screen and (max-width: 768px) {
            .hall-ticket {
                padding: 14px !important;
                margin: 8px !important;
            }
            .ticket-row {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 5px !important;
            }
            table {
                font-size: 11px !important;
            }
            th, td {
                padding: 6px 4px !important;
            }
            h2 {
                font-size: 22px !important;
            }
            h3 {
                font-size: 17px !important;
            }
        }
        
        @media screen and (max-width: 480px) {
            .hall-ticket {
                padding: 10px !important;
            }
            table {
                font-size: 9px !important;
            }
            th, td {
                padding: 5px 2px !important;
            }
        }
        
        /* Additional styling for better UX */
        .hall-ticket {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .ticket-row:hover {
            transform: translateX(5px);
            transition: transform 0.3s ease;
        }
        
        table tr:hover {
            background-color: #e6f3ff !important;
            transition: background-color 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3>Smart Exam</h3>
                <p>Student Panel</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="my_exams.php">My Exams</a></li>
                <li><a href="hall_ticket.php" class="active">Hall Ticket</a></li>
                <li><a href="seat_allocation.php">Seat Allocation</a></li>
                <li><a href="logout.php" class="logout-link">Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="page-header">
                <h1>Hall Ticket</h1>
                <p>View and print your consolidated hall ticket for all exams</p>
            </div>

            <!-- Hall Ticket Display -->
            <?php if ($tickets && $tickets->num_rows > 0): ?>
            <div class="content-box">
                <div style="text-align:center; margin-bottom: 16px;" id="open-ticket-wrap">
                    <button type="button" id="open-ticket-btn" class="open-ticket-btn btn btn-info">Hall Ticket</button>
                </div>
                <div class="hall-ticket" id="hall-ticket-card" style="display:none; max-width: 980px; margin: 0 auto; padding: 22px; border: 2px solid #2b6cb0; border-radius: 14px; background: #fff; box-shadow: 0 12px 30px rgba(0,0,0,0.08);">
                    <div style="text-align: center; margin-bottom: 28px;">
                        <h1 style="margin: 0; font-size: 28px; color: #1a365d; letter-spacing: 1px;">EXAMINATION HALL TICKET</h1>
                    </div>

                    <div style="background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e0; margin-bottom: 20px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #2d3748;">
                            <tbody>
                                <tr>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700; width: 180px;">Student Name</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($student['name'] ?? ''); ?></td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700; width: 140px;">Register No</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($student['register_no'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700;">Roll Number</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($student['roll_no'] ?? $student['register_no'] ?? ''); ?></td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700;">Department</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($student['department'] ?? ''); ?></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700;">Year</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;">Year <?php echo htmlspecialchars($student['year'] ?? ''); ?></td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0; font-weight: 700;">Course</td>
                                    <td style="padding: 12px 14px; border: 1px solid #e2e8f0;"><?php echo htmlspecialchars($student['department'] ?? ''); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="background: #fff; padding: 16px; border-radius: 12px; border: 1px solid #cbd5e0; margin-bottom: 20px;">
                        <h3 style="font-size: 18px; color: #1a365d; margin-bottom: 14px; text-align: center;">Exam Schedule</h3>
                        <div style="overflow-x: auto;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 12px; color: #2d3748;">
                                <thead>
                                    <tr style="background: #2b6cb0; color: white;">
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: left;">Sr No</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: left;">Course Code</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: left;">Subject Code</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: left;">Subject Name</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Pattern</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Exam Date</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Exam Time</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Hall Name</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Hall No</th>
                                        <th style="padding: 14px 10px; border: 1px solid #e2e8f0; text-align: center;">Seat No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $row_count = 0;
                                    while ($ticket = $tickets->fetch_assoc()):
                                        $row_count++;
                                        $row_bg = $row_count % 2 == 0 ? '#f8fafc' : '#fff';
                                    ?>
                                    <tr style="background: <?php echo $row_bg; ?>;">
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px;"><?php echo $row_count; ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px;"><?php echo htmlspecialchars($ticket['exam_name']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px;"><?php echo htmlspecialchars($ticket['subject_code']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px;"><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center;"><?php echo htmlspecialchars($ticket['session']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center;"><?php echo htmlspecialchars($ticket['exam_date']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center;"><?php echo htmlspecialchars($ticket['exam_time']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center;"><?php echo htmlspecialchars($ticket['hall_name']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center;"><?php echo htmlspecialchars($ticket['hall_no']); ?></td>
                                        <td style="border: 1px solid #e2e8f0; padding: 12px 10px; text-align: center; font-weight: 700;"><?php echo htmlspecialchars($ticket['seat_number']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px;">
                        <div style="flex: 1; min-width: 240px; background: #f8fafc; border: 1px solid #cbd5e0; border-radius: 12px; padding: 16px;">
                            <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: #2b6cb0;">Important Instructions to student:</p>
                            <ol style="margin: 0; padding-left: 16px; color: #4a5568; font-size: 12px; line-height: 1.6;">
                                <li>Examination Hall Ticket will be considered valid only if signed by Competent Authority.</li>
                                <li>Possession of papers, books, notes of any kind, use of mobile phones or any attempt to assist or get assistance from any other student is strictly prohibited.</li>
                                <li>Student should occupy their seats before 10 minutes of Examination.</li>
                            </ol>
                            <p style="margin: 10px 0 0 0; font-size: 11px; color: #c53030;"><strong>Note:</strong> Kindly confirm date and time from exam timetable issued by the university.</p>
                        </div>
                        <div style="flex: 0 0 180px; min-width: 170px; background: #fff; border: 1px solid #cbd5e0; border-radius: 12px; padding: 16px; text-align: center;">
                            <p style="margin: 0 0 20px 0; font-size: 12px; color: #4a5568;">Controller of Examinations</p>
                            <div style="width: 100%; height: 48px; border-top: 2px dashed #a0aec0; margin-top: 18px;"></div>
                        </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; align-items: center;">
                        <div style="font-size: 12px; color: #718096;">Generated on: <?php echo date('H:i d/m/Y'); ?></div>
                        <button onclick="window.print()" class="print-ticket-btn" style="background: linear-gradient(135deg, #2b6cb0 0%, #4299e1 100%); color: white; border: none; padding: 10px 18px; font-size: 13px; font-weight: 700; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 10px rgba(43, 108, 176, 0.25); transition: transform 0.2s ease;">Print Hall Ticket</button>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info" style="text-align: center; font-size: 18px; padding: 20px;">No hall tickets available. Seats may not be allocated yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    (function () {
        var originalTitle = document.title;
        var openButton = document.getElementById('open-ticket-btn');
        var openWrap = document.getElementById('open-ticket-wrap');
        var hallTicketCard = document.getElementById('hall-ticket-card');

        if (openButton && hallTicketCard) {
            openButton.addEventListener('click', function () {
                hallTicketCard.style.display = 'block';
                if (openWrap) {
                    openWrap.style.display = 'none';
                }
            });
        }

        window.addEventListener('beforeprint', function () {
            document.title = '';
        });
        window.addEventListener('afterprint', function () {
            document.title = originalTitle;
        });
    })();
    </script>
</body>
</html>

