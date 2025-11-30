<?php
class Licitatie {
    private $conn;
    private $table_name = "licitatii";

    public $id;
    public $expeditie_id;
    public $status;
    public $data_start;
    public $data_finalizare;
    public $termen_ore;
    public $oferta_castigatoare_id;
    public $motiv_justificare;
    public $created_by;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Creare licitație nouă
    public function create($expeditie_id, $created_by) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (expeditie_id, status, created_by) 
                  VALUES 
                 (:expeditie_id, 'active', :created_by)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expeditie_id", $expeditie_id);
        $stmt->bindParam(":created_by", $created_by);
        
        return $stmt->execute();
    }

    // Pornire licitație
    public function startLicitatie($licitatie_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'active', data_start = NOW() 
                 WHERE id = :id AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $licitatie_id);
        
        return $stmt->execute();
    }

    // Finalizare licitație
    public function finalizeazaLicitatie($licitatie_id, $oferta_castigatoare_id = null, $motiv_justificare = '') {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'finalizata', 
                     data_finalizare = NOW(),
                     oferta_castigatoare_id = :oferta_castigatoare_id,
                     motiv_justificare = :motiv_justificare
                 WHERE id = :id AND status = 'active'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $licitatie_id);
        $stmt->bindParam(":oferta_castigatoare_id", $oferta_castigatoare_id);
        $stmt->bindParam(":motiv_justificare", $motiv_justificare);
        
        return $stmt->execute();
    }

    // Obține licitații active
    public function getLicitatiiActive() {
        $query = "SELECT l.*, 
                     e.nume_client, 
                     e.destinatie as loc_descarcare, 
                     'Târgu Mureș' as loc_incarcare, 
                     e.produs,
                     u.company_name as manager_company
              FROM " . $this->table_name . " l
              INNER JOIN expedities e ON l.expeditie_id = e.id
              INNER JOIN users u ON l.created_by = u.id
              WHERE l.status = 'active'
              ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obține licitații pentru manager
    public function getLicitatiiByManager($manager_id) {
        $query = "SELECT l.*, 
                     e.nume_client, 
                     e.destinatie as loc_descarcare, 
                     'Târgu Mureș' as loc_incarcare, 
                     e.produs,
                     COUNT(o.id) as numar_oferte
              FROM " . $this->table_name . " l
              INNER JOIN expedities e ON l.expeditie_id = e.id
              LEFT JOIN oferte o ON l.id = o.licitatie_id
              WHERE l.created_by = :manager_id
              GROUP BY l.id
              ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":manager_id", $manager_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obține licitație by ID
    public function getLicitatieById($licitatie_id) {
        $query = "SELECT l.*, 
                     e.nume_client, 
                     e.destinatie, 
                     e.produs,
                     e.tarif_tinta,
                     e.moneda
              FROM " . $this->table_name . " l
              INNER JOIN expedities e ON l.expeditie_id = e.id
              WHERE l.id = :licitatie_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":licitatie_id", $licitatie_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
}
?>