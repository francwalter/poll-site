<?php
class Security {
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        if (empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateName($name) {
        // Allow only letters and digits (Unicode aware), no spaces or symbols.
        return preg_match('/^[\\p{L}\\p{N}]+$/u', $name) === 1;
    }
    
    public static function generateUnsubscribeToken() {
        return bin2hex(random_bytes(32));
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}
