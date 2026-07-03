<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/security.php';

class Auth {
    const SESSION_TIMEOUT = 3600;
    
    public static function isLoggedIn() {
        if (empty($_SESSION['admin_logged_in'])) return false;
        if (isset($_SESSION['admin_login_time'])) {
            if (time() - $_SESSION['admin_login_time'] > self::SESSION_TIMEOUT) {
                self::logout();
                return false;
            }
            $_SESSION['admin_login_time'] = time();
        }
        return true;
    }
    
    public static function login($username, $password) {
        if ($username !== ADMIN_USERNAME) return false;
        if (!ADMIN_PASSWORD_HASH) return false;
        if (!Security::verifyPassword($password, ADMIN_PASSWORD_HASH)) return false;
        
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_username'] = $username;
        return true;
    }
    
    public static function logout() {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_login_time']);
        unset($_SESSION['admin_username']);
        session_destroy();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_PATH . '/admin/login.php');
            exit();
        }
    }
}
