<?php
session_start();
session_unset();
session_destroy();

// Redirect ke login dengan query string
header("Location: login.php?message=Anda+telah+logout");
exit;
?>
