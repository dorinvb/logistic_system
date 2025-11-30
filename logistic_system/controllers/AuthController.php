<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/security.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->userModel = new User($db);
    }

    public function login($username, $password) {
        // Validare input
        if (empty($username) || empty($password)) {
            return ["success" => false, "message" => "Completăți toate câmpurile"];
        }

        // Sanitizare
        $username = Security::sanitize($username);
        
        // Verificare user
        $user = $this->userModel->getUserByUsername($username);
        
        if (!$user) {
            return ["success" => false, "message" => "Utilizator sau parolă incorectă"];
        }

        // Verificare parolă
        if (password_verify($password, $user['password_hash'])) {
            if ($user['is_active']) {
                // Setare sesiune
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['company'] = $user['company_name'];
                
                // Regenerare ID sesiune pentru securitate
                session_regenerate_id(true);
                
                return ["success" => true, "role" => $user['role']];
            } else {
                return ["success" => false, "message" => "Cont dezactivat"];
            }
        } else {
            return ["success" => false, "message" => "Utilizator sau parolă incorectă"];
        }
    }

    public function register($data) {
        // Validare
        if (empty($data['username']) || empty($data['password']) || empty($data['email']) || empty($data['role'])) {
            return ["success" => false, "message" => "Completăți toate câmpurile obligatorii"];
        }

        // Verificare username unic
        if ($this->userModel->usernameExists($data['username'])) {
            return ["success" => false, "message" => "Username deja folosit"];
        }

        // Verificare email unic
        if ($this->userModel->emailExists($data['email'])) {
            return ["success" => false, "message" => "Email deja folosit"];
        }

        // Creare user
        $result = $this->userModel->createUser([
            'username' => Security::sanitize($data['username']),
            'password' => $data['password'],
            'email' => Security::sanitize($data['email']),
            'role' => $data['role'],
            'company_name' => Security::sanitize($data['company_name'] ?? ''),
            'phone' => Security::sanitize($data['phone'] ?? '')
        ]);

        return $result;
    }

    public function logout() {
        // Ștergere sesiune
        $_SESSION = array();
        
        // Ștergere cookie sesiune
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        header("Location: login.php");
        exit;
    }
}
?>