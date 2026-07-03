<?php
session_start();
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'de'])) {
        $_SESSION['lang'] = $lang;
    }
}
$referer = $_SERVER['HTTP_REFERER'] ?? BASE_PATH . '/';
header('Location: ' . $referer);
exit();
