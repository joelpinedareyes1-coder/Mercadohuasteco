<?php
session_start();
require_once 'config.php';

$mensaje = '';
$error = '';

// Si el usuario ya está logueado, redirigir
if (esta_logueado()) {
    redirigir_por_rol();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = limpiar_entrada($_POST['email']);
    
    // Validaciones
    if (empty($email)) {
        $error = "El email es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "El formato del email no es válido.";
    } else {
        try {
            // Verificar si el email existe y tiene pregunta secreta
            $stmt = $pdo->prepare("SELECT id, pregunta_secreta FROM usuarios WHERE email = ? AND activo = 1");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                $error = "Email no encontrado o cuenta inactiva.";
            } elseif (empty($usuario['pregunta_secreta'])) {
                $error = "Esta cuenta no tiene configurada una pregunta secreta. Contacta al administrador.";
            } else {
                // Guardar email en sesión y redirigir
                $_SESSION['email_para_reset'] = $email;
                header("Location: responder_pregunta.php");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Error en el sistema. Intenta más tarde.";
            error_log("Error en olvide_password.php: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - Mercado Huasteco</title>
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
            max-width: 400px;
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
        
        input[type="email"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        input[type="email"]:focus {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔐 Mercado Huasteco</h1>
            <p>Recuperar Contraseña</p>
        </div>
        
        <div class="info">
            <strong>Paso 1 de 3:</strong> Ingresa tu email para comenzar el proceso de recuperación.
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email de tu cuenta:</label>
                <input type="email" id="email" name="email" required 
                       placeholder="ejemplo@correo.com"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <small>Ingresa el email con el que te registraste</small>
            </div>
            
            <button type="submit" class="btn">Continuar</button>
        </form>
        
        <div class="links">
            <p><a href="auth.php">← Volver al inicio de sesión</a></p>
            <p><a href="auth.php">¿No tienes cuenta? Regístrate</a></p>
        </div>
    </div>
</body>
</html>