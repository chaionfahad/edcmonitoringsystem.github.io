<?php
// Database configuration
// Copy this file to database.php and update with your credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'edc_monitoring');
define('DB_USER', 'root');
define('DB_PASS', 'your_password_here');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'EDC Monitoring System');
define('TIMEZONE', 'Asia/Dhaka');

date_default_timezone_set(TIMEZONE);
