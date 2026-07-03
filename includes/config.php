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
define('DB_PATH', getenv('DB_PATH') ?: APP_ROOT . '/data/poll.db');
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: 'admin');
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH'));
define('SMTP_ENABLED', getenv('SMTP_ENABLED') === 'true');
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_FROM', getenv('SMTP_FROM'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Poll Site');
define('SITE_TITLE', getenv('SITE_TITLE') ?: 'Poll Site');
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/poll');
define('DEBUG', getenv('DEBUG') === 'true');

ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Strict');

if (!session_id()) {
    session_start();
}