<?php
// ============================================
// STUDENT LOGOUT
// Destroys session and redirects to login page
// ============================================

session_start();
session_unset();
session_destroy();
header("Location: ../index.html");
exit();
?>
