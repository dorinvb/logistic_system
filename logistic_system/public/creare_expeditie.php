<?php
session_start();

// Verifică dacă userul este logat și este manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Expeditie.php';
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$expeditieModel = new Expeditie($db);

$success = '';
$error = '';

// Procesare formular creare expediție
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nume_client' => $_POST['nume_client'],
        'destinatie' => $_POST['destinatie'],
        'produs' => $_POST['produs'],
        'necesita_adr' => $_POST['necesita_adr'] ?? 0,
        'tarif_tinta' => $_POST['tarif_tinta'],
        'moneda' => $_POST['moneda'],
        'data_transport' => $_POST['data_transport'],
        'created_by' => $_SESSION['user_id']
    ];

    // Validări de bază
    if (empty($data['nume_client']) || empty($data['destinatie']) || empty($data['produs']) || empty($data['data_transport'])) {
        $error = "Toate câmpurile obligatorii trebuie completate!";
    } else {
        $result = $expeditieModel->create($data);
        
        if ($result['success']) {
            $success = $result['message'];
            $_POST = array(); // Reset form
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creare Expediție - Sistem Logistic</title>
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
        }
        .logout-btn:hover, .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-container h2 {
            color: #667eea;
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
        input, textarea, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            font-family: inherit;
        }
        input:focus, textarea:focus, select:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }
        .btn:hover {
            background: #5a6fd8;
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
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
        }
        .adr-warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-top: 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚚 Creare Expediție Nouă</h1>
        <div class="user-info">
            <span>Manager: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="dashboard_manager.php" class="back-btn">← Dashboard</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="form-container">
            <h2>Adaugă o nouă expediție</h2>
            
            <div class="info-box">
                <strong>💡 Informații:</strong> Toate expedițiile vor fi create cu punctul de încărcare <strong>Târgu Mureș</strong>. 
                După creare, poți porni licitația pentru a primi oferte de la transportatori.
            </div>
            
            <?php if ($success): ?>
                <div class="success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                    <br><small>Expediția a fost creată și este acum disponibilă pentru licitație.</small>
                    <br><a href="lista_expeditii.php" class="btn" style="margin-top: 0.5rem; background: #28a745;">Vezi Toate Expedițiile</a>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nume_client" class="required">Nume Client</label>
                    <input type="text" id="nume_client" name="nume_client" required
                           value="<?php echo htmlspecialchars($_POST['nume_client'] ?? ''); ?>"
                           placeholder="ex: Comcerial, Azochim, Archim, etc.">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="destinatie" class="required">Destinație</label>
                        <input type="text" id="destinatie" name="destinatie" required
                               value="<?php echo htmlspecialchars($_POST['destinatie'] ?? ''); ?>"
                               placeholder="ex: Focșani, Oltenița, Glogovat, etc.">
                    </div>

                    <div class="form-group">
                        <label for="produs" class="required">Produs</label>
                        <input type="text" id="produs" name="produs" required
                               value="<?php echo htmlspecialchars($_POST['produs'] ?? ''); ?>"
                               placeholder="ex: NPK NON ADR, AN CU ADR, CAN NON ADR, etc.">
                    </div>
                </div>

                <div class="form-group">
                    <label for="necesita_adr">Transport ADR necesar?</label>
                    <select id="necesita_adr" name="necesita_adr" onchange="toggleADRWarning()">
                        <option value="0" <?php echo (($_POST['necesita_adr'] ?? '0') === '0') ? 'selected' : ''; ?>>NU - Transport normal</option>
                        <option value="1" <?php echo (($_POST['necesita_adr'] ?? '0') === '1') ? 'selected' : ''; ?>>DA - Transport substanțe periculoase (ADR)</option>
                    </select>
                    <div id="adr-warning" class="adr-warning" style="display: none;">
                        ⚠️ <strong>ATENȚIE:</strong> Pentru transporturile ADR, doar transportatorii cu autorizație ADR validă vor putea participa la licitație.
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tarif_tinta" class="required">Tarif Țintă</label>
                        <input type="number" id="tarif_tinta" name="tarif_tinta" required step="0.01" min="0"
                               value="<?php echo htmlspecialchars($_POST['tarif_tinta'] ?? ''); ?>"
                               placeholder="ex: 300.00">
                    </div>

                    <div class="form-group">
                        <label for="moneda" class="required">Monedă</label>
                        <select id="moneda" name="moneda" required>
                            <option value="">Selectează moneda</option>
                            <option value="EUR" <?php echo (($_POST['moneda'] ?? '') === 'EUR') ? 'selected' : ''; ?>>EUR</option>
                            <option value="RON" <?php echo (($_POST['moneda'] ?? '') === 'RON') ? 'selected' : ''; ?>>RON</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="data_transport" class="required">Data Transportului</label>
                    <input type="date" id="data_transport" name="data_transport" required
                           value="<?php echo htmlspecialchars($_POST['data_transport'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn">🚚 Crează Expediție</button>
            </form>
        </div>
    </div>

    <script>
        // Setează data minimă pentru mâine
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('data_transport').min = tomorrow.toISOString().split('T')[0];
        
        // Focus primul câmp
        document.getElementById('nume_client').focus();

        // Funcție pentru afișare/ascundere avertisment ADR
        function toggleADRWarning() {
            const necesitaADR = document.getElementById('necesita_adr').value;
            const adrWarning = document.getElementById('adr-warning');
            
            if (necesitaADR === '1') {
                adrWarning.style.display = 'block';
            } else {
                adrWarning.style.display = 'none';
            }
        }

        // Apelează funcția la încărcarea paginii
        document.addEventListener('DOMContentLoaded', toggleADRWarning);
    </script>
</body>
</html>