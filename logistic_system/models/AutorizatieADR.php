<?php
class AutorizatieADR {
    private $conn;
    private $table_name = "autorizatii_adr";

    public $id;
    public $transportator_id;
    public $numar_autorizatie;
    public $data_emitere;
    public $data_expirare;
    public $poza_autorizatie;
    public $este_valabila;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Creare/autorizare ADR nouă
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (transportator_id, numar_autorizatie, data_emitere, data_expirare, poza_autorizatie) 
                  VALUES 
                 (:transportator_id, :numar_autorizatie, :data_emitere, :data_expirare, :poza_autorizatie)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":transportator_id", $data['transportator_id']);
        $stmt->bindParam(":numar_autorizatie", $data['numar_autorizatie']);
        $stmt->bindParam(":data_emitere", $data['data_emitere']);
        $stmt->bindParam(":data_expirare", $data['data_expirare']);
        $stmt->bindParam(":poza_autorizatie", $data['poza_autorizatie']);
        
        if ($stmt->execute()) {
            return [
                "success" => true, 
                "message" => "Autorizația ADR a fost salvată cu succes!",
                "autorizatie_id" => $this->conn->lastInsertId()
            ];
        } else {
            return [
                "success" => false, 
                "message" => "Eroare la salvarea autorizației ADR"
            ];
        }
    }

    // Verifică dacă transportatorul are autorizație ADR validă
    public function areAutorizatieValabila($transportator_id) {
        $query = "SELECT id, data_expirare 
                  FROM " . $this->table_name . " 
                  WHERE transportator_id = :transportator_id 
                  AND este_valabila = 1 
                  AND data_expirare >= CURDATE() 
                  ORDER BY data_expirare DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'are_autorizatie' => true,
                'data_expirare' => $result['data_expirare'],
                'zile_ramase' => $this->getZilePanaLaExpirare($result['data_expirare'])
            ];
        }
        
        return ['are_autorizatie' => false];
    }

    // Obține autorizația curentă a transportatorului
    public function getAutorizatieCurenta($transportator_id) {
        $query = "SELECT * 
                  FROM " . $this->table_name . " 
                  WHERE transportator_id = :transportator_id 
                  AND este_valabila = 1 
                  ORDER BY data_expirare DESC 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Obține istoricul autorizațiilor unui transportator
    public function getIstoricAutorizatii($transportator_id) {
        $query = "SELECT * 
                  FROM " . $this->table_name . " 
                  WHERE transportator_id = :transportator_id 
                  ORDER BY data_expirare DESC, created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizează autorizația ADR
    public function updateAutorizatie($autorizatie_id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                 SET numar_autorizatie = :numar_autorizatie,
                     data_emitere = :data_emitere,
                     data_expirare = :data_expirare,
                     poza_autorizatie = :poza_autorizatie,
                     este_valabila = 1
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":numar_autorizatie", $data['numar_autorizatie']);
        $stmt->bindParam(":data_emitere", $data['data_emitere']);
        $stmt->bindParam(":data_expirare", $data['data_expirare']);
        $stmt->bindParam(":poza_autorizatie", $data['poza_autorizatie']);
        $stmt->bindParam(":id", $autorizatie_id);
        
        return $stmt->execute();
    }

    // Dezactivează autorizații expirate
    public function dezactiveazaAutorizatiiExpirate($transportator_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET este_valabila = 0 
                 WHERE transportator_id = :transportator_id 
                 AND data_expirare < CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        
        return $stmt->execute();
    }

    // Helper function pentru calcul zile până la expirare
    private function getZilePanaLaExpirare($data_expirare) {
        $today = new DateTime();
        $expirare = new DateTime($data_expirare);
        $diff = $today->diff($expirare);
        return $diff->days;
    }

    // Verifică dacă expediția necesită ADR
    public function expeditieNecesitaADR($expeditie_id) {
        $query = "SELECT necesita_adr FROM expedities WHERE id = :expeditie_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expeditie_id", $expeditie_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['necesita_adr'] == 1;
        }
        return false;
    }
}
?>