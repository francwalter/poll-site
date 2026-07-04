<?php
function load_env_file($file = '.env') {
    if (!file_exists($file)) return;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            putenv("$key=$value");
        }
    }
}

load_env_file(__DIR__ . '/../.env');

define('APP_ROOT', dirname(__DIR__));

// Auto-detect BASE_PATH for subdirectory installations
// Set BASE_PATH in .env to override (e.g., BASE_PATH=/poll-site)
if (!defined('BASE_PATH')) {
    $basePath = getenv('BASE_PATH');
    if (!$basePath) {
        // Auto-detect: get the directory between domain and current script
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        // Normalize slashes
        $scriptPath = str_replace('\\', '/', $scriptPath);
        // Remove trailing slash if present
        $basePath = rtrim($scriptPath, '/');
        // If the path ends with /includes, /admin, or /api, strip that subdirectory to get the project root
        if (preg_match('#/(includes|admin|api)$#', $basePath, $matches)) {
            $basePath = substr($basePath, 0, -strlen($matches[0]));
        }
    }
    define('BASE_PATH', $basePath ?: '');
}

$dbPath = getenv('DB_PATH') ?: APP_ROOT . '/data/poll.db';
if (substr($dbPath, 0, 1) === '.' || (strpos($dbPath, '/') !== 0 && strpos($dbPath, ':') !== 1)) {
    $dbPath = APP_ROOT . '/' . ltrim($dbPath, './\\');
}
define('DB_PATH', $dbPath);
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');

function getAdminPasswordHash() {
    load_env_file(__DIR__ . '/../.env');
    return getenv('ADMIN_PASSWORD_HASH');
}

// Email Configuration
define('EMAIL_TYPE', getenv('EMAIL_TYPE') ?: 'sendmail');
define('SMTP_ENABLED', getenv('SMTP_ENABLED') === 'true');
define('SMTP_FROM', getenv('SMTP_FROM'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Poll Site');

// SMTP Provider settings (only used if EMAIL_TYPE=smtp)
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));

// Site Configuration
define('SITE_TITLE', getenv('SITE_TITLE') ?: 'Poll Site');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/poll');
define('DEBUG', getenv('DEBUG') === 'true');

ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Strict');

if (!session_id()) {
    session_start();
}