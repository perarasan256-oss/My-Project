<?php
// ============================================
// ADMIN LOGOUT
// Destroys session and redirects to login page
// ============================================

session_start();
session_unset();    // Remove all session variables
session_destroy();  // Destroy the session
header("Location: ../index.html");
exit();
?>
