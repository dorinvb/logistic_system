<?php
session_start();

// Verifică dacă userul este logat și este manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Expeditie.php';
require_once __DIR__ . '/../models/Licitatie.php';

$database = new Database();
$db = $database->getConnection();
$expeditieModel = new Expeditie($db);
$licitatieModel = new Licitatie($db);

// Obține toate expedițiile managerului
$expeditii = $expeditieModel->getExpeditiiByManager($_SESSION['user_id']);

// Preluare parametri filtrare
$filtru_client = $_GET['client'] ?? '';
$filtru_status = $_GET['status'] ?? '';
$filtru_data_start = $_GET['data_start'] ?? '';
$filtru_data_end = $_GET['data_end'] ?? '';

// Aplicare filtre
if (!empty($expeditii)) {
    $expeditii_filtrate = array_filter($expeditii, function($expeditie) use ($filtru_client, $filtru_status, $filtru_data_start, $filtru_data_end) {
        $match = true;
        
        // Filtru client
        if ($filtru_client && stripos($expeditie['nume_client'], $filtru_client) === false) {
            $match = false;
        }
        
        // Filtru status
        if ($filtru_status && $expeditie['status'] !== $filtru_status) {
            $match = false;
        }
        
        // Filtru data transport
        if ($filtru_data_start && strtotime($expeditie['data_transport']) < strtotime($filtru_data_start)) {
            $match = false;
        }
        if ($filtru_data_end && strtotime($expeditie['data_transport']) > strtotime($filtru_data_end)) {
            $match = false;
        }
        
        return $match;
    });
} else {
    $expeditii_filtrate = [];
}

// Obține lista unică de clienți pentru dropdown
$clienti_unici = [];
if (!empty($expeditii)) {
    $clienti_unici = array_unique(array_column($expeditii, 'nume_client'));
    sort($clienti_unici);
}

// Funcție pentru formatarea textului (prima literă mare, restul mic)
function formatText($text) {
    return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
}

// Funcție pentru calcularea timpului rămas
function getTimpRamas($data_start, $termen_ore = 24) {
    if (!$data_start) return '';
    
    $end_time = strtotime($data_start) + ($termen_ore * 3600);
    $now = time();
    $diff = $end_time - $now;
    
    if ($diff <= 0) return 'Expirat';
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    return $hours . 'h ' . $minutes . 'm';
}

