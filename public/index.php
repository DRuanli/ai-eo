<?php
/**
 * Main Entry Point
 * Initializes the application
 */

// Load Configuration
require_once '../app/config/config.php';

// Load Helpers
require_once '../app/helpers/session_helper.php';
require_once '../app/helpers/validation_helper.php';

// Start Session
session_start();

// Check Session Timeout
checkSessionTimeout();

// Autoload Core Classes
spl_autoload_register(function($className) {
    if(file_exists('../app/core/' . $className . '.php')) {
        require_once '../app/core/' . $className . '.php';
    }
});

// Initialize App
$init = new App();