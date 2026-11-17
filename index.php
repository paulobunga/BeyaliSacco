<?PHP
/**
 * BeyaliSacco Modernized Entry Point
 * Checks for database configuration and directs to appropriate page.
 */

// Check if configuration exists
if (file_exists('config/config.php')) {
    // Configuration exists, proceed to login page
    header('Location: login.php');
    exit;
} else {
    // No configuration, direct to setup
    header('Location: setup.php');
    exit;
}