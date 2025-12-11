<?php
session_start();
require_once 'config.php';

$mensaje = '';
$error = '';

// Verificar que existe la sesión del email
if (!isset($_SESSION['email_para_reset'])) {
    header("Location: olvide_password.php");
    exit();
}

$email = $_SESSION['email_para_reset'];

// Obtener la pregunta secreta del usuario
try {
    $stmt = $pdo->prepare("SELECT pregunta_secreta, respuesta_secreta FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        // Si no se encuentra el usuario, limpiar sesión y redirigir
        unset($_SESSION['email_para_reset']);
        header("Location: olvide_password.php");
        exit();
    }
    
    $pregunta_secreta = $usuario['pregunta_secreta'];
    $respuesta_secreta_hash = $usuario['respuesta_secreta'];
    
} catch(PDOException $e) {
    $error = "Error en el sistema. Intenta más tarde.";
    error_log("Error en responder_pregunta.php: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $respuesta = $_POST['respuesta'];
    
    // Validaciones
    if (empty($respuesta)) {
        $error = "La respuesta es obligatoria.";
    } else {
        // Verificar la respuesta usando password_verify
        if (password_verify($respuesta, $respuesta_secreta_hash)) {
            // Respuesta correcta, aprobar el reset
            $_SESSION['reset_aprobado'] = true;
            header("Location: reset_password.php");
            exit();
        } else {
            $error = "Respuesta incorrecta. Intenta de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pregunta Secreta - Mercado Huasteco</title>
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
        
        input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input[type="text"]:focus {
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
        
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #bee5eb;
        }
        
        .question-box {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #006666;
        }
        
        .question-box h3 {
            color: #006666;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .question-box p {
            color: #495057;
            font-style: italic;
            font-size: 1rem;
        }
        
        .links {
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .links a {
            color: #006666;
            text-decoration: none;
            font-weight: 500;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
        
        small {
            color: #6c757d;
            font-size: 0.875rem;
            display: block;
            margin-top: 0.25rem;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔐 Mercado Huasteco</h1>
            <p>Pregunta Secreta</p>
        </div>
        
        <div class="info">
            <strong>Paso 2 de 3:</strong> Responde tu pregunta secreta para verificar tu identidad.
        </div>
        
        <div class="email-info">
            Recuperando contraseña para: <strong><?php echo htmlspecialchars($email); ?></strong>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="question-box">
            <h3>Tu pregunta secreta es:</h3>
            <p>"<?php echo htmlspecialchars($pregunta_secreta); ?>"</p>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="respuesta">Tu respuesta:</label>
                <input type="text" id="respuesta" name="respuesta" required 
                       placeholder="Escribe tu respuesta exacta">
                <small>La respuesta debe coincidir exactamente con la que registraste</small>
            </div>
            
            <button type="submit" class="btn">Verificar Respuesta</button>
        </form>
        
        <div class="links">
            <p><a href="olvide_password.php">← Cambiar email</a></p>
            <p><a href="auth.php">Cancelar y volver al login</a></p>
        </div>
    </div>
</body>
</html>