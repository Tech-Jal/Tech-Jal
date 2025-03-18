<?php
// Site configuration
define('SITE_NAME', 'FoodFusion');
define('SITE_URL', 'http://localhost'); // Change this in production
define('SITE_EMAIL', 'contact@foodfusion.com');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'foodfusion');
define('DB_USER', 'root');
define('DB_PASS', '');

// Security configuration
define('CSRF_EXPIRATION', 3600); // 1 hour
define('SESSION_EXPIRATION', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_TIME', 180); // 3 minutes
define('PASSWORD_MIN_LENGTH', 8);

// File upload configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('MAX_IMAGE_WIDTH', 2000);
define('MAX_IMAGE_HEIGHT', 2000);

// Recipe configuration
define('RECIPES_PER_PAGE', 12);
define('FEATURED_RECIPES_COUNT', 6);
define('RECENT_RECIPES_COUNT', 8);

// User configuration
define('FEATURED_CHEFS_COUNT', 4);
define('DEFAULT_AVATAR', 'default-avatar.png');

// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@example.com');
define('SMTP_PASSWORD', 'your-password');
define('SMTP_ENCRYPTION', 'tls');

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DURATION', 3600); // 1 hour

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', SESSION_EXPIRATION);

// Create required directories if they don't exist
$directories = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../uploads/recipes',
    __DIR__ . '/../uploads/profiles',
    __DIR__ . '/../logs',
    __DIR__ . '/../cache'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set default timezone
date_default_timezone_set('UTC');

// Load environment-specific configuration
$env_config = __DIR__ . '/config.' . (getenv('APP_ENV') ?: 'local') . '.php';
if (file_exists($env_config)) {
    require_once $env_config;
}

// Initialize error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $message = date('Y-m-d H:i:s') . " - Error [$errno]: $errstr in $errfile on line $errline\n";
    error_log($message, 3, __DIR__ . '/../logs/error.log');
    
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div style='color:red;'><strong>Error:</strong> $errstr</div>";
    }
    
    return true;
}
set_error_handler('customErrorHandler');

// Initialize exception handler
function customExceptionHandler($exception) {
    $message = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . 
              " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($message, 3, __DIR__ . '/../logs/error.log');
    
    if (defined('DEVELOPMENT_MODE') && DEVELOPMENT_MODE) {
        echo "<div style='color:red;'><strong>Exception:</strong> " . $exception->getMessage() . "</div>";
    } else {
        echo "<div style='color:red;'>An error occurred. Please try again later.</div>";
    }
}
set_exception_handler('customExceptionHandler');

// Function to clean up expired sessions
function cleanupSessions() {
    $session_files = glob(session_save_path() . '/*');
    $now = time();
    
    foreach ($session_files as $file) {
        if (is_file($file) && ($now - filemtime($file) > SESSION_EXPIRATION)) {
            @unlink($file);
        }
    }
}

// Register shutdown function
register_shutdown_function(function() {
    // Cleanup sessions periodically (1% chance on each request)
    if (mt_rand(1, 100) === 1) {
        cleanupSessions();
    }
}); 