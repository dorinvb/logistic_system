<?php
class Security {
    
    // Sanitizare input
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }

    // Validare email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    // Validare parolă (minim 6 caractere)
    public static function validatePassword($password) {
        return strlen($password) >= 6;
    }

    // Generare token CSRF
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Verificare CSRF token
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    // Redirect securizat
    public static function redirect($url) {
        header("Location: " . $url);
        exit();
    }

    // Verificare dacă user este autentificat
    public static function checkAuth($required_role = null) {
        if (!isset($_SESSION['user_id'])) {
            self::redirect('login.php');
        }
        
        if ($required_role && $_SESSION['role'] !== $required_role) {
            $_SESSION['error'] = "Nu ai permisiunea de a accesa această pagină";
            self::redirect('dashboard.php');
        }
        
        return true;
    }
}
?>