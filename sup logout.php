<?php
// ============================================
// SUPERVISOR LOGOUT
// Destroys session and redirects to login page
// ============================================

session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="refresh" content="0; url=../index.html">
    <title>Logging out...</title>
</head>
<body>
    <p>Logging out... <a href="../index.html">Click here if not redirected</a></p>
</body>
</html>