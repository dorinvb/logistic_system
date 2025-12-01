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
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$expeditieModel = new Expeditie($db);
$licitatieModel = new Licitatie($db);

$success = '';
$error = '';

// Preluare ID expediție din URL
$expeditie_id = $_GET['expeditie_id'] ?? '';

if (!$expeditie_id) {
    header("Location: lista_expeditii.php");
    exit();
}

// Verifică dacă expediția aparține managerului curent
$expeditie = $expeditieModel->getExpeditieById($expeditie_id);
if (!$expeditie || $expeditie['created_by'] != $_SESSION['user_id']) {
    header("Location: lista_expeditii.php");
    exit();
}

// Verifică dacă expediția are deja licitație activă
if ($licitatieModel->areLicitatieActiva($expeditie_id)) {
    $error = "Această expediție are deja o licitație activă!";
}

// Procesare start licitație
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Creează licitația
    $result = $licitatieModel->create($expeditie_id, $_SESSION['user_id']);
    
    if ($result) {
        // Actualizează statusul expediției
        $expeditieModel->updateStatus($expeditie_id, 'in_licitatie');
        
        $success = "Licitatia a fost pornită cu succes! Transportatorii pot acum să trimită oferte.";
    } else {
        $error = "Eroare la pornirea licitației!";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Licitatie - Sistem Logistic</title>
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
            border-left: 4px solid #667eea;
        }
        .info-section h2 {
            color: #667eea;
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
        .confirm-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirm-section h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
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
        <h1>⚡ START LICITAȚIE</h1>
        <div class="user-info">
            <span>Manager: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="lista_expeditii.php" class="back-btn">← LISTĂ EXPEDIIȚII</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="success">
                ✅ <?php echo htmlspecialchars($success); ?>
                <div style="margin-top: 1rem;">
                    <a href="lista_expeditii.php" class="btn btn-success">← ÎNAPOI LA LISTĂ</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                ❌ <?php echo htmlspecialchars($error); ?>
                <div style="margin-top: 1rem;">
                    <a href="lista_expeditii.php" class="btn btn-danger">← ÎNAPOI LA LISTĂ</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($success) && empty($error)): ?>
        <!-- Informații expediție -->
        <div class="info-section">
            <h2>📋 DETALII EXPEDIE PENTRU LICITAȚIE</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">ID EXPEDIE:</span>
                    <span class="info-value">#<?php echo $expeditie['id']; ?></span>
                </div>
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
                    <span class="info-label">TARIF ȚINTĂ:</span>
                    <span class="info-value"><strong><?php echo number_format($expeditie['tarif_tinta'], 2); ?> <?php echo $expeditie['moneda']; ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DATA TRANSPORT:</span>
                    <span class="info-value"><?php echo date('d.m.Y', strtotime($expeditie['data_transport'])); ?></span>
                </div>
            </div>

            <?php if ($expeditie['necesita_adr']): ?>
                <div class="warning-box">
                    <strong>⚠️ ATENȚIE - TRANSPORT ADR:</strong><br>
                    Această expediție necesită autorizație ADR validă. 
                    Doar transportatorii cu autorizație ADR înregistrată și validă vor putea participa la licitație.
                </div>
            <?php endif; ?>
        </div>

        <!-- Confirmare start licitație -->
        <div class="confirm-section">
            <h3>🚀 CONFIRMARE START LICITAȚIE</h3>
            <p>Licitatia va fi activă timp de <strong>24 de ore</strong>. Transportatorii vor putea trimite oferte în acest interval.</p>
            
            <div class="warning-box">
                <strong>📋 CONDII LICITAȚIE:</strong><br>
                • Durată: <strong>24 de ore</strong><br>
                • Transportatorii pot trimite o singură ofertă per licitație<br>
                • Ofertele sunt finale și nu pot fi modificate<br>
                <?php if ($expeditie['necesita_adr']): ?>
                • <strong>Doar transportatori cu ADR valid</strong> pot participa
                <?php endif; ?>
            </div>

            <form method="POST" action="" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-success" onclick="return confirm('Sigur doriți să porniți licitația? Această acțiune nu poate fi anulată.')">
                    ⚡ DA, PORNEȘTE LICITAȚIA
                </button>
                <a href="lista_expeditii.php" class="btn btn-danger">❌ ANULEAZĂ</a>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Confirmare înainte de trimitere
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('Sigur doriți să porniți licitația? Această acțiune nu poate fi anulată.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>