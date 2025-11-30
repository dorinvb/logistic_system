<?php
session_start();

// Include path corect
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/security.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../controllers/AuthController.php';

$authController = new AuthController();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'email' => $_POST['email'],
        'role' => $_POST['role'],
        'company_name' => $_POST['company_name'] ?? '',
        'phone' => $_POST['phone'] ?? ''
    ];
    
    $result = $authController->register($data);
    
    if ($result['success']) {
        $success = "Cont creat cu succes! Poți să te loghezi.";
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
    <title>Înregistrare - Sistem Logistic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
        }
        h2 { text-align: center; margin-bottom: 1.5rem; color: #333; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; color: #555; font-weight: 500; }
        input, select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        input:focus, select:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }
        .btn:hover { background: #5a6fd8; }
        .success { background: #efe; color: #363; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; }
        .error { background: #fee; color: #c33; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; }
        .login-link { text-align: center; margin-top: 1rem; }
        .login-link a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>📝 Înregistrare Sistem Logistic</h2>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username*:</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Parolă* (minim 6 caractere):</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email*:</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="role">Rol*:</label>
                <select id="role" name="role" required>
                    <option value="">Selectează rolul</option>
                    <option value="manager" <?php echo (($_POST['role'] ?? '') === 'manager') ? 'selected' : ''; ?>>Manager Transport</option>
                    <option value="transportator" <?php echo (($_POST['role'] ?? '') === 'transportator') ? 'selected' : ''; ?>>Transportator</option>
                    <option value="supervizor" <?php echo (($_POST['role'] ?? '') === 'supervizor') ? 'selected' : ''; ?>>Supervizor</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="company_name">Nume Firmă:</label>
                <input type="text" id="company_name" name="company_name" 
                       value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Telefon:</label>
                <input type="text" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>
            
            <button type="submit" class="btn">Înregistrează cont</button>
        </form>
        
        <div class="login-link">
            <p>Ai deja cont? <a href="login.php">Loghează-te aici</a></p>
        </div>
    </div>
</body>
</html>