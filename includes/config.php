<?php
// Disable error display in production (logs errors instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Set strict timezone
date_default_timezone_set('UTC'); // Change to your local timezone (e.g., 'America/New_York')

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>