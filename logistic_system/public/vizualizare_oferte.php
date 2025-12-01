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
require_once __DIR__ . '/../models/Oferta.php';
require_once __DIR__ . '/../models/Atribuire.php';
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$expeditieModel = new Expeditie($db);
$licitatieModel = new Licitatie($db);
$ofertaModel = new Oferta($db);
$atribuireModel = new Atribuire($db);

$success = '';
$error = '';

// Preluare ID expediție din URL
$expeditie_id = $_GET['expeditie_id'] ?? '';

if (!$expeditie_id) {
    header("Location: lista_expeditii.php");
    exit();
}

// Verifică dacă expediția aparține managerului curent
$expeditie = $expeditieModel->getExpeditieCuOferte($expeditie_id, $_SESSION['user_id']);
if (!$expeditie) {
    header("Location: lista_expeditii.php");
    exit();
}

// Obține ofertele pentru licitație
$oferte = [];
if ($expeditie['licitatie_id']) {
    $oferte = $ofertaModel->getOferteByLicitatie($expeditie['licitatie_id']);
}

// Procesare selectare câștigător
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selectare_castigator'])) {
    $oferta_id = $_POST['oferta_id'];
    
    // Obține detaliile ofertei selectate
    foreach ($oferte as $oferta) {
        if ($oferta['id'] == $oferta_id) {
            // Crează atribuirea
            $data_atribuire = [
                'expeditie_id' => $expeditie_id,
                'licitatie_id' => $expeditie['licitatie_id'],
                'transportator_id' => $oferta['transportator_id'],
                'pret_final' => $oferta['pret_oferit'],
                'moneda' => $oferta['moneda']
            ];
            
            $result = $atribuireModel->create($data_atribuire);
            
            if ($result) {
                // Finalizează licitația
                $licitatieModel->finalizeazaLicitatie($expeditie['licitatie_id'], $oferta_id, 'Ofertă selectată manual de manager');
                
                // Actualizează statusul expediției
                $expeditieModel->updateStatus($expeditie_id, 'atribuita');
                
                // Actualizează statusul ofertei câștigătoare
                $ofertaModel->updateStatus($oferta_id, 'câștigătoare');
                
                $success = "Transportatorul a fost selectat ca câștigător! Va primi notificare să completeze datele camionului.";
                
                // Reîncarcă datele
                $expeditie = $expeditieModel->getExpeditieCuOferte($expeditie_id, $_SESSION['user_id']);
            } else {
                $error = "Eroare la atribuirea transportatorului!";
            }
            break;
        }
    }
}

// Procesare export date către operare
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_operare'])) {
    $atribuire_id = $_POST['atribuire_id'];
    
    // Marchează că datele au fost exportate
    $query = "UPDATE atribuiri 
              SET date_exportate = 1, 
                  data_export = NOW() 
              WHERE id = :atribuire_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":atribuire_id", $atribuire_id);
    $result = $stmt->execute();
    
    if ($result) {
        $success = "Datele au fost marcate ca exportate către operare!";
    } else {
        $error = "Eroare la marcarea exportului!";
    }
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

