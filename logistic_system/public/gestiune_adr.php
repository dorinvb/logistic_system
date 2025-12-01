<?php
session_start();

// Verifică dacă userul este logat și este transportator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'transportator') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AutorizatieADR.php';
require_once __DIR__ . '/../utils/security.php';

$database = new Database();
$db = $database->getConnection();
$adrModel = new AutorizatieADR($db);

$success = '';
$error = '';

// Verifică starea autorizației curente
$stare_autorizatie = $adrModel->areAutorizatieValabila($_SESSION['user_id']);
$autorizatie_curenta = $adrModel->getAutorizatieCurenta($_SESSION['user_id']);
$istoric_autorizatii = $adrModel->getIstoricAutorizatii($_SESSION['user_id']);

// Procesare formular încărcare/autorizare ADR
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_success = false;
    $nume_fisier = '';
    
    // Procesare upload fișier
    if (isset($_FILES['poza_autorizatie']) && $_FILES['poza_autorizatie']['error'] === UPLOAD_ERR_OK) {
        $director_upload = __DIR__ . '/../uploads/adr/';
        
        // Crează directorul dacă nu există
        if (!is_dir($director_upload)) {
            mkdir($director_upload, 0755, true);
        }
        
        $extensie = pathinfo($_FILES['poza_autorizatie']['name'], PATHINFO_EXTENSION);
        $nume_fisier = 'ADR_' . $_SESSION['user_id'] . '_' . time() . '.' . $extensie;
        $cale_fisier = $director_upload . $nume_fisier;
        
        // Verifică tipul fișierului
        $tipuri_permise = ['jpg', 'jpeg', 'png', 'pdf'];
        if (in_array(strtolower($extensie), $tipuri_permise)) {
            if (move_uploaded_file($_FILES['poza_autorizatie']['tmp_name'], $cale_fisier)) {
                $upload_success = true;
            } else {
                $error = "Eroare la încărcarea fișierului!";
            }
        } else {
            $error = "Doar fișiere JPG, JPEG, PNG și PDF sunt permise!";
        }
    } else {
        $error = "Trebuie să încărcați o poză a autorizației ADR!";
    }
    
    if ($upload_success && empty($error)) {
        $data = [
            'transportator_id' => $_SESSION['user_id'],
            'numar_autorizatie' => $_POST['numar_autorizatie'],
            'data_emitere' => $_POST['data_emitere'],
            'data_expirare' => $_POST['data_expirare'],
            'poza_autorizatie' => $nume_fisier
        ];
        
        $result = $adrModel->create($data);
        
        if ($result['success']) {
            $success = $result['message'];
            // Reîncarcă datele actualizate
            $stare_autorizatie = $adrModel->areAutorizatieValabila($_SESSION['user_id']);
            $autorizatie_curenta = $adrModel->getAutorizatieCurenta($_SESSION['user_id']);
            $istoric_autorizatii = $adrModel->getIstoricAutorizatii($_SESSION['user_id']);
        } else {
            $error = $result['message'];
            // Șterge fișierul încărcat dacă a eșuat salvarea în DB
            if (file_exists($cale_fisier)) {
                unlink($cale_fisier);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestiune ADR - Sistem Logistic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .status-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        .status-valabil {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .status-expirat {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .status-lipsa {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .form-section, .istoric-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .form-section h2, .istoric-section h2 {
            color: #00b894;
            margin-bottom: 1.5rem;
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
        .istoric-table {
            width: 100%;
            border-collapse: collapse;
        }
        .istoric-table th,
        .istoric-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .istoric-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
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
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
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
        <h1>⚠️ GESTIUNE AUTORIZAȚII ADR</h1>
        <div class="user-info">
            <span>Transportator: <strong><?php echo htmlspecialchars($_SESSION['company'] ?? $_SESSION['username']); ?></strong></span>
            <a href="dashboard_transportator.php" class="back-btn">← DASHBOARD</a>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <!-- Status Autorizație -->
        <div class="status-section <?php 
            if ($stare_autorizatie['are_autorizatie']) {
                echo 'status-valabil';
            } else {
                echo $autorizatie_curenta ? 'status-expirat' : 'status-lipsa';
            }
        ?>">
            <div class="status-icon">
                <?php if ($stare_autorizatie['are_autorizatie']): ?>
                    ✅
                <?php elseif ($autorizatie_curenta): ?>
                    ❌
                <?php else: ?>
                    ⚠️
                <?php endif; ?>
            </div>
            
            <h2>
                <?php if ($stare_autorizatie['are_autorizatie']): ?>
                    AUTORIZAȚIE ADR VALABILĂ
                <?php elseif ($autorizatie_curenta): ?>
                    AUTORIZAȚIE ADR EXPIRATĂ
                <?php else: ?>
                    NU EXISTĂ AUTORIZAȚIE ADR
                <?php endif; ?>
            </h2>
            
            <?php if ($stare_autorizatie['are_autorizatie']): ?>
                <p>Autorizația dvs. ADR este valabilă până la <strong><?php echo date('d.m.Y', strtotime($stare_autorizatie['data_expirare'])); ?></strong></p>
                <p><strong><?php echo $stare_autorizatie['zile_ramase']; ?> zile</strong> rămase până la expirare</p>
            <?php elseif ($autorizatie_curenta): ?>
                <p>Autorizația dvs. ADR a expirat la <strong><?php echo date('d.m.Y', strtotime($autorizatie_curenta['data_expirare'])); ?></strong></p>
                <p>Pentru a participa la transporturile ADR, trebuie să reînnoiți autorizația.</p>
            <?php else: ?>
                <p>Pentru a participa la transporturile cu substanțe periculoase (ADR), trebuie să încărcați autorizația ADR.</p>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="success">✅ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Formular Încărcare Autorizație -->
        <div class="form-section">
            <h2><?php echo $autorizatie_curenta ? 'REÎNNOIRE AUTORIZAȚIE ADR' : 'ÎNCĂRCARE AUTORIZAȚIE ADR'; ?></h2>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="numar_autorizatie" class="required">Număr Autorizație ADR</label>
                        <input type="text" id="numar_autorizatie" name="numar_autorizatie" required
                               value="<?php echo htmlspecialchars($_POST['numar_autorizatie'] ?? ''); ?>"
                               placeholder="ex: ADR-RO-123456">
                    </div>

                    <div class="form-group">
                        <label for="data_emitere" class="required">Data Emiterii</label>
                        <input type="date" id="data_emitere" name="data_emitere" required
                               value="<?php echo htmlspecialchars($_POST['data_emitere'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="data_expirare" class="required">Data Expirării</label>
                        <input type="date" id="data_expirare" name="data_expirare" required
                               value="<?php echo htmlspecialchars($_POST['data_expirare'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="poza_autorizatie" class="required">Poză/Scan Autorizație</label>
                        <input type="file" id="poza_autorizatie" name="poza_autorizatie" required
                               accept=".jpg,.jpeg,.png,.pdf">
                        <small style="color: #666;">Formate acceptate: JPG, JPEG, PNG, PDF (max 5MB)</small>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <?php echo $autorizatie_curenta ? '🔄 REÎNNOIEȘTE AUTORIZAȚIA' : '📤 ÎNCARCĂ AUTORIZAȚIA'; ?>
                </button>
            </form>
        </div>

        <!-- Istoric Autorizații -->
        <?php if (!empty($istoric_autorizatii)): ?>
        <div class="istoric-section">
            <h2>📊 ISTORIC AUTORIZAȚII ADR</h2>
            
            <table class="istoric-table">
                <thead>
                    <tr>
                        <th>Număr Autorizație</th>
                        <th>Data Emiterii</th>
                        <th>Data Expirării</th>
                        <th>Status</th>
                        <th>Data Încărcării</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($istoric_autorizatii as $autorizatie): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($autorizatie['numar_autorizatie']); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($autorizatie['data_emitere'])); ?></td>
                        <td><?php echo date('d.m.Y', strtotime($autorizatie['data_expirare'])); ?></td>
                        <td>
                            <?php 
                            $este_valabila = $autorizatie['este_valabila'] && strtotime($autorizatie['data_expirare']) >= time();
                            if ($este_valabila) {
                                echo '<span class="badge badge-success">VALABILĂ</span>';
                            } elseif (strtotime($autorizatie['data_expirare']) < time()) {
                                echo '<span class="badge badge-danger">EXPIRATĂ</span>';
                            } else {
                                echo '<span class="badge badge-warning">INACTIVĂ</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($autorizatie['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Setări pentru datepicker
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_emitere').max = today;
        
        // Data expirare minim data emiterii
        document.getElementById('data_emitere').addEventListener('change', function() {
            document.getElementById('data_expirare').min = this.value;
        });
        
        // Verificare fișier
        document.getElementById('poza_autorizatie').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // MB
                if (fileSize > 5) {
                    alert('Fișierul este prea mare! Maxim 5MB permis.');
                    this.value = '';
                }
            }
        });
    </script>
</body>
</html>