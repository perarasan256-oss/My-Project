-- ============================================
-- SMART EXAM SEAT ALLOCATION - Database Setup
-- Run this SQL in phpMyAdmin on your WAMP server
-- This script recreates the database to avoid schema mismatches from older imports.
-- ============================================

-- Step 1: Recreate the database
DROP DATABASE IF EXISTS smart_exam_db;
CREATE DATABASE smart_exam_db;
USE smart_exam_db;

-- ============================================
-- Step 2: Create Tables
-- ============================================

-- Admin table: stores admin login credentials
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Students table: stores student details
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    register_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    parent_phone VARCHAR(20) DEFAULT '',
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- Supervisors table: stores supervisor details
CREATE TABLE IF NOT EXISTS supervisors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    assigned_hall INT DEFAULT NULL
) ENGINE=InnoDB;

-- Exams table: stores exam details
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(100) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    department VARCHAR(50) NOT NULL DEFAULT 'All',
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    session VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

-- Exam halls table: stores hall details
CREATE TABLE IF NOT EXISTS exam_halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hall_name VARCHAR(100) NOT NULL,
    hall_no VARCHAR(20) NOT NULL,
    total_seats INT NOT NULL
) ENGINE=InnoDB;

-- Seat allocation table: links students to seats in halls for exams
CREATE TABLE IF NOT EXISTS seat_allocation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    hall_id INT NOT NULL,
    seat_number INT NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES exam_halls(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Attendance table: tracks student attendance during exams
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    hall_id INT NOT NULL,
    status ENUM('Present', 'Absent') DEFAULT 'Absent',
    marked_by INT DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES exam_halls(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES supervisors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Malpractice table: stores malpractice reports
CREATE TABLE IF NOT EXISTS malpractice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    hall_id INT NOT NULL,
    description TEXT NOT NULL,
    reported_by INT NOT NULL,
    report_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES exam_halls(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES supervisors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Step 3: Insert Sample Data
-- ============================================

-- Default admin (username: admin, password: admin123)
-- Sample passwords are stored in plain text so the login flow can upgrade them to hashes on first login.
INSERT INTO admin (full_name, username, password) VALUES ('System Administrator', 'admin', 'admin123');

-- Sample students (password = register number)
INSERT INTO students (register_no, name, department, year, phone, parent_phone, password) VALUES
('REG001', 'Rahul Kumar', 'Computer Science', 3, '9876543210', '9123456780', 'REG001'),
('REG002', 'Priya Sharma', 'Computer Science', 3, '9876543211', '9123456781', 'REG002'),
('REG003', 'Amit Singh', 'Electronics', 2, '9876543212', '9123456782', 'REG003'),
('REG004', 'Sneha Patel', 'Electronics', 2, '9876543213', '9123456783', 'REG004'),
('REG005', 'Vikram Reddy', 'Mechanical', 4, '9876543214', '9123456784', 'REG005'),
('REG006', 'Anita Desai', 'Mechanical', 4, '9876543215', '9123456785', 'REG006'),
('REG007', 'Karthik Nair', 'Civil', 1, '9876543216', '9123456786', 'REG007'),
('REG008', 'Meera Joshi', 'Civil', 1, '9876543217', '9123456787', 'REG008'),
('REG009', 'Suresh Iyer', 'Computer Science', 2, '9876543218', '9123456788', 'REG009'),
('REG010', 'Divya Menon', 'Electronics', 3, '9876543219', '9123456789', 'REG010');

-- Sample supervisors (password = username)
INSERT INTO supervisors (name, username, password, assigned_hall) VALUES
('Dr. Ramesh', 'ramesh', 'ramesh', NULL),
('Prof. Sunita', 'sunita', 'sunita', NULL),
('Dr. Venkat', 'venkat', 'venkat', NULL);

-- Sample exams
INSERT INTO exams (exam_name, subject, exam_date) VALUES
('Mid Semester', 'Data Structures', '2026-03-15'),
('Mid Semester', 'Digital Electronics', '2026-03-16'),
('Mid Semester', 'Engineering Mathematics', '2026-03-17'),
('End Semester', 'Database Management', '2026-04-20'),
('End Semester', 'Microprocessors', '2026-04-21');

-- Sample exam halls
INSERT INTO exam_halls (hall_name, total_seats) VALUES
('Hall A', 30),
('Hall B', 25),
('Hall C', 20);
