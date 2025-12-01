<?php
class Oferta {
    private $conn;
    private $table_name = "oferte";

    public $id;
    public $licitatie_id;
    public $transportator_id;
    public $pret_oferit;
    public $moneda;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Creare ofertă nouă
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (licitatie_id, transportator_id, pret_oferit, moneda) 
                  VALUES 
                 (:licitatie_id, :transportator_id, :pret_oferit, :moneda)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":licitatie_id", $data['licitatie_id']);
        $stmt->bindParam(":transportator_id", $data['transportator_id']);
        $stmt->bindParam(":pret_oferit", $data['pret_oferit']);
        $stmt->bindParam(":moneda", $data['moneda']);
        
        return $stmt->execute();
    }

    // Obține ofertele pentru o licitație
    public function getOferteByLicitatie($licitatie_id) {
        $query = "SELECT o.*, u.company_name 
                  FROM " . $this->table_name . " o
                  INNER JOIN users u ON o.transportator_id = u.id
                  WHERE o.licitatie_id = :licitatie_id 
                  ORDER BY o.pret_oferit ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":licitatie_id", $licitatie_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obține ofertele unui transportator
    public function getOferteByTransportator($transportator_id) {
        $query = "SELECT o.*, l.id as licitatie_id, e.nume_client, e.destinatie
                  FROM " . $this->table_name . " o
                  INNER JOIN licitatii l ON o.licitatie_id = l.id
                  INNER JOIN expedities e ON l.expeditie_id = e.id
                  WHERE o.transportator_id = :transportator_id 
                  ORDER BY o.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizează statusul unei oferte
    public function updateStatus($oferta_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $oferta_id);
        
        return $stmt->execute();
    }

    // Verifică dacă transportatorul a oferit deja la o licitație
    public function hasTransportatorOferit($licitatie_id, $transportator_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE licitatie_id = :licitatie_id AND transportator_id = :transportator_id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":licitatie_id", $licitatie_id);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>