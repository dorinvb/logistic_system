<?php
class Database {
    private $host = "localhost";
    private $db_name = "logistic_system";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            echo "Eroare conexiune: " . $this->safeError($exception->getMessage());
        }
        return $this->conn;
    }

    private function safeError($error) {
        // Ascunde detalii sensibile în production
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') === false) {
            return "Eroare de conexiune. Contactează administratorul.";
        }
        return $error;
    }
}
?>