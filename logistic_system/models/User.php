<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password_hash;
    public $email;
    public $role;
    public $company_name;
    public $phone;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Verifică dacă username există
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Verifică dacă email există
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Obține user by username
    public function getUserByUsername($username) {
        $query = "SELECT id, username, password_hash, email, role, company_name, phone, is_active 
                  FROM " . $this->table_name . " 
                  WHERE username = :username AND is_active = 1 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Creare user nou
    public function createUser($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                     (username, password_hash, email, role, company_name, phone) 
                      VALUES 
                     (:username, :password_hash, :email, :role, :company_name, :phone)";
            
            $stmt = $this->conn->prepare($query);
            
            // Hash parolă
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Bind parameters
            $stmt->bindParam(":username", $data['username']);
            $stmt->bindParam(":password_hash", $password_hash);
            $stmt->bindParam(":email", $data['email']);
            $stmt->bindParam(":role", $data['role']);
            $stmt->bindParam(":company_name", $data['company_name']);
            $stmt->bindParam(":phone", $data['phone']);
            
            if ($stmt->execute()) {
                return [
                    "success" => true, 
                    "message" => "Utilizator creat cu succes!",
                    "user_id" => $this->conn->lastInsertId()
                ];
            } else {
                return [
                    "success" => false, 
                    "message" => "Eroare la crearea utilizatorului"
                ];
            }
            
        } catch (PDOException $exception) {
            return [
                "success" => false, 
                "message" => "Eroare database: " . $exception->getMessage()
            ];
        }
    }

    // Obține toți transportatorii activi
    public function getTransportatoriActivi() {
        $query = "SELECT id, username, company_name, email, phone 
                  FROM " . $this->table_name . " 
                  WHERE role = 'transportator' AND is_active = 1 
                  ORDER BY company_name";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>




