<?php
session_start();

// Verifică dacă userul este logat și este transportator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'transportator') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Licitatie.php';
require_once __DIR__ . '/../models/Oferta.php';
require_once __DIR__ . '/../models/AutorizatieADR.php';
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$licitatieModel = new Licitatie($db);
$ofertaModel = new Oferta($db);
$adrModel = new AutorizatieADR($db);

$success = '';
$error = '';

// Preluare ID licitație din URL
$licitatie_id = $_GET['licitatie_id'] ?? '';

if (!$licitatie_id) {
    header("Location: dashboard_transportator.php");
    exit();
}

// Obține detalii licitație
$licitatie = $licitatieModel->getLicitatieById($licitatie_id);
if (!$licitatie || $licitatie['status'] !== 'active') {
    header("Location: dashboard_transportator.php");
    exit();
}

// Verifică dacă transportatorul a oferit deja
if ($ofertaModel->hasTransportatorOferit($licitatie_id, $_SESSION['user_id'])) {
    $error = "Ați trimis deja o ofertă pentru această licitație!";
}

// Verifică ADR dacă este necesar
if ($licitatie['necesita_adr']) {
    $stare_adr = $adrModel->areAutorizatieValabila($_SESSION['user_id']);
    if (!$stare_adr['are_autorizatie']) {
        $error = "Această licitație necesită autorizație ADR validă. Vă rugăm să vă actualizați autorizația ADR înainte de a trimite o ofertă.";
    }
}

// Procesare trimitere ofertă
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $data = [
        'licitatie_id' => $licitatie_id,
        'transportator_id' => $_SESSION['user_id'],
        'pret_oferit' => $_POST['pret_oferit'],
        'moneda' => $licitatie['moneda']
    ];

    // Validări
    if (empty($data['pret_oferit']) || $data['pret_oferit'] <= 0) {
        $error = "Prețul ofertei trebuie să fie un număr pozitiv!";
    } else {
        $result = $ofertaModel->create($data);
        
        if ($result) {
            $success = "Oferta a fost trimisă cu succes! Veți fi notificat când licitația se încheie.";
        } else {
            $error = "Eroare la trimiterea ofertei!";
        }
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

$timp_ramas = getTimpRamas($licitatie['data_start']);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trimitere Ofertă - Sistem Logistic</title>
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
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
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
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .info-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #00b894;
        }
        .info-section h2 {
            color: #00b894;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        .timp-raman {
            background: #ffeaa7;
            color: #2d3436;
            padding: 1rem;
            border-radius: 5px;
            font-weight: 600;
            text-align: center;
            margin: 1rem 0;
        }
        .adr-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .adr-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
            border-left: 4px solid #28a745;
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-section h3 {
            color: #00b894;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: 500;
        }
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        input:focus, select:focus {
            border-color: #00b894;
            outline: none;
        }
        .btn {
            background: #00b894;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
            font-weight: 500;
        }
        .btn:hover {
            background: #00a085;
        }
        .btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
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
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📨 TRIMITERE OFERTĂ</h1>
        <div class="user-info">
            <span>Transportator: <strong><?php echo htmlspecialchars($_SESSION['company'] ?? $_SESSION['username']); ?></strong></span>
            <a href="dashboard_transportator.php" class="back-btn">← DASHBOARD</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                ✅ <?php echo htmlspecialchars($success); ?>
                <div style="margin-top: 1rem;">
                    <a href="dashboard_transportator.php" class="btn">← ÎNAPOI LA DASHBOARD</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($error); ?>
                <div style="margin-top: 1rem;">
                    <?php if (strpos($error, 'ADR') !== false): ?>
                        <a href="gestiune_adr.php" class="btn">⚠️ GESTIONEAZĂ AUTORIZAȚIA ADR</a>
                    <?php endif; ?>
                    <a href="dashboard_transportator.php" class="btn" style="background: #6c757d;">← ÎNAPOI LA DASHBOARD</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($success) && empty($error)): ?>
        <!-- Informații licitație -->
        <div class="info-section">
            <h2>📋 DETALII LICITAȚIE</h2>
            
            <div class="timp-raman">
                ⏰ TIMP RĂMAS: <strong><?php echo $timp_ramas; ?></strong>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">ID LICITAȚIE:</span>
                    <span class="info-value">#<?php echo $licitatie['id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">CLIENT:</span>
                    <span class="info-value"><?php echo htmlspecialchars($licitatie['nume_client']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DESTINAȚIE:</span>
                    <span class="info-value"><?php echo htmlspecialchars($licitatie['destinatie']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRODUS:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($licitatie['produs']); ?>
                        <?php if ($licitatie['necesita_adr']): ?>
                            <span class="adr-badge">⚠️ ADR</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">TARIF ȚINTĂ:</span>
                    <span class="info-value"><strong><?php echo number_format($licitatie['tarif_tinta'], 2); ?> <?php echo $licitatie['moneda']; ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DATA TRANSPORT:</span>
                    <span class="info-value"><?php echo date('d.m.Y', strtotime($licitatie['data_transport'])); ?></span>
                </div>
            </div>

            <?php if ($licitatie['necesita_adr']): ?>
                <?php
                $stare_adr = $adrModel->areAutorizatieValabila($_SESSION['user_id']);
                if ($stare_adr['are_autorizatie']):
                ?>
                    <div class="adr-success">
                        ✅ <strong>AUTORIZAȚIE ADR VALABILĂ</strong><br>
                        Autorizația dvs. ADR este validă până la <?php echo date('d.m.Y', strtotime($stare_adr['data_expirare'])); ?> 
                        (<?php echo $stare_adr['zile_ramase']; ?> zile rămase)
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Formular trimitere ofertă -->
        <div class="form-section">
            <h3>💶 TRIMITE OFERTĂ</h3>
            
            <div class="warning-box">
                <strong>📋 IMPORTANT:</strong><br>
                • Oferta este <strong>finală și irevocabilă</strong><br>
                • Nu puteți modifica oferta după trimitere<br>
                • Puteți trimite <strong>o singură ofertă</strong> per licitație<br>
                • Oferta trebuie să fie în <strong><?php echo $licitatie['moneda']; ?></strong>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="pret_oferit" class="required">Preț Oferit (<?php echo $licitatie['moneda']; ?>)</label>
                    <input type="number" id="pret_oferit" name="pret_oferit" required 
                           step="0.01" min="0.01" max="999999.99"
                           placeholder="ex: 250.00">
                    <small style="color: #666;">Introduceți prețul în <?php echo $licitatie['moneda']; ?></small>
                </div>

                <button type="submit" class="btn" onclick="return confirm('Sigur doriți să trimiteți această ofertă? Oferta este finală și nu poate fi modificată.')">
                    📨 TRIMITE OFERTĂ
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Confirmare înainte de trimitere
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Sigur doriți să trimiteți această ofertă? Oferta este finală și nu poate fi modificată.')) {
                e.preventDefault();
            }
        });

        // Sugestie preț bazată pe tarif țintă
        const pretOferit = document.getElementById('pret_oferit');
        const tarifTinta = <?php echo $licitatie['tarif_tinta']; ?>;
        
        pretOferit.addEventListener('focus', function() {
            if (!this.value) {
                this.value = (tarifTinta * 0.9).toFixed(2); // 10% sub tarif țintă
            }
        });
    </script>
</body>
</html>