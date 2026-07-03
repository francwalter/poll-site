<?php
function translate($key) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Determine language (e.g., from session or default)
    $lang = $_SESSION['lang'] ?? 'en';
    $file = __DIR__ . "/../locale/$lang.php";

    // Debugging output
    // error_log("Loading language file: " . $file);
    // error_log("Key requested: " . $key);

    // Always include the file to get the correct translations for the current session lang
    $translations = file_exists($file) ? include $file : [];
        return $translations[$key] ?? $key;
    }
