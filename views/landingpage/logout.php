<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to landing page
header('Location: ../landingpage/landingpage.php'); // Sesuaikan dengan path yang benar
exit;
?>