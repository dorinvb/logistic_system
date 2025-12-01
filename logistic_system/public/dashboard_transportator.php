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

$database = new Database();
$db = $database->getConnection();
$licitatieModel = new Licitatie($db);
$ofertaModel = new Oferta($db);

// Obține licitațiile active
$licitatii_active = $licitatieModel->getLicitatiiActive();

// Obține ofertele transportatorului curent
$ofertele_mele = $ofertaModel->getOferteByTransportator($_SESSION['user_id']);

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
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Transportator - Sistem Logistic</title>
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
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 4px solid #00b894;
        }
        .welcome-section h2 {
            color: #00b894;
            margin-bottom: 0.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            color: #555;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #00b894;
        }
        .licitatii-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .licitatii-section h2 {
            color: #00b894;
            margin-bottom: 1.5rem;
        }
        .table-header {
            background: #2c3e50;
            color: white;
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 80px 1fr 1fr 1fr 1fr 100px 120px 150px;
            gap: 1rem;
            align-items: center;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 5px 5px 0 0;
        }
        .table-row {
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 80px 1fr 1fr 1fr 1fr 100px 120px 150px;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #e1e5e9;
            font-size: 0.85rem;
        }
        .table-row:last-child {
            border-bottom: none;
        }
        .table-row:hover {
            background: #f8f9fa;
        }
        .btn {
            background: #00b894;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
        }
        .btn:hover {
            background: #00a085;
        }
        .btn-oferta {
            background: #0984e3;
        }
        .btn-oferta:hover {
            background: #0770c4;
        }
        .timp-raman {
            background: #ffeaa7;
            color: #2d3436;
            padding: 0.4rem 0.7rem;
            border-radius: 5px;
            font-weight: 600;
            font-size: 0.75rem;
            text-align: center;
        }
        .no-licitatii {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .oferta-status {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }
        .status-activa {
            background: #d1ecf1;
            color: #0c5460;
        }
        .status-castigatoare {
            background: #d4edda;
            color: #155724;
        }
        .status-respinsa {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚚 DASHBOARD TRANSPORTATOR</h1>
        <div class="user-info">
            <span>Transportator: <strong><?php echo htmlspecialchars($_SESSION['company'] ?? $_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="container">
        <!-- Sectiunea Welcome -->
        <div class="welcome-section">
            <h2>Bun venit, <?php echo htmlspecialchars($_SESSION['company'] ?? $_SESSION['username']); ?>! 🚛</h2>
            <p>De aici poți vedea licitațiile active și poți trimite oferte pentru expediții.</p>
        </div>

        <!-- Statistici -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Licitații Active</h3>
                <div class="stat-number"><?php echo count($licitatii_active); ?></div>
            </div>
            <div class="stat-card">
                <h3>Oferte Trimise</h3>
                <div class="stat-number"><?php echo count($ofertele_mele); ?></div>
            </div>
            <div class="stat-card">
                <h3>Oferte Câștigătoare</h3>
                <div class="stat-number"><?php echo count(array_filter($ofertele_mele, fn($o) => $o['status'] === 'câștigătoare')); ?></div>
            </div>
        </div>

        <!-- Licitații Active -->
        <div class="licitatii-section">
            <h2>📋 LICITAȚII ACTIVE</h2>
            
            <?php if (empty($licitatii_active)): ?>
                <div class="no-licitatii">
                    <h3>Nu există licitații active în acest moment</h3>
                    <p>Vei fi notificat când vor apărea noi licitații.</p>
                </div>
            <?php else: ?>
                <div class="table-header">
                    <div>ID</div>
                    <div>CLIENT</div>
                    <div>DESTINAȚIE</div>
                    <div>PRODUS</div>
                    <div>MANAGER</div>
                    <div>TERMEN</div>
                    <div>STATUS OFERTĂ</div>
                    <div>ACȚIUNE</div>
                </div>
                
                <?php foreach ($licitatii_active as $licitatie): ?>
                <?php 
                $timp_ramas = getTimpRamas($licitatie['data_start']);
                $has_oferta = false;
                $oferta_status = '';
                
                // Verifică dacă transportatorul a oferit deja
                foreach ($ofertele_mele as $oferta) {
                    if ($oferta['licitatie_id'] == $licitatie['id']) {
                        $has_oferta = true;
                        $oferta_status = $oferta['status'];
                        break;
                    }
                }
                ?>
                <div class="table-row">
                    <div>#<?php echo $licitatie['id']; ?></div>
                    <div><strong><?php echo htmlspecialchars($licitatie['nume_client']); ?></strong></div>
                    <div><?php echo htmlspecialchars($licitatie['loc_descarcare']); ?></div>
                    <div class="text-uppercase"><?php echo htmlspecialchars($licitatie['produs']); ?></div>
                    <div><?php echo htmlspecialchars($licitatie['manager_company']); ?></div>
                    <div class="timp-raman"><?php echo $timp_ramas; ?></div>
                    <div>
                        <?php if ($has_oferta): ?>
                            <?php 
                            $status_class = 'status-' . str_replace('ă', 'a', $oferta_status);
                            $status_text = ucfirst($oferta_status);
                            ?>
                            <div class="oferta-status <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </div>
                        <?php else: ?>
                            <div style="color: #666; font-size: 0.8rem;">Nicio ofertă</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if (!$has_oferta): ?>
                            <a href="trimitere_oferta.php?licitatie_id=<?php echo $licitatie['id']; ?>" class="btn btn-oferta">
                                📨 TRIMITE OFERTĂ
                            </a>
                        <?php else: ?>
                            <a href="vizualizare_oferta.php?licitatie_id=<?php echo $licitatie['id']; ?>" class="btn">
                                👁️ VIZUALIZEAZĂ
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Ofertele Mele -->
        <div class="licitatii-section">
            <h2>📊 OFERTELE MELE</h2>
            
            <?php if (empty($ofertele_mele)): ?>
                <div class="no-licitatii">
                    <h3>Nu ai trimis nicio ofertă încă</h3>
                    <p>Alege o licitație activă și trimite prima ta ofertă!</p>
                </div>
            <?php else: ?>
                <div class="table-header">
                    <div>ID LICITAȚIE</div>
                    <div>CLIENT</div>
                    <div>DESTINAȚIE</div>
                    <div>PRET OFERIT</div>
                    <div>DATA OFERTĂ</div>
                    <div>STATUS</div>
                    <div>ACȚIUNE</div>
                </div>
                
                <?php foreach ($ofertele_mele as $oferta): ?>
                <div class="table-row">
                    <div>#<?php echo $oferta['licitatie_id']; ?></div>
                    <div><strong><?php echo htmlspecialchars($oferta['nume_client']); ?></strong></div>
                    <div><?php echo htmlspecialchars($oferta['destinatie']); ?></div>
                    <div><strong><?php echo number_format($oferta['pret_oferit'], 2); ?> <?php echo $oferta['moneda']; ?></strong></div>
                    <div><?php echo date('d.m.Y H:i', strtotime($oferta['created_at'])); ?></div>
                    <div>
                        <?php 
                        $status_class = 'status-' . str_replace('ă', 'a', $oferta['status']);
                        $status_text = ucfirst($oferta['status']);
                        ?>
                        <div class="oferta-status <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </div>
                    </div>
                    <div>
                        <a href="vizualizare_oferta.php?licitatie_id=<?php echo $oferta['licitatie_id']; ?>" class="btn">
                            👁️ DETALII
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>