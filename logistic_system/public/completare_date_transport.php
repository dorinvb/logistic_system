<?php
session_start();

// Verifică dacă userul este logat și este transportator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'transportator') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Atribuire.php';
require_once __DIR__ . '/../models/Expeditie.php';
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$atribuireModel = new Atribuire($db);
$expeditieModel = new Expeditie($db);

$success = '';
$error = '';

// Preluare ID atribuire din URL
$atribuire_id = $_GET['id'] ?? '';

if (!$atribuire_id) {
    header("Location: dashboard_transportator.php");
    exit();
}

// Verifică dacă atribuirea aparține transportatorului curent
$atribuire = $atribuireModel->getAtribuireById($atribuire_id);
if (!$atribuire || $atribuire['transportator_id'] != $_SESSION['user_id']) {
    header("Location: dashboard_transportator.php");
    exit();
}

// Procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'numar_camion' => $_POST['numar_camion'],
        'numar_remorca' => $_POST['numar_remorca'],
        'nume_sofer' => $_POST['nume_sofer'],
        'prenume_sofer' => $_POST['prenume_sofer'],
        'tip_document' => $_POST['tip_document'],
        'numar_document' => $_POST['numar_document']
    ];

    // Validări
    if (empty($data['numar_camion']) || empty($data['nume_sofer']) || empty($data['prenume_sofer']) || empty($data['numar_document'])) {
        $error = "Toate câmpurile obligatorii trebuie completate!";
    } else {
        $result = $atribuireModel->updateDetaliiTransport($atribuire_id, $data);
        
        if ($result) {
            $success = "Datele camionului și șoferului au fost salvate cu succes!";
            // Actualizează statusul expediției
            $expeditieModel->updateStatus($atribuire['expeditie_id'], 'atribuita');
        } else {
            $error = "Eroare la salvarea datelor!";
        }
    }
}

// Obține detalii expediție
$expeditie = $expeditieModel->getExpeditieById($atribuire['expeditie_id']);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completare Date Transport - Sistem Logistic</title>
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
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            border-left: 4px solid #00b894;
        }
        .info-section h3 {
            color: #00b894;
            margin-bottom: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        .info-value {
            font-size: 1rem;
            color: #333;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-container h2 {
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .form-row, .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚚 COMPLETARE DATE TRANSPORT</h1>
        <div class="user-info">
            <span>Transportator: <strong><?php echo htmlspecialchars($_SESSION['company'] ?? $_SESSION['username']); ?></strong></span>
            <a href="dashboard_transportator.php" class="back-btn">← DASHBOARD</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <!-- Informații expediție -->
        <div class="info-section">
            <h3>📦 INFORMATII EXPEDITIE</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">CLIENT:</span>
                    <span class="info-value"><?php echo htmlspecialchars($expeditie['nume_client']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DESTINATIE:</span>
                    <span class="info-value"><?php echo htmlspecialchars($expeditie['destinatie']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRODUS:</span>
                    <span class="info-value"><?php echo htmlspecialchars($expeditie['produs']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">DATA TRANSPORT:</span>
                    <span class="info-value"><?php echo date('d.m.Y', strtotime($expeditie['data_transport'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">PRET OFERTAT:</span>
                    <span class="info-value"><strong><?php echo number_format($atribuire['pret_final'], 2); ?> <?php echo $atribuire['moneda']; ?></strong></span>
                </div>
            </div>
        </div>

        <!-- Formular completare date -->
        <div class="form-container">
            <h2>📝 DETALII CAMION ȘI ȘOFER</h2>
            
            <?php if ($success): ?>
                <div class="success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                    <br><small>Expediția a fost confirmată cu succes!</small>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="numar_camion" class="required">Număr Înmatriculare Camion</label>
                        <input type="text" id="numar_camion" name="numar_camion" required
                               value="<?php echo htmlspecialchars($_POST['numar_camion'] ?? ''); ?>"
                               placeholder="ex: AB12ABC" maxlength="10">
                    </div>

                    <div class="form-group">
                        <label for="numar_remorca">Număr Înmatriculare Remorcă (dacă există)</label>
                        <input type="text" id="numar_remorca" name="numar_remorca"
                               value="<?php echo htmlspecialchars($_POST['numar_remorca'] ?? ''); ?>"
                               placeholder="ex: AB13ABC" maxlength="10">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nume_sofer" class="required">Nume Șofer</label>
                        <input type="text" id="nume_sofer" name="nume_sofer" required
                               value="<?php echo htmlspecialchars($_POST['nume_sofer'] ?? ''); ?>"
                               placeholder="ex: Popescu">
                    </div>

                    <div class="form-group">
                        <label for="prenume_sofer" class="required">Prenume Șofer</label>
                        <input type="text" id="prenume_sofer" name="prenume_sofer" required
                               value="<?php echo htmlspecialchars($_POST['prenume_sofer'] ?? ''); ?>"
                               placeholder="ex: Ion">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tip_document" class="required">Tip Document Identitate</label>
                        <select id="tip_document" name="tip_document" required>
                            <option value="">Selectează tip document</option>
                            <option value="CI" <?php echo (($_POST['tip_document'] ?? '') === 'CI') ? 'selected' : ''; ?>>Carte de Identitate (CI)</option>
                            <option value="PASAPORT" <?php echo (($_POST['tip_document'] ?? '') === 'PASAPORT') ? 'selected' : ''; ?>>Pașaport</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="numar_document" class="required">Număr Document</label>
                        <input type="text" id="numar_document" name="numar_document" required
                               value="<?php echo htmlspecialchars($_POST['numar_document'] ?? ''); ?>"
                               placeholder="ex: AX123456" maxlength="20">
                    </div>
                </div>

                <button type="submit" class="btn">✅ CONFIRMĂ ȘI SALVEAZĂ DATELE</button>
            </form>
        </div>
    </div>
</body>
</html>
