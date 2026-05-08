// ============================================
// SMART EXAM SEAT ALLOCATION - Form Validation
// This file contains JavaScript validation functions
// used across the application
// ============================================

/**
 * Validate the login form
 * Checks that username/register number and password are not empty
 * and that a role is selected
 */
function validateLoginForm() {
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value.trim();
    var roleEl = document.getElementById('role');
    var role = roleEl ? roleEl.value : '';

    // Check if username is empty
    if (username === '') {
        alert('Please enter your Username / Register Number.');
        document.getElementById('username').focus();
        return false;
    }

    // Check if password is empty
    if (password === '') {
        alert('Please enter your Password.');
        document.getElementById('password').focus();
        return false;
    }

    // Check if role is selected only on pages that still include the field
    if (roleEl && role === '') {
        alert('Please select your Role.');
        roleEl.focus();
        return false;
    }

    return true; // Form is valid
}

/**
 * Validate Add Student form
 * Checks all required fields are filled
 */
function validateStudentForm() {
    var regNo = document.getElementById('register_no').value.trim();
    var name = document.getElementById('name').value.trim();
    var department = document.getElementById('department').value.trim();
    var password = document.getElementById('password').value.trim();
    var actionField = document.querySelector('input[name="action"]');
    var action = actionField ? actionField.value : '';

    if (regNo === '') {
        alert('Please enter Register Number.');
        return false;
    }

    if (name === '') {
        alert('Please enter Student Name.');
        return false;
    }

    if (department === '') {

        return false;
    }

    if (password === '' && action !== 'update_student') {
        alert('Please enter Password.');
        return false;
    }

    if (password !== '' && password.length < 4) {
        alert('Password must be at least 4 characters.');
        return false;
    }

    return true;
}

/**
 * Validate Add Exam form
 */
function validateExamForm() {
    var examName = document.getElementById('exam_name').value.trim();
    var subject = document.getElementById('subject').value.trim();
    var subjectCode = document.getElementById('subject_code').value.trim();
    var examDate = document.getElementById('exam_date').value;
    var examTime = document.getElementById('exam_time').value;
    var session = document.getElementById('session').value;

    if (examName === '') {
        alert('Please enter Exam Name.');
        return false;
    }

    if (subject === '') {
        alert('Please enter Subject Name.');
        return false;
    }

    if (subjectCode === '') {
        alert('Please enter Subject Code.');
        return false;
    }

    if (examDate === '') {
        alert('Please select Exam Date.');
        return false;
    }

    if (examTime === '') {
        alert('Please select Exam Time.');
        return false;
    }

    if (session === '') {
        alert('Please select Session.');
        return false;
    }

    return true;
}

/**
 * Validate Add Exam Hall form
 */
function validateHallForm() {
    var hallName = document.getElementById('hall_name').value.trim();
    var hallNo = document.getElementById('hall_no').value.trim();
    var totalSeats = document.getElementById('total_seats').value;

    if (hallName === '') {
        alert('Please enter Hall Name.');
        return false;
    }

    if (hallNo === '') {
        alert('Please enter Hall Number.');
        return false;
    }

    if (totalSeats === '' || totalSeats < 1) {
        alert('Please enter a valid number of seats.');
        return false;
    }

    return true;
}

/**
 * Validate Add Supervisor form
 */
function validateSupervisorForm() {
    var name = document.getElementById('name').value.trim();
    var username = document.getElementById('username').value.trim();
    var password = document.getElementById('password').value.trim();
    var actionField = document.querySelector('input[name="action"]');
    var action = actionField ? actionField.value : '';

    if (name === '') {
        alert('Please enter Supervisor Name.');
        return false;
    }

    if (username === '') {
        alert('Please enter Username.');
        return false;
    }

    if (username.length < 3) {
        alert('Username must be at least 3 characters.');
        return false;
    }

    // Check for valid username characters (alphanumeric, underscore, dash, @)
    var usernameRegex = /^[a-zA-Z0-9_@-]+$/;
    if (!usernameRegex.test(username)) {
        alert('Username can only contain letters, numbers, underscores, dashes, and @.');
        return false;
    }

    if (password === '' && action !== 'update_supervisor') {
        alert('Please enter Password.');
        return false;
    }

    if (password !== '' && password.length < 4) {
        alert('Password must be at least 4 characters.');
        return false;
    }

    return true;
}

/**
 * Validate Malpractice Report form
 */
function validateMalpracticeForm() {
    var studentId = document.getElementById('student_id').value;
    var description = document.getElementById('description').value.trim();

    if (studentId === '') {
        alert('Please select a Student.');
        return false;
    }

    if (description === '') {
        alert('Please enter a description of the malpractice.');
        return false;
    }

    if (description.length < 10) {
        alert('Description must be at least 10 characters.');
        return false;
    }

    return true;
}

/**
 * Confirm before delete action
 */
function confirmDelete(item) {
    return confirm('Are you sure you want to delete this ' + item + '?');
}

/**
 * Confirm seat allocation
 */
function confirmAllocation() {
    return confirm('This will allocate seats for all students for the selected exam. Continue?');
}