$timp_ramas = getTimpRamas($expeditie['data_start']);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vizualizare Oferte - Sistem Logistic</title>
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .info-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }
        .info-section h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-size: 1rem;
            color: #333;
        }
        .adr-badge {
            display: inline-block;
            background: #ffeaa7;
            color: #856404;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .timp-raman {
            background: #ffeaa7;
            color: #2d3436;
            padding: 1rem;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            margin: 1rem 0;
        }
        .oferte-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .oferte-section h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .oferta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .oferta-table th,
        .oferta-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .oferta-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        .oferta-table tr:hover {
            background: #f8f9fa;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.875rem;
            font-weight: 500;
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
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background: #e0a800;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            border-left: 4px solid #ffc107;
        }
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .atribuire-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .atribuire-section h2 {
            color: #28a745;
            margin-bottom: 1.5rem;
        }
        .date-actualizate {
            background: #f8d7da;
            color: #721c24;
            padding: 0.5rem;
            border-radius: 5px;
            margin: 0.5rem 0;
            font-weight: 600;
        }
        .email-button {
            background: #17a2b8;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }
        .email-button:hover {
            background: #138496;
        }
        .no-oferte {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 VIZUALIZARE OFERTE</h1>
        <div class="user-info">
            <span>Manager: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="lista_expeditii.php" class="back-btn">← LISTĂ EXPEDIIȚII</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Informații expediție -->
        <div class="info-section">
            <h2>📋 EXPEDIE #<?php echo $expeditie['id']; ?></h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">CLIENT:</span>
                    <span class="info-value"><?php echo htmlspecialchars($expeditie['nume_client']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DESTINAȚIE:</span>
                    <span class="info-value"><?php echo htmlspecialchars($expeditie['destinatie']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRODUS:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($expeditie['produs']); ?>
                        <?php if ($expeditie['necesita_adr']): ?>
                            <span class="adr-badge">⚠️ ADR</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">DATA TRANSPORT:</span>
                    <span class="info-value"><?php echo date('d.m.Y', strtotime($expeditie['data_transport'])); ?></span>
                </div>
            </div>

            <?php if ($expeditie['status'] === 'in_licitatie'): ?>
                <div class="timp-raman">
                    ⏰ LICITAȚIE ACTIVĂ: <strong><?php echo $timp_ramas; ?></strong> RĂMASE
                </div>
            <?php endif; ?>
        </div>

        <?php if ($expeditie['status'] === 'in_licitatie'): ?>
        <!-- Secțiune oferte active -->
        <div class="oferte-section">
            <h2>💰 OFERTE PRIMITE (<?php echo count($oferte); ?>)</h2>
            
            <?php if (empty($oferte)): ?>
                <div class="no-oferte">
                    <h3>Încă nu s-au primit oferte pentru această licitație</h3>
                    <p>Așteptați expirarea termenului de licitație sau reveniți mai târziu.</p>
                </div>
            <?php else: ?>
                <table class="oferta-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>TRANSPORTATOR</th>
                            <th>PRET OFERIT</th>
                            <th>DATA OFERTĂ</th>
                            <th>ACȚIUNI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($oferte as $index => $oferta): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($oferta['company_name']); ?></strong></td>
                            <td>
                                <strong style="font-size: 1.1rem;">
                                    <?php echo number_format($oferta['pret_oferit'], 2); ?> <?php echo $oferta['moneda']; ?>
                                </strong>
                                <?php if ($index === 0): ?>
                                    <span class="badge badge-success">CEA MAI MICĂ</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($oferta['created_at'])); ?></td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="oferta_id" value="<?php echo $oferta['id']; ?>">
                                    <button type="submit" name="selectare_castigator" class="btn btn-success" 
                                            onclick="return confirm('Sigur doriți să selectați acest transportator ca câștigător?')">
                                        ✅ SELECTEAZĂ
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="warning-box" style="margin-top: 1.5rem;">
                    <strong>📋 INSTRUCȚIUNI:</strong><br>
                    • Puteți selecta orice ofertă ca câștigătoare<br>
                    • După selecție, licitația se încheie și transportatorul va primi notificare<br>
                    • Dacă nu selectați manual, câștigătorul va fi cea mai mică ofertă la expirare
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($expeditie['status'] === 'atribuita' && $expeditie['atribuire_id']): ?>
        <!-- Secțiune atribuire și date camion -->
        <div class="atribuire-section">
            <h2>🚚 EXPEDIE ATRIBUITĂ</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">TRANSPORTATOR:</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($expeditie['transportator_company']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRET FINAL:</span>
                    <span class="info-value"><strong><?php echo number_format($expeditie['pret_final'], 2); ?> <?php echo $expeditie['moneda']; ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">STATUS ATRIBUIRII:</span>
                    <span class="info-value">
                        <?php 
                        $status_class = 'badge-';
                        $status_text = '';
                        switch($expeditie['status_atribuire']) {
                            case 'confirmata': 
                                $status_class .= 'success';
                                $status_text = 'CONFIRMATĂ';
                                break;
                            case 'in_asteptare': 
                                $status_class .= 'warning';
                                $status_text = 'ÎN AȘTEPTARE';
                                break;
                            default: 
                                $status_class .= 'danger';
                                $status_text = strtoupper($expeditie['status_atribuire']);
                        }
                        ?>
                        <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </span>
                </div>
            </div>

            <!-- Date camion și șofer -->
            <div style="margin-top: 1.5rem;">
                <h3 style="color: #555; margin-bottom: 1rem;">📝 DATE CAMION ȘI ȘOFER</h3>
                
                <?php if ($expeditie['numar_camion'] && $expeditie['nume_sofer']): ?>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">NUMĂR CAMION:</span>
                            <span class="info-value"><?php echo htmlspecialchars($expeditie['numar_camion']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">NUMĂR REMORCĂ:</span>
                            <span class="info-value"><?php echo htmlspecialchars($expeditie['numar_remorca'] ?: '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">ȘOFER:</span>
                            <span class="info-value"><?php echo htmlspecialchars($expeditie['nume_sofer'] . ' ' . $expeditie['prenume_sofer']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">DOCUMENT:</span>
                            <span class="info-value"><?php echo $expeditie['tip_document']; ?>: <?php echo htmlspecialchars($expeditie['numar_document']); ?></span>
                        </div>
                    </div>
                    
                    <!-- Buton export către operare -->
                    <div style="margin-top: 2rem; text-align: center;">
                        <?php 
                        $subject = "Expeditie " . $expeditie['produs'] . " - " . $expeditie['nume_client'];
                        $body = "Expeditie: " . $expeditie['produs'] . "%0D%0A" .
                                "Client: " . $expeditie['nume_client'] . "%0D%0A" .
                                "Destinatie: " . $expeditie['destinatie'] . "%0D%0A" .
                                "Data transport: " . date('d.m.Y', strtotime($expeditie['data_transport'])) . "%0D%0A%0D%0A" .
                                "TRANSPORTATOR: " . $expeditie['transportator_company'] . "%0D%0A" .
                                "Camion: " . $expeditie['numar_camion'] . "%0D%0A" .
                                "Remorca: " . ($expeditie['numar_remorca'] ?: '-') . "%0D%0A" .
                                "Sofer: " . $expeditie['nume_sofer'] . " " . $expeditie['prenume_sofer'] . "%0D%0A" .
                                "Document: " . $expeditie['tip_document'] . " " . $expeditie['numar_document'] . "%0D%0A%0D%0A" .
                                "ATENȚIE: Aceste date pot fi actualizate ulterior de către transportator.";
                        ?>
                        
                        <a href="mailto:?subject=<?php echo urlencode($subject); ?>&body=<?php echo $body; ?>" 
                           class="email-button" target="_blank">
                            📧 EXPORTĂ DATE CĂTRE OPERARE
                        </a>
                        
                        <!-- Marchează ca exportat -->
                        <form method="POST" action="" style="display: inline; margin-left: 1rem;">
                            <input type="hidden" name="atribuire_id" value="<?php echo $expeditie['atribuire_id']; ?>">
                            <button type="submit" name="export_operare" class="btn btn-success">
                                ✅ MARCHEAZĂ CA EXPORTAT
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <div class="warning-box">
                        ⏳ <strong>AȘTEPTĂM DATELE DE LA TRANSPORTATOR</strong><br>
                        Transportatorul câștigător încă nu a completat datele camionului și șoferului.
                        Va primi notificare să completeze aceste date.
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Confirmări pentru acțiuni importante
        document.querySelectorAll('button[name="selectare_castigator"]').forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Sigur doriți să selectați acest transportator ca câștigător? Această acțiune va încheia licitația.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>