<?php
session_start();
require_once 'config.php';

$mensaje = '';
$error = '';

// Verificar que el usuario pasó por todo el proceso
if (!isset($_SESSION['email_para_reset']) || !isset($_SESSION['reset_aprobado']) || $_SESSION['reset_aprobado'] !== true) {
    header("Location: olvide_password.php");
    exit();
}

$email = $_SESSION['email_para_reset'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    // Validaciones
    if (empty($nueva_password) || empty($confirmar_password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif (strlen($nueva_password) < 6) {
        $error = "La nueva contraseña debe tener al menos 6 caracteres.";
    } elseif ($nueva_password !== $confirmar_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        try {
            // Hashear la nueva contraseña
            $nueva_password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
            
            // Actualizar la contraseña en la base de datos
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE email = ? AND activo = 1");
            $resultado = $stmt->execute([$nueva_password_hash, $email]);
            
            if ($resultado && $stmt->rowCount() > 0) {
                // Limpiar las sesiones
                unset($_SESSION['email_para_reset']);
                unset($_SESSION['reset_aprobado']);
                
                // Redirigir al login con mensaje de éxito
                $_SESSION['mensaje_login'] = "¡Contraseña actualizada exitosamente! Ya puedes iniciar sesión con tu nueva contraseña.";
                header("Location: auth.php");
                exit();
            } else {
                $error = "Error al actualizar la contraseña. Intenta de nuevo.";
            }
        } catch(PDOException $e) {
            $error = "Error en el sistema. Intenta más tarde.";
            error_log("Error en reset_password.php: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña - Mercado Huasteco</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #006666 0%, #004d4d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo h1 {
            color: #006666;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .logo p {
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #006666;
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(45deg, #006666, #004d4d);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: linear-gradient(45deg, #004d4d, #003333);
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #c3e6cb;
        }
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #bee5eb;
        }
        
        .email-info {
            background: #e7f3ff;
            padding: 0.5rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            color: #0066cc;
            font-weight: 500;
        }
        
        .security-tips {
            background: #fff3cd;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #ffeaa7;
        }
        
        .security-tips h4 {
            color: #856404;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        
        .security-tips ul {
            color: #856404;
            font-size: 0.9rem;
            margin-left: 1rem;
        }
        
        .security-tips li {
            margin-bottom: 0.25rem;
        }
        
        small {
            color: #6c757d;
            font-size: 0.875rem;
            display: block;
            margin-top: 0.25rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔐 Mercado Huasteco</h1>
            <p>Nueva Contraseña</p>
        </div>
        
        <div class="info">
            <strong>Paso 3 de 3:</strong> Crea tu nueva contraseña segura.
        </div>
        
        <div class="email-info">
            Cambiando contraseña para: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="security-tips">
            <h4>💡 Consejos para una contraseña segura:</h4>
            <ul>
                <li>Mínimo 6 caracteres (recomendado 8+)</li>
                <li>Combina letras, números y símbolos</li>
                <li>No uses información personal</li>
                <li>Evita contraseñas comunes</li>
            </ul>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nueva_password">Nueva Contraseña:</label>
                <input type="password" id="nueva_password" name="nueva_password" required 
                       minlength="6" onkeyup="checkPasswordStrength()">
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirmar_password">Confirmar Nueva Contraseña:</label>
                <input type="password" id="confirmar_password" name="confirmar_password" required 
                       minlength="6" onkeyup="checkPasswordMatch()">
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <button type="submit" class="btn">Actualizar Contraseña</button>
        </form>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('nueva_password').value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                strengthDiv.innerHTML = '<span class="strength-weak">🔴 Contraseña débil</span>';
            } else if (strength <= 3) {
                strengthDiv.innerHTML = '<span class="strength-medium">🟡 Contraseña media</span>';
            } else {
                strengthDiv.innerHTML = '<span class="strength-strong">🟢 Contraseña fuerte</span>';
            }
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('nueva_password').value;
            const confirm = document.getElementById('confirmar_password').value;
            const matchDiv = document.getElementById('password-match');
            
            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchDiv.innerHTML = '<span class="strength-strong">✅ Las contraseñas coinciden</span>';
            } else {
                matchDiv.innerHTML = '<span class="strength-weak">❌ Las contraseñas no coinciden</span>';
            }
        }
    </script>
</body>
</html>