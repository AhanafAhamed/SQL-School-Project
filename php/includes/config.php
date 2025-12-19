<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'library_db');

// App configuration
define('APP_NAME', 'Library Management System');

// Custom error handler to redirect errors to console
function console_error_handler($errno, $errstr, $errfile, $errline) {
    $type = 'log';
    switch ($errno) {
        case E_USER_ERROR:
        case E_ERROR:
        case E_CORE_ERROR:
        case E_COMPILE_ERROR:
            $type = 'error';
            break;
        case E_USER_WARNING:
        case E_WARNING:
        case E_CORE_WARNING:
        case E_COMPILE_WARNING:
            $type = 'warn';
            break;
        case E_USER_NOTICE:
        case E_NOTICE:
            $type = 'info';
            break;
        default:
            $type = 'log';
            break;
    }
    
    $message = "PHP $type: $errstr in $errfile on line $errline";
    $message = addslashes($message);
    echo "<script>console.$type(\"$message\");</script>";
    return true; // Don't execute PHP internal error handler
}

function console_exception_handler($exception) {
    $message = "PHP Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    $message = addslashes($message);
    echo "<script>console.error(\"$message\");</script>";
}

set_error_handler("console_error_handler");
set_exception_handler("console_exception_handler");
ini_set('display_errors', 0);
error_reporting(E_ALL);
?>
