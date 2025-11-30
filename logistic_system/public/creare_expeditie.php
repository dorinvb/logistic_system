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
        'nume_expeditie' => $_POST['nume_expeditie'],
        'loc_incarcare' => $_POST['loc_incarcare'],
        'loc_descarcare' => $_POST['loc_descarcare'],
        'client_nume' => $_POST['client_nume'],
        'distanta_km' => $_POST['distanta_km'],
        'tonaj' => $_POST['tonaj'],
        'tarif_tinta' => $_POST['tarif_tinta'],
        'data_expeditie' => $_POST['data_expeditie'],
        'detalii' => $_POST['detalii'],
        'created_by' => $_SESSION['user_id']
    ];

    $result = $expeditieModel->create($data);
    
    if ($result['success']) {
        $success = $result['message'];
        $_POST = array();
    } else {
        $error = $result['message'];
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 5px;
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
                <strong>💡 Informații:</strong> Expedițiile vor fi create cu punctul de încărcare prestabilit Târgu Mureș, 
                dar poți modifica dacă este necesar pentru retururi sau alte situații.
            </div>
            
            <?php if ($success): ?>
                <div class="success">
                    ✅ <?php echo htmlspecialchars($success); ?>
                    <br><small>Expediția a fost creată și este acum disponibilă pentru licitație.</small>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nume_expeditie">Nume Expediție *</label>
                    <input type="text" id="nume_expeditie" name="nume_expeditie" required
                           value="<?php echo htmlspecialchars($_POST['nume_expeditie'] ?? ''); ?>"
                           placeholder="ex: Expediție Client X - București">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="loc_incarcare">Loc Încărcare *</label>
                        <input type="text" id="loc_incarcare" name="loc_incarcare" required
                               value="<?php echo htmlspecialchars($_POST['loc_incarcare'] ?? 'Târgu Mureș'); ?>"
                               placeholder="ex: Târgu Mureș">
                    </div>

                    <div class="form-group">
                        <label for="loc_descarcare">Loc Descărcare *</label>
                        <input type="text" id="loc_descarcare" name="loc_descarcare" required
                               value="<?php echo htmlspecialchars($_POST['loc_descarcare'] ?? ''); ?>"
                               placeholder="ex: București">
                    </div>
                </div>

                <div class="form-group">
                    <label for="client_nume">Nume Client *</label>
                    <input type="text" id="client_nume" name="client_nume" required
                           value="<?php echo htmlspecialchars($_POST['client_nume'] ?? ''); ?>"
                           placeholder="ex: SC Client SRL">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="distanta_km">Distanță (km) *</label>
                        <input type="number" id="distanta_km" name="distanta_km" required step="0.1"
                               value="<?php echo htmlspecialchars($_POST['distanta_km'] ?? ''); ?>"
                               placeholder="ex: 325.5">
                    </div>

                    <div class="form-group">
                        <label for="tonaj">Tonaj (tone) *</label>
                        <input type="number" id="tonaj" name="tonaj" required step="0.01"
                               value="<?php echo htmlspecialchars($_POST['tonaj'] ?? '24.00'); ?>"
                               placeholder="ex: 24.00">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tarif_tinta">Tarif Țintă (RON) *</label>
                        <input type="number" id="tarif_tinta" name="tarif_tinta" required step="0.01"
                               value="<?php echo htmlspecialchars($_POST['tarif_tinta'] ?? ''); ?>"
                               placeholder="ex: 1500.00">
                    </div>

                    <div class="form-group">
                        <label for="data_expeditie">Data Expediției *</label>
                        <input type="date" id="data_expeditie" name="data_expeditie" required
                               value="<?php echo htmlspecialchars($_POST['data_expeditie'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="detalii">Detalii Expediție (opțional)</label>
                    <textarea id="detalii" name="detalii" rows="4"
                              placeholder="ex: Marfă generală, ambalaj special, instrucțiuni de descărcare..."><?php echo htmlspecialchars($_POST['detalii'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn">🚚 Crează Expediție</button>
            </form>
        </div>
    </div>

    <script>
        // Setează data minimă pentru azi
        document.getElementById('data_expeditie').min = new Date().toISOString().split('T')[0];
    </script>
</body>
</html>