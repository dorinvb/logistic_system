<?php
class Expeditie {
    private $conn;
    private $table_name = "expedities";

    public $id;
    public $nume_client;
    public $destinatie;
    public $produs;
    public $tarif_tinta;
    public $moneda;
    public $data_transport;
    public $status;
    public $data_creare;
    public $created_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Creare expediție nouă
public function create($data) {
    $query = "INSERT INTO " . $this->table_name . " 
             (nume_client, destinatie, produs, necesita_adr, tarif_tinta, moneda, data_transport, created_by) 
              VALUES 
             (:nume_client, :destinatie, :produs, :necesita_adr, :tarif_tinta, :moneda, :data_transport, :created_by)";
    
    $stmt = $this->conn->prepare($query);
    
    $stmt->bindParam(":nume_client", $data['nume_client']);
    $stmt->bindParam(":destinatie", $data['destinatie']);
    $stmt->bindParam(":produs", $data['produs']);
    $stmt->bindParam(":necesita_adr", $data['necesita_adr']);
    $stmt->bindParam(":tarif_tinta", $data['tarif_tinta']);
    $stmt->bindParam(":moneda", $data['moneda']);
    $stmt->bindParam(":data_transport", $data['data_transport']);
    $stmt->bindParam(":created_by", $data['created_by']);
    
    if ($stmt->execute()) {
        return [
            "success" => true, 
            "message" => "Expediție creată cu succes!",
            "expeditie_id" => $this->conn->lastInsertId()
        ];
    } else {
        return [
            "success" => false, 
            "message" => "Eroare la crearea expediției"
        ];
    }
}

    // Obține toate expedițiile unui manager
    public function getExpeditiiByManager($manager_id) {
        $query = "SELECT e.*, 
                         l.id as licitatie_id,
                         l.data_start,
                         l.termen_ore,
                         l.data_finalizare,
                         (SELECT COUNT(*) FROM oferte o WHERE o.licitatie_id = l.id) as numar_oferte,
                         a.pret_final,
                         a.numar_camion,
                         a.nume_sofer,
                         u.company_name as transportator_company
                  FROM " . $this->table_name . " e
                  LEFT JOIN licitatii l ON e.id = l.expeditie_id AND l.status = 'active'
                  LEFT JOIN atribuiri a ON e.id = a.expeditie_id
                  LEFT JOIN users u ON a.transportator_id = u.id
                  WHERE e.created_by = :manager_id 
                  ORDER BY e.data_transport DESC, e.data_creare DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":manager_id", $manager_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obține expediții disponibile pentru licitație
    public function getExpeditiiDisponibile($manager_id) {
        $query = "SELECT e.* 
                  FROM " . $this->table_name . " e
                  WHERE e.created_by = :manager_id 
                  AND e.status = 'planificata'
                  AND e.id NOT IN (
                      SELECT l.expeditie_id 
                      FROM licitatii l 
                      WHERE l.status = 'active'
                  )
                  ORDER BY e.data_transport ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":manager_id", $manager_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Actualizează statusul expediției
    public function updateStatus($expeditie_id, $status) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = :status 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $expeditie_id);
        
        return $stmt->execute();
    }

    // Obține expediție by ID
    public function getExpeditieById($expeditie_id) {
        $query = "SELECT e.*, u.username as manager_name 
                  FROM " . $this->table_name . " e
                  LEFT JOIN users u ON e.created_by = u.id
                  WHERE e.id = :expeditie_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expeditie_id", $expeditie_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }
    // Obține toate expedițiile cu licitații active și ofertele lor
public function getExpeditiiCuOferte($manager_id) {
    $query = "SELECT e.*, 
                     l.id as licitatie_id,
                     l.data_start,
                     l.termen_ore,
                     l.data_finalizare,
                     (SELECT COUNT(*) FROM oferte o WHERE o.licitatie_id = l.id) as numar_oferte,
                     (SELECT MIN(pret_oferit) FROM oferte o WHERE o.licitatie_id = l.id) as oferta_minima,
                     (SELECT MAX(pret_oferit) FROM oferte o WHERE o.licitatie_id = l.id) as oferta_maxima
              FROM " . $this->table_name . " e
              LEFT JOIN licitatii l ON e.id = l.expeditie_id AND l.status = 'active'
              WHERE e.created_by = :manager_id 
              AND e.status = 'in_licitatie'
              ORDER BY e.data_transport ASC, e.data_creare DESC";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":manager_id", $manager_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obține detalii complete pentru o expediție cu toate ofertele
public function getExpeditieCuOferte($expeditie_id, $manager_id) {
    $query = "SELECT e.*, 
                     l.id as licitatie_id,
                     l.data_start,
                     l.termen_ore,
                     l.data_finalizare,
                     a.id as atribuire_id,
                     a.pret_final,
                     a.moneda,
                     a.numar_camion,
                     a.numar_remorca,
                     a.nume_sofer,
                     a.prenume_sofer,
                     a.tip_document,
                     a.numar_document,
                     a.status as status_atribuire,
                     a.adr_verificat,
                     u.company_name as transportator_company
              FROM " . $this->table_name . " e
              LEFT JOIN licitatii l ON e.id = l.expeditie_id
              LEFT JOIN atribuiri a ON e.id = a.expeditie_id
              LEFT JOIN users u ON a.transportator_id = u.id
              WHERE e.id = :expeditie_id 
              AND e.created_by = :manager_id";
    
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":expeditie_id", $expeditie_id);
    $stmt->bindParam(":manager_id", $manager_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}
}
?>