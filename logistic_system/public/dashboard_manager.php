<?php
session_start();

// Verifică dacă userul este logat și este manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'manager') {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Licitatie.php';
require_once __DIR__ . '/../models/Expeditie.php';

$database = new Database();
$db = $database->getConnection();

$licitatieModel = new Licitatie($db);
$expeditieModel = new Expeditie($db);

// Obține licitațiile managerului curent
$licitatii = $licitatieModel->getLicitatiiByManager($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Manager - Sistem Logistic</title>
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
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .welcome-section h2 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .action-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #667eea;
        }
        .action-card h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .licitatii-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .licitatii-section h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.875rem;
        }
        .status-pregatire {
            background: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.875rem;
        }
        .status-finalizata {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
            font-size: 0.875rem;
        }
        .no-licitatii {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .no-licitatii .btn {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚚 Dashboard Manager Transport</h1>
        <div class="user-info">
            <span>Bun venit, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h2>Bun venit în sistemul de management logistic!</h2>
            <p>De aici poți gestiona expedițiile, licitațiile și ofertele transportatorilor.</p>
        </div>

        <div class="actions-grid">
            <div class="action-card">
    <h3>🚚 Expediție Nouă</h3>
    <p>Crează o nouă expediție pentru licitație</p>
    <a href="creare_expeditie.php" class="btn">Crează Expediție</a>
</div>
            
            <div class="action-card">
                <h3>⚡ Start Licitatie</h3>
                <p>Pornește licitație pentru expediție</p>
                <a href="start_licitatie.php" class="btn">Start Licitatie</a>
            </div>
            
            <div class="action-card">
                <h3>📊 Vezi Expediții</h3>
                <p>Lista tuturor expedițiilor tale</p>
                <a href="lista_expeditii.php" class="btn">Vezi Expediții</a>
            </div>
        </div>

        <div class="licitatii-section">
            <h2>📋 Licitatiile Mele</h2>
            
            <?php if (empty($licitatii)): ?>
                <div class="no-licitatii">
                    <p>Nu ai nicio licitație activă în acest moment.</p>
                    <p><a href="creare_expeditie.php" class="btn">Creează prima expediție</a></p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Client</th>
                            <th>Destinație</th>
                            <th>Produs</th>
                            <th>Status</th>
                            <th>Număr Oferte</th>
                            <th>Data Creare</th>
                            <th>Acțiuni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licitatii as $licitatie): ?>
                        <tr>
                            <td>#<?php echo $licitatie['id']; ?></td>
                            <td><?php echo htmlspecialchars($licitatie['nume_client'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($licitatie['loc_descarcare'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($licitatie['produs'] ?? 'N/A'); ?></td>
                            <td>
                                <?php 
                                $status_class = '';
                                switch($licitatie['status']) {
                                    case 'active': $status_class = 'status-active'; break;
                                    case 'finalizata': $status_class = 'status-finalizata'; break;
                                    default: $status_class = 'status-pregatire';
                                }
                                ?>
                                <span class="<?php echo $status_class; ?>">
                                    <?php echo ucfirst($licitatie['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $licitatie['numar_oferte']; ?> oferte</td>
                            <td><?php echo date('d.m.Y H:i', strtotime($licitatie['data_start'])); ?></td>
                            <td>
                                <a href="licitatie_detalii.php?id=<?php echo $licitatie['id']; ?>" class="btn" style="padding: 0.5rem 1rem; font-size: 0.875rem;">
                                    Vezi Detalii
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>