// Funcție pentru obținerea ofertelor unei licitație
function getOferteLicitatie($db, $licitatie_id) {
    $query = "SELECT o.*, u.company_name 
              FROM oferte o 
              INNER JOIN users u ON o.transportator_id = u.id 
              WHERE o.licitatie_id = :licitatie_id 
              ORDER BY o.pret_oferit ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":licitatie_id", $licitatie_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listă Expediții - Sistem Logistic</title>
    <style>
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #333;
            line-height: 1.6;
            font-size: 14px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .logout-btn, .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .logout-btn:hover, .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .page-header h2 {
            color: #667eea;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        /* Filtre Section */
        .filtre-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .filtre-section h3 {
            color: #555;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .filtre-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .form-group input, .form-group select {
            padding: 0.6rem;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 0.9rem;
            font-family: inherit;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn-filtru {
            background: #667eea;
            color: white;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .btn-filtru:hover {
            background: #5a6fd8;
        }
        .btn-reset {
            background: #6c757d;
            color: white;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            text-align: center;
        }
        .btn-reset:hover {
            background: #5a6268;
        }
        
        /* Tabel Section */
        .expedities-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .table-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 60px 100px 1fr 1fr 1fr 1fr 1fr 150px 120px;
            gap: 1rem;
            align-items: center;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .table-row {
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 60px 100px 1fr 1fr 1fr 1fr 1fr 150px 120px;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #e1e5e9;
            font-size: 0.85rem;
            transition: background-color 0.2s;
        }
        .table-row:last-child {
            border-bottom: none;
        }
        .table-row:hover {
            background: #f8f9fa;
        }
        .expeditie-id {
            font-weight: bold;
            color: #667eea;
            font-size: 0.9rem;
        }
        .status-planificata {
            background: #fff3cd;
            color: #856404;
            padding: 0.4rem 0.7rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
        }
        .status-in_licitatie {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.4rem 0.7rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
        }
        .status-atribuita {
            background: #d4edda;
            color: #155724;
            padding: 0.4rem 0.7rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
        }
        .timp-raman {
            background: #ffeaa7;
            color: #2d3436;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.75rem;
            text-align: center;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .no-expeditii {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        .no-expeditii .btn {
            margin-top: 1rem;
        }
        .tarif-null {
            color: #999;
            font-style: italic;
        }
        .oferte-info {
            font-size: 0.75rem;
            color: #666;
            text-align: center;
        }
        .atribuire-info {
            font-size: 0.75rem;
            color: #155724;
            text-align: center;
        }
        .expand-details {
            grid-column: 1 / -1;
            background: #f8f9fa;
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .oferta-item {
            background: white;
            padding: 0.5rem 0.75rem;
            margin: 0.25rem 0;
            border-radius: 3px;
            border-left: 3px solid #667eea;
            font-size: 0.8rem;
        }
        .text-uppercase {
            text-transform: uppercase;
        }
        .text-capitalize {
            text-transform: capitalize;
        }
        .text-normal {
            text-transform: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 LISTĂ EXPEDIIȚII</h1>
        <div class="user-info">
            <span>Manager: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="dashboard_manager.php" class="back-btn">← DASHBOARD</a>
            <a href="creare_expeditie.php" class="back-btn">➕ EXPEDIIȚIE NOUĂ</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
            
        </div>
    </div>

    <div class="container">
        <div class="page-header">
            <h2>GESTIONARE EXPEDIIȚII</h2>
        </div>
        
        <!-- Sectiunea Filtre -->
        <div class="filtre-section">
            <h3>🔍 FILTRE EXPEDIIȚII</h3>
            <form method="GET" action="" class="filtre-form">
                <div class="form-group">
                    <label for="client">CLIENT</label>
                    <select id="client" name="client">
                        <option value="">TOȚI CLIENTII</option>
                        <?php foreach ($clienti_unici as $client): ?>
                            <option value="<?php echo htmlspecialchars($client); ?>" 
                                <?php echo ($filtru_client === $client) ? 'selected' : ''; ?>>
                                <?php echo formatText($client); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">STATUS</label>
                    <select id="status" name="status">
                        <option value="">TOATE STATUSURILE</option>
                        <option value="planificata" <?php echo ($filtru_status === 'planificata') ? 'selected' : ''; ?>>PLANIFICATĂ</option>
                        <option value="in_licitatie" <?php echo ($filtru_status === 'in_licitatie') ? 'selected' : ''; ?>>ÎN LICITAȚIE</option>
                        <option value="atribuita" <?php echo ($filtru_status === 'atribuita') ? 'selected' : ''; ?>>ATRIBUITĂ</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data_start">DATA TRANSPORT DE LA</label>
                    <input type="date" id="data_start" name="data_start" value="<?php echo htmlspecialchars($filtru_data_start); ?>">
                </div>
                
                <div class="form-group">
                    <label for="data_end">DATA TRANSPORT PÂNĂ LA</label>
                    <input type="date" id="data_end" name="data_end" value="<?php echo htmlspecialchars($filtru_data_end); ?>">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-filtru">🔍 APLICĂ FILTRE</button>
                </div>
                
                <div class="form-group">
                    <a href="lista_expeditii.php" class="btn-reset">🔄 RESETEAZĂ</a>
                </div>
            </form>
        </div>
        
        <!-- Tabelul cu expediții -->
        <div class="expedities-table">
            <!-- Capul de tabel -->
            <div class="table-header">
                <div>ID</div>
                <div>DATA ADĂUGARE</div>
                <div>CLIENT</div>
                <div>DESTINAȚIE</div>
                <div>PRODUS</div>
                <div>TARIF ȚINTĂ</div>
                <div>DATA TRANSPORT</div>
                <div>STATUS & ACȚIUNI</div>
                <div>OFERTE & DETALII</div>
            </div>
            
            <?php if (empty($expeditii_filtrate)): ?>
                <div class="no-expeditii">
                    <h3>NU S-AU GĂSIT EXPEDIIȚII</h3>
                    <p><?php echo empty($expeditii) ? 'Nu ai nicio expediție creată încă.' : 'Nicio expediție nu corespunde filtrelor tale.'; ?></p>
                    <?php if (empty($expeditii)): ?>
                        <a href="creare_expeditie.php" class="btn">➕ CREAZĂ PRIMA EXPEDIIȚIE</a>
                    <?php else: ?>
                        <a href="lista_expeditii.php" class="btn">🔄 AFIȘEAZĂ TOATE</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($expeditii_filtrate as $expeditie): ?>
                <div class="table-row">
                    <!-- Coloanele principale -->
                    <div class="expeditie-id"><?php echo $expeditie['id']; ?></div>
                    <div><?php echo date('Y-m-d', strtotime($expeditie['data_creare'])); ?></div>
                    <div><strong><?php echo formatText(htmlspecialchars($expeditie['nume_client'])); ?></strong></div>
                    <div><?php echo formatText(htmlspecialchars($expeditie['destinatie'])); ?></div>
                    <div class="text-uppercase"><?php echo htmlspecialchars($expeditie['produs']); ?></div>
                    <div>
                        <?php if ($expeditie['tarif_tinta']): ?>
                            <strong><?php echo number_format($expeditie['tarif_tinta'], 2); ?> <?php echo $expeditie['moneda']; ?></strong>
                        <?php else: ?>
                            <span class="tarif-null">N/A</span>
                        <?php endif; ?>
                    </div>
                    <div><?php echo date('Y-m-d', strtotime($expeditie['data_transport'])); ?></div>
                    
                    <!-- Coloana Status & Acțiuni -->
                    <div>
                        <?php 
                        $status_class = 'status-' . $expeditie['status'];
                        $status_text = '';
                        switch($expeditie['status']) {
                            case 'planificata': 
                                $status_text = 'PLANIFICATĂ';
                                break;
                            case 'in_licitatie': 
                                $status_text = 'ÎN LICITAȚIE';
                                break;
                            case 'atribuita': 
                                $status_text = 'ATRIBUITĂ';
                                break;
                            default: 
                                $status_text = strtoupper($expeditie['status']);
                        }
                        ?>
                        <div class="<?php echo $status_class; ?>" style="margin-bottom: 0.5rem;">
                            <?php echo $status_text; ?>
                        </div>
                        
                        <?php if ($expeditie['status'] === 'planificata'): ?>
                            <a href="start_licitatie.php?expeditie_id=<?php echo $expeditie['id']; ?>" class="btn btn-success">
                                ⚡ START LICITAȚIE
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Coloana Oferte & Detalii -->
                    <div>
                        <?php if ($expeditie['status'] === 'in_licitatie' && $expeditie['licitatie_id']): ?>
                            <?php 
                            $timp_ramas = getTimpRamas($expeditie['data_start'], $expeditie['termen_ore']);
                            $oferte = getOferteLicitatie($db, $expeditie['licitatie_id']);
                            ?>
                            <div class="timp-raman" style="margin-bottom: 0.5rem;">
                                <?php echo $timp_ramas; ?>
                            </div>
                            <div class="oferte-info">
                                OFERTE: <?php echo count($oferte); ?>
                            </div>
                            
                        <?php elseif ($expeditie['status'] === 'atribuita'): ?>
                            <div class="atribuire-info">
                                ✅ ATRIBUITĂ
                            </div>
                            
                        <?php else: ?>
                            <div class="oferte-info">
                                AȘteaptă licitația
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Rând extins cu detalii (doar pentru licitații active) -->
                <?php if ($expeditie['status'] === 'in_licitatie' && $expeditie['licitatie_id'] && count($oferte) > 0): ?>
                <div class="expand-details">
                    <strong>OFERTE PRIMITE (<?php echo count($oferte); ?>):</strong>
                    <?php foreach ($oferte as $oferta): ?>
                        <div class="oferta-item">
                            <?php echo number_format($oferta['pret_oferit'], 2); ?> <?php echo $oferta['moneda']; ?> - <?php echo htmlspecialchars($oferta['company_name']); ?>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top: 0.5rem; font-size: 0.8rem; color: #666;">
                        Atribuirea este blocată. Așteptați expirarea termenului de licitație.
                    </div>
                </div>
                <?php endif; ?>
                
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Setări pentru datepicker
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_start').max = today;
        document.getElementById('data_end').max = today;
        
        // Auto-submit form la schimbarea dropdown-urilor
        document.getElementById('client').addEventListener('change', function() {
            this.form.submit();
        });
        
        document.getElementById('status').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>