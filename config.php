<?php
// ============================================
// DATABASE CONNECTION FILE
// This file connects to MySQL using MySQLi
// Include this file in every PHP file that needs database access
// ============================================

// Enable error reporting for development (remove or disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials (change these if your WAMP settings are different)
$host = "localhost";       // WAMP default host
$username = "root";        // WAMP default username
$password = "";            // WAMP default password (empty)
$database = "smart_exam_db"; // Our database name

function initializeDatabaseSchema(string $host, string $username, string $password, string $database): void
{
    $serverConn = new mysqli($host, $username, $password);
    if ($serverConn->connect_error) {
        die("Connection failed: " . $serverConn->connect_error);
    }

    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        $serverConn->close();
        die("Database setup file not found: " . $sqlFile);
    }

    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        $serverConn->close();
        die("Unable to read database setup file.");
    }

    if (!$serverConn->multi_query($sql)) {
        $error = $serverConn->error;
        $serverConn->close();
        die("Database initialization failed: " . $error);
    }

    do {
        if ($result = $serverConn->store_result()) {
            $result->free();
        }
    } while ($serverConn->more_results() && $serverConn->next_result());

    if ($serverConn->error) {
        $error = $serverConn->error;
        $serverConn->close();
        die("Database initialization failed: " . $error);
    }

    $serverConn->close();
}

function requiredTablesMissing(mysqli $conn): bool
{
    $requiredTables = ['admin', 'students', 'supervisors', 'exams', 'exam_halls'];

    foreach ($requiredTables as $table) {
        $safeTable = $conn->real_escape_string($table);
        $result = $conn->query("SHOW TABLES LIKE '{$safeTable}'");

        if (!$result || $result->num_rows === 0) {
            if ($result instanceof mysqli_result) {
                $result->free();
            }

            return true;
        }

        $result->free();
    }

    return false;
}

function ensureSchemaUpdates(mysqli $conn): void
{
    $departmentColumn = $conn->query("SHOW COLUMNS FROM exams LIKE 'department'");
    if ($departmentColumn && $departmentColumn->num_rows === 0) {
        $conn->query("ALTER TABLE exams ADD COLUMN department VARCHAR(50) NOT NULL DEFAULT 'All' AFTER subject_code");
    }

    if ($departmentColumn instanceof mysqli_result) {
        $departmentColumn->free();
    }
}

mysqli_report(MYSQLI_REPORT_OFF);

// Create connection using MySQLi
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_errno === 1049) {
    initializeDatabaseSchema($host, $username, $password, $database);
    $conn = new mysqli($host, $username, $password, $database);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (requiredTablesMissing($conn)) {
    $conn->close();
    initializeDatabaseSchema($host, $username, $password, $database);
    $conn = new mysqli($host, $username, $password, $database);
}

ensureSchemaUpdates($conn);
?>