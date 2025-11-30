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

$database = new Database();
$db = $database->getConnection();
$expeditieModel = new Expeditie($db);
$licitatieModel = new Licitatie($db);

// Obține toate expedițiile managerului
$expeditii = $expeditieModel->getExpeditiiByManager($_SESSION['user_id']);

// Funcție pentru calcularea timpului rămas
function getTimpRamas($data_start, $termen_ore) {
    if (!$data_start) return '';
    
    $end_time = strtotime($data_start) + ($termen_ore * 3600);
    $now = time();
    $diff = $end_time - $now;
    
    if ($diff <= 0) return 'Expirat';
    
    $hours = floor($diff / 3600);
    $minutes = floor(($diff % 3600) / 60);
    
    return $hours . 'h ' . $minutes . 'm Rămas';
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listă Expediții - Sistem Logistic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .expedities-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .expedities-section h2 {
            color: #667eea;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .expeditie-item {
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .expeditie-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            display: grid;
            grid-template-columns: 80px 1fr 1fr 1fr 1fr 1fr 1fr;
            gap: 1rem;
            align-items: center;
            border-bottom: 1px solid #e1e5e9;
        }
        .expeditie-id {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }
        .expeditie-details {
            padding: 1rem 1.5rem;
            background: white;
        }
        .status-planificata {
            background: #fff3cd;
            color: #856404;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-in_licitatie {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-atribuita {
            background: #d4edda;
            color: #155724;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .timp-raman {
            background: #ffeaa7;
            color: #2d3436;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            font-weight: 600;
            margin: 0.5rem 0;
        }
        .oferta-item {
            background: #f8f9fa;
            padding: 0.75rem 1rem;
            margin: 0.5rem 0;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .atribuire-info {
            background: #d4edda;
            padding: 1rem;
            border-radius: 5px;
            margin: 0.5rem 0;
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
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .btn-small {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📦 Listă Expediții</h1>
        <div class="user-info">
            <span>Manager: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="dashboard_manager.php" class="back-btn">← Dashboard</a>
            <a href="creare_expeditie.php" class="back-btn">➕ Expediție Nouă</a>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="expedities-section">
            <h2>Expedițiile Mele</h2>
            
            <?php if (empty($expeditii)): ?>
                <p style="text-align: center; padding: 2rem;">Nu ai nicio expediție creată încă.</p>
            <?php else: ?>
                <?php foreach ($expeditii as $expeditie): ?>
                <div class="expeditie-item">
                    <!-- Header cu informații de bază -->
                    <div class="expeditie-header">
                        <div class="expeditie-id">#<?php echo $expeditie['id']; ?></div>
                        <div><?php echo date('Y-m-d', strtotime($expeditie['data_creare'])); ?></div>
                        <div><strong><?php echo htmlspecialchars($expeditie['nume_client']); ?></strong></div>
                        <div><?php echo htmlspecialchars($expeditie['destinatie']); ?></div>
                        <div><?php echo htmlspecialchars($expeditie['produs']); ?></div>
                        <div>
                            <?php if ($expeditie['tarif_tinta']): ?>
                                <strong><?php echo number_format($expeditie['tarif_tinta'], 2); ?> <?php echo $expeditie['moneda']; ?></strong>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php 
                            $status_class = 'status-' . $expeditie['status'];
                            $status_text = ucfirst(str_replace('_', ' ', $expeditie['status']));
                            ?>
                            <span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </div>
                    </div>

                    <!-- Detalii expandate -->
                    <div class="expeditie-details">
                        <div style="margin-bottom: 1rem;">
                            <strong>Data Transportului:</strong> <?php echo date('Y-m-d', strtotime($expeditie['data_transport'])); ?>
                        </div>

                        <?php if ($expeditie['status'] === 'in_licitatie' && $expeditie['licitatie_id']): ?>
                            <!-- Expediție în licitație -->
                            <div class="timp-raman">
                                Termen: <?php echo getTimpRamas($expeditie['data_start'], $expeditie['termen_ore']); ?>
                            </div>
                            
                            <?php if ($expeditie['numar_oferte'] > 0): ?>
                                <div style="margin: 1rem 0;">
                                    <strong>Oferte Primite (<?php echo $expeditie['numar_oferte']; ?>):</strong>
                                    <!-- Aici vor fi afișate ofertele -->
                                    <div class="oferta-item">
                                        90.00 EUR - Trans Rapid SRL
                                    </div>
                                </div>
                                <div style="color: #666; font-size: 0.875rem;">
                                    Atribuirea este blocată. Așteptați expirarea termenului de licitație 
                                    pentru a permite mai multe oferte.
                                </div>
                            <?php else: ?>
                                <div style="color: #666; font-size: 0.875rem;">
                                    Încă nu s-au primit oferte. Așteptați expirarea termenului de licitație.
                                </div>
                            <?php endif; ?>

                        <?php elseif ($expeditie['status'] === 'atribuita'): ?>
                            <!-- Expediție atribuită -->
                            <div class="atribuire-info">
                                <strong>Atribuit către:</strong> 
                                <?php echo $expeditie['transportator_company'] ? htmlspecialchars($expeditie['transportator_company']) : 'Transportator'; ?>
                                (<?php echo number_format($expeditie['pret_final'], 2); ?> <?php echo $expeditie['moneda']; ?>)
                                
                                <?php if ($expeditie['numar_camion'] || $expeditie['nume_sofer']): ?>
                                    <div style="margin-top: 0.5rem;">
                                        <?php if ($expeditie['numar_camion']): ?>
                                            <strong>Camion:</strong> <?php echo htmlspecialchars($expeditie['numar_camion']); ?>
                                        <?php endif; ?>
                                        <?php if ($expeditie['nume_sofer']): ?>
                                            <br><strong>Șofer:</strong> <?php echo htmlspecialchars($expeditie['nume_sofer']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 0.5rem; color: #666;">
                                        Așteptăm detaliile camionului/șoferului.
                                    </div>
                                <?php endif; ?>
                            </div>

                        <?php elseif ($expeditie['status'] === 'planificata'): ?>
                            <!-- Expediție planificată - buton start licitație -->
                            <div style="text-align: center; margin-top: 1rem;">
                                <a href="start_licitatie.php?expeditie_id=<?php echo $expeditie['id']; ?>" class="btn">
                                    ⚡ Start Licitatie
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>