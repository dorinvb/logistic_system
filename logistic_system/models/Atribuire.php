<?php
class Atribuire {
    private $conn;
    private $table_name = "atribuiri";

    public $id;
    public $expeditie_id;
    public $licitatie_id;
    public $transportator_id;
    public $pret_final;
    public $moneda;
    public $numar_camion;
    public $numar_remorca;
    public $nume_sofer;
    public $prenume_sofer;
    public $tip_document;
    public $numar_document;
    public $data_atribuire;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Creare atribuire nouă
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (expeditie_id, licitatie_id, transportator_id, pret_final, moneda) 
                  VALUES 
                 (:expeditie_id, :licitatie_id, :transportator_id, :pret_final, :moneda)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":expeditie_id", $data['expeditie_id']);
        $stmt->bindParam(":licitatie_id", $data['licitatie_id']);
        $stmt->bindParam(":transportator_id", $data['transportator_id']);
        $stmt->bindParam(":pret_final", $data['pret_final']);
        $stmt->bindParam(":moneda", $data['moneda']);
        
        return $stmt->execute();
    }

    // Actualizare detalii camion/șofer
    public function updateDetaliiTransport($atribuire_id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                 SET numar_camion = :numar_camion,
                     numar_remorca = :numar_remorca,
                     nume_sofer = :nume_sofer,
                     prenume_sofer = :prenume_sofer,
                     tip_document = :tip_document,
                     numar_document = :numar_document,
                     status = 'confirmata'
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":numar_camion", $data['numar_camion']);
        $stmt->bindParam(":numar_remorca", $data['numar_remorca']);
        $stmt->bindParam(":nume_sofer", $data['nume_sofer']);
        $stmt->bindParam(":prenume_sofer", $data['prenume_sofer']);
        $stmt->bindParam(":tip_document", $data['tip_document']);
        $stmt->bindParam(":numar_document", $data['numar_document']);
        $stmt->bindParam(":id", $atribuire_id);
        
        return $stmt->execute();
    }

    // Obține atribuire by expediție
    public function getAtribuireByExpeditie($expeditie_id) {
        $query = "SELECT a.*, u.company_name as transportator_company
                  FROM " . $this->table_name . " a
                  INNER JOIN users u ON a.transportator_id = u.id
                  WHERE a.expeditie_id = :expeditie_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expeditie_id", $expeditie_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Obține atribuirile unui transportator
    public function getAtribuiriByTransportator($transportator_id) {
        $query = "SELECT a.*, e.nume_client, e.destinatie, e.produs, e.data_transport
                  FROM " . $this->table_name . " a
                  INNER JOIN expedities e ON a.expeditie_id = e.id
                  WHERE a.transportator_id = :transportator_id
                  ORDER BY a.data_atribuire DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transportator_id", $transportator_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Verifică dacă expediția este deja atribuită
    public function isExpeditieAtribuita($expeditie_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE expeditie_id = :expeditie_id AND status = 'confirmata' 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":expeditie_id", $expeditie_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
}
?>