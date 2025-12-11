<?php
require_once 'config.php';

$error = '';
$mensaje = '';

// Verificar si hay mensaje de éxito desde reset de contraseña
if (isset($_SESSION['mensaje_login'])) {
    $mensaje = $_SESSION['mensaje_login'];
    unset($_SESSION['mensaje_login']);
}

// Si el usuario ya está logueado, redirigir
if (esta_logueado()) {
    redirigir_por_rol();
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        // Lógica de inicio de sesión
        $email = limpiar_entrada($_POST['email']);
        $password = $_POST['password'];
        
        // Validaciones básicas
        if (empty($email) || empty($password)) {
            $error = "Email y contraseña son obligatorios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del email no es válido.";
        } else {
            try {
                // Buscar usuario por email
                $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND activo = 1");
                $stmt->execute([$email]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario && password_verify($password, $usuario['password'])) {
                    // Login exitoso - crear sesión
                    $_SESSION['user_id'] = $usuario['id'];
                    $_SESSION['nombre'] = $usuario['nombre'];
                    $_SESSION['email'] = $usuario['email'];
                    $_SESSION['rol'] = $usuario['rol'];
                    
                    // Redirigir según el rol
                    redirigir_por_rol();
                } else {
                    $error = "Email o contraseña incorrectos.";
                }
            } catch(PDOException $e) {
                $error = "Error en el sistema: " . $e->getMessage();
            }
        }
    } elseif ($action === 'register') {
        // Lógica de registro
        $nombre = limpiar_entrada($_POST['nombre']);
        $email = limpiar_entrada($_POST['email']);
        $password = $_POST['password'];
        $confirmar_password = $_POST['confirmar_password'];
        $rol = isset($_POST['rol']) ? limpiar_entrada($_POST['rol']) : '';
        
        // Debug: verificar qué se está recibiendo
        error_log("Datos de registro recibidos - Nombre: $nombre, Email: $email, Rol: '$rol'");
        
        // Validaciones
        if (empty($nombre) || empty($email) || empty($password) || empty($rol)) {
            $error = "Todos los campos son obligatorios. Rol recibido: '$rol'";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "El formato del email no es válido.";
        } elseif (strlen($password) < 6) {
            $error = "La contraseña debe tener al menos 6 caracteres.";
        } elseif ($password !== $confirmar_password) {
            $error = "Las contraseñas no coinciden.";
        } elseif (!in_array($rol, ['cliente', 'vendedor'])) {
            $error = "Rol no válido. Rol recibido: '$rol'";
        } else {
            try {
                // Verificar si el email ya existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Este email ya está registrado.";
                } else {
                    // Encriptar la contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insertar nuevo usuario
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$nombre, $email, $password_hash, $rol]);
                    
                    if ($result) {
                        $user_id = $pdo->lastInsertId();
                        error_log("Usuario registrado exitosamente - ID: $user_id, Rol: $rol");
                        
                        // Verificar que se guardó correctamente
                        $verify_stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                        $verify_stmt->execute([$user_id]);
                        $saved_rol = $verify_stmt->fetchColumn();
                        error_log("Rol guardado en BD: '$saved_rol'");
                        
                        $mensaje = "Registro exitoso. Ya puedes iniciar sesión.";
                    } else {
                        $error = "Error al registrar el usuario.";
                    }
                }
            } catch(PDOException $e) {
                $error = "Error en el registro: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión / Registrarse - Mercado Huasteco</title>
    <link rel="stylesheet" href="auth_styles.css">
    <link rel="stylesheet" href="css/auth-mobile.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Estilos para mensajes de error y éxito */
        .mensaje {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            max-width: 350px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
        }
        
        .mensaje.error {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
        }
        
        .mensaje.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .mensaje-content {
            display: flex;
            align-items: center;
        }
        
        /* Asegurar que el botón de registro sea visible */
        .sign-up-container .btn,
        .sign-up-container button[type="submit"] {
            width: 100% !important;
            padding: 12px 45px !important;
            margin: 20px 0 !important;
            background: linear-gradient(135deg, #006666, #004d4d) !important;
            color: white !important;
            border: none !important;
            border-radius: 25px !important;
            font-size: 16px !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 999 !important;
            height: auto !important;
            min-height: 45px !important;
        }
        
        .sign-up-container .btn:hover,
        .sign-up-container button[type="submit"]:hover {
            background: linear-gradient(135deg, #004d4d, #006666) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 15px rgba(0, 102, 102, 0.3) !important;
        }
        
        /* Formularios se controlan con JavaScript en móvil */
            gap: 10px;
        }
        
        .mensaje-content i {
            font-size: 18px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Ocultar enlaces de redes sociales */
        .social-container {
            display: none;
        }
        
        .form span:not(.role-text):not(.checkmark):not(.checkmark-small) {
            display: none;
        }
        
        /* Ajustar espaciado sin redes sociales */
        .form h1 {
            margin-bottom: 30px;
        }
        
        /* Estilos mejorados para el selector de rol */
        .role-selection-container {
            margin: 10px 0;
            width: 100%;
        }
        
        .role-selection-title {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #333;
            border-left: 2px solid #007bff;
            padding-left: 8px;
            line-height: 1.2;
        }
        
        .role-selection-title i {
            color: #007bff;
            font-size: 16px;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.2));
            padding: 6px;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .role-selection {
            display: flex !important;
            gap: 8px;
            width: 100%;
        }
        
        .role-card {
            flex: 1;
            cursor: pointer;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            background: white;
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }
        
        .role-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.15);
        }
        
        .role-card input[type="radio"] {
            display: none;
        }
        
        .role-card-content {
            padding: 10px 8px;
            text-align: center;
            position: relative;
        }
        
        .role-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-size: 16px;
            color: white;
            transition: all 0.3s ease;
        }
        
        .cliente-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .vendedor-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .role-info h4 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        .role-info p {
            margin: 0;
            font-size: 11px;
            font-size: 12px;
            color: #666;
            line-height: 1.4;
        }
        
        .role-checkmark {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #28a745;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            opacity: 0;
            transform: scale(0);
            transition: all 0.3s ease;
        }
        
        /* Estado seleccionado */
        .role-card input[type="radio"]:checked + .role-card-content {
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 123, 255, 0.1) 100%);
        }
        
        .role-card input[type="radio"]:checked + .role-card-content .role-checkmark {
            opacity: 1;
            transform: scale(1);
        }
        
        .role-card:has(input[type="radio"]:checked) {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
        }
        
        .role-card:has(input[type="radio"]:checked) .role-icon {
            transform: scale(1.1);
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .role-selection {
                flex-direction: column;
                gap: 12px;
            }
            
            .role-card-content {
                padding: 15px 12px;
            }
            
            .role-icon {
                width: 40px;
                height: 40px;
                font-size: 16px;
                margin-bottom: 12px;
            }
            
            .role-info h4 {
                font-size: 14px;
            }
            
            .role-info p {
                font-size: 11px;
            }
            
            /* Botón de registro aún más pequeño en pantallas pequeñas */
            .sign-up-container .btn,
            .sign-up-container button[type="submit"] {
                padding: 8px 16px !important;
                font-size: 13px !important;
                min-height: 40px !important;
                margin: 10px 0 !important;
            }
            
            /* Formulario de registro ultra compacto */
            .sign-up-container .form {
                padding: 10px 15px !important;
            }
            
            .sign-up-container h1 {
                font-size: 1.1rem !important;
                margin-bottom: 8px !important;
                margin-top: 3px !important;
            }
            
            .sign-up-container .input-group {
                margin: 6px 0 !important;
            }
            
            .sign-up-container .input-group input {
                padding: 8px 10px 8px 36px !important;
                font-size: 14px !important;
                min-height: 40px !important;
            }
            
            .sign-up-container .input-group i {
                font-size: 14px !important;
                left: 10px !important;
            }
            
            .sign-up-container .role-selection-container {
                margin: 6px 0 !important;
            }
            
            .sign-up-container .role-selection-title {
                font-size: 11px !important;
                margin-bottom: 4px !important;
            }
            
            .sign-up-container .role-card-content {
                padding: 6px 4px !important;
            }
            
            .sign-up-container .role-icon {
                width: 26px !important;
                height: 26px !important;
                font-size: 12px !important;
                margin-bottom: 4px !important;
            }
            
            .sign-up-container .role-info h4 {
                font-size: 12px !important;
            }
            
            .sign-up-container .role-info p {
                font-size: 9px !important;
            }
            
            .sign-up-container .mobile-switch {
                margin-top: 8px !important;
                padding: 8px !important;
            }
            
            .sign-up-container .mobile-switch p {
                font-size: 13px !important;
            }
        }
        
        /* Hacer el título "Crear Cuenta" más pequeño */
        .sign-up-container h1 {
            font-size: 1.1rem !important;
            margin-bottom: 15px !important;
        }
        
        /* ===== RESPONSIVE PARA MÓVILES ===== */
        @media (max-width: 768px) {
            /* Ocultar el panel verde overlay */
            .overlay-container {
                display: none !important;
            }
            
            /* Los formularios ocupan todo el ancho */
            .sign-in-container,
            .sign-up-container {
                width: 100% !important;
                left: 0 !important;
                transform: none !important;
                position: relative !important;
            }
            
            /* El container principal se ajusta */
            .container {
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                min-height: 100vh !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 10px 0 !important;
            }
            
            /* Mostrar solo un formulario a la vez */
            .sign-in-container {
                display: block;
            }
            
            .sign-up-container {
                display: none;
            }
            
            /* Cuando está en modo registro */
            .container.right-panel-active .sign-in-container {
                display: none;
            }
            
            .container.right-panel-active .sign-up-container {
                display: block;
            }
            
            /* Estilos para los formularios en móvil */
            .form {
                padding: 20px !important;
                max-width: 100% !important;
            }
            
            .form h1 {
                font-size: 1.5rem !important;
                margin-bottom: 20px !important;
            }
            
            /* Optimización especial para inicio de sesión en móvil */
            .sign-in-container .form h1 {
                font-size: 1.8rem !important;
                margin-bottom: 25px !important;
                margin-top: 10px !important;
            }
            
            /* Inputs más grandes y cómodos para móvil */
            .input-group {
                margin: 12px 0 !important;
            }
            
            .input-group input {
                padding: 14px 15px 14px 45px !important;
                font-size: 16px !important;
                min-height: 50px !important;
                border-radius: 8px !important;
            }
            
            .input-group i {
                font-size: 18px !important;
                left: 15px !important;
            }
            
            /* Botones más grandes y táctiles */
            .btn, button[type="submit"] {
                padding: 15px 30px !important;
                font-size: 17px !important;
                min-height: 52px !important;
                margin: 20px 0 !important;
                border-radius: 10px !important;
                font-weight: 600 !important;
            }
            
            /* ===== FORMULARIO DE REGISTRO COMPACTO PARA MÓVIL ===== */
            .sign-up-container .form {
                padding: 15px 20px !important;
            }
            
            .sign-up-container h1 {
                font-size: 1.3rem !important;
                margin-bottom: 12px !important;
                margin-top: 5px !important;
            }
            
            .sign-up-container .input-group {
                margin: 8px 0 !important;
            }
            
            .sign-up-container .input-group input {
                padding: 10px 12px 10px 40px !important;
                font-size: 15px !important;
                min-height: 44px !important;
            }
            
            .sign-up-container .input-group i {
                font-size: 16px !important;
                left: 12px !important;
            }
            
            .sign-up-container .btn,
            .sign-up-container button[type="submit"] {
                padding: 10px 20px !important;
                font-size: 14px !important;
                min-height: 44px !important;
                margin: 12px 0 !important;
            }
            
            .sign-up-container .mobile-switch {
                margin-top: 12px !important;
                padding: 12px !important;
            }
            
            /* Selector de rol más compacto */
            .sign-up-container .role-selection-container {
                margin: 8px 0 !important;
            }
            
            .sign-up-container .role-selection-title {
                font-size: 12px !important;
                margin-bottom: 6px !important;
                padding-left: 6px !important;
            }
            
            .sign-up-container .role-selection-title i {
                font-size: 14px !important;
                width: 24px !important;
                height: 24px !important;
                padding: 4px !important;
            }
            
            .sign-up-container .role-card {
                border-radius: 8px !important;
            }
            
            .sign-up-container .role-card-content {
                padding: 8px 6px !important;
            }
            
            .sign-up-container .role-icon {
                width: 30px !important;
                height: 30px !important;
                font-size: 14px !important;
                margin-bottom: 6px !important;
            }
            
            .sign-up-container .role-info h4 {
                font-size: 13px !important;
                margin-bottom: 2px !important;
            }
            
            .sign-up-container .role-info p {
                font-size: 10px !important;
                line-height: 1.3 !important;
            }
            
            .sign-up-container .role-checkmark {
                width: 20px !important;
                height: 20px !important;
                font-size: 10px !important;
                top: 8px !important;
                right: 8px !important;
            }
            
            /* Opciones del formulario más espaciadas */
            .form-options {
                margin: 15px 0 !important;
                flex-direction: column !important;
                gap: 12px !important;
                align-items: flex-start !important;
            }
            
            .remember-me {
                font-size: 15px !important;
            }
            
            .forgot-password {
                font-size: 15px !important;
                padding: 8px 0 !important;
            }
            
            /* Botones de cambio de formulario */
            .mobile-switch {
                text-align: center;
                margin-top: 20px;
                padding: 15px;
                background: rgba(0, 102, 102, 0.1);
                border-radius: 10px;
            }
            
            .mobile-switch p {
                font-size: 15px !important;
                margin: 0 !important;
            }
            
            .mobile-switch a {
                color: #006666;
                text-decoration: none;
                font-weight: 600;
                font-size: 16px !important;
            }
            
            .mobile-switch a:hover {
                text-decoration: underline;
            }
            
            /* Espaciado del body para evitar que el teclado tape campos */
            body {
                padding-bottom: 100px !important;
            }
            
            /* Mejorar visibilidad de mensajes en móvil */
            .mensaje {
                top: 10px !important;
                right: 10px !important;
                left: 10px !important;
                max-width: calc(100% - 20px) !important;
                font-size: 14px !important;
            }
            
            /* Prevenir zoom en inputs en iOS */
            input, select, textarea {
                font-size: 16px !important;
            }
            
            /* Mejorar área táctil de checkboxes y links */
            .remember-me {
                padding: 10px 0 !important;
                min-height: 44px !important;
                display: flex !important;
                align-items: center !important;
            }
            
            /* Espaciado cómodo entre elementos */
            .sign-in-container .form {
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                padding: 30px 20px !important;
            }
            
            /* Hacer el título de inicio de sesión más visible */
            .sign-in-container h1 {
                color: #006666 !important;
                font-weight: 700 !important;
            }
            
            /* Mejorar contraste de placeholders */
            input::placeholder {
                color: #999 !important;
                opacity: 1 !important;
            }
            
            /* Feedback visual al tocar inputs */
            input:focus {
                border: 2px solid #006666 !important;
                box-shadow: 0 0 0 3px rgba(0, 102, 102, 0.1) !important;
                transform: scale(1.01) !important;
            }
            
            /* Mejorar botones en móvil */
            .sign-in-container .btn {
                background: linear-gradient(135deg, #006666, #004d4d) !important;
                box-shadow: 0 4px 15px rgba(0, 102, 102, 0.3) !important;
                text-transform: uppercase !important;
                letter-spacing: 0.5px !important;
            }
            
            .sign-in-container .btn:active {
                transform: scale(0.98) !important;
            }
        }
    </style>
</head>
<body>
    <?php if ($error): ?>
        <div class="mensaje error">
            <div class="mensaje-content">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($mensaje): ?>
        <div class="mensaje success">
            <div class="mensaje-content">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $mensaje; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="container" id="container">
        <!-- Formulario de Registro -->
        <div class="form-container sign-up-container">
            <form action="auth.php" method="POST" class="form">
                <input type="hidden" name="action" value="register">
                <h1>Crear Cuenta</h1>
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nombre" placeholder="Nombre completo" required 
                           value="<?php echo isset($_POST['nombre']) && $_POST['action'] === 'register' ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required 
                           value="<?php echo isset($_POST['email']) && $_POST['action'] === 'register' ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirmar_password" placeholder="Confirmar contraseña" required>
                </div>
                
                <div class="role-selection-container">
                    <div class="role-selection-title">
                        <i class="fas fa-user-tag"></i>
                        Selecciona tu tipo de cuenta
                    </div>
                    
                    <div class="role-selection">
                        <label class="role-card" for="rol_cliente">
                            <input type="radio" name="rol" value="cliente" id="rol_cliente"
                                   <?php 
                                   $selected_rol = $_POST['rol'] ?? 'cliente';
                                   echo ($selected_rol === 'cliente') ? 'checked' : ''; 
                                   ?>>
                            <div class="role-card-content">
                                <div class="role-icon cliente-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="role-info">
                                    <h4>Cliente</h4>
                                    <p>Explora y compra en tiendas locales</p>
                                </div>
                                <div class="role-checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </label>
                        
                        <label class="role-card" for="rol_vendedor">
                            <input type="radio" name="rol" value="vendedor" id="rol_vendedor"
                                   <?php echo ($selected_rol === 'vendedor') ? 'checked' : ''; ?>>
                            <div class="role-card-content">
                                <div class="role-icon vendedor-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div class="role-info">
                                    <h4>Vendedor</h4>
                                    <p>Crea y gestiona tu tienda online</p>
                                </div>
                                <div class="role-checkmark">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn">Registrarse</button>
                
                <!-- Link para móviles -->
                <div class="mobile-switch">
                    <p>¿Ya tienes cuenta? <a href="#" onclick="switchToSignIn(); return false;">Inicia Sesión</a></p>
                </div>
            </form>
        </div>

        <!-- Formulario de Inicio de Sesión -->
        <div class="form-container sign-in-container">
            <form action="auth.php" method="POST" class="form">
                <input type="hidden" name="action" value="login">
                <h1>Iniciar Sesión</h1>
                
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required 
                           value="<?php echo isset($_POST['email']) && $_POST['action'] === 'login' ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>
                
                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span class="checkmark-small"></span>
                        Recordarme
                    </label>
                    <a href="olvide_password.php" class="forgot-password">¿Olvidaste tu contraseña?</a>
                </div>
                
                <button type="submit" class="btn">Iniciar Sesión</button>
                
                <!-- Link para móviles -->
                <div class="mobile-switch">
                    <p>¿No tienes cuenta? <a href="#" onclick="switchToSignUp(); return false;">Regístrate</a></p>
                </div>
            </form>
        </div>

        <!-- Panel de Overlay -->
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>¡Bienvenido de vuelta!</h1>
                    <p>Para mantenerte conectado con Mercado Huasteco, por favor inicia sesión con tu información personal</p>
                    <button class="btn ghost" id="signIn">Iniciar Sesión</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>¡Hola, Amigo!</h1>
                    <p>Ingresa tus datos personales y comienza tu viaje con nosotros en Mercado Huasteco</p>
                    <button class="btn ghost" id="signUp">Registrarse</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Detectar si es dispositivo móvil
        function isMobile() {
            return window.innerWidth <= 768 || /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        }

        // Detectar si es iOS
        function isIOS() {
            return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        }

        // Elementos del DOM
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        // Variables para manejar el estado
        let isSignUpMode = false;
        
        // Función para verificar el estado actual
        function checkCurrentMode() {
            const hasRightPanelActive = container.classList.contains('right-panel-active');
            isSignUpMode = hasRightPanelActive;
            console.log('Estado actual - right-panel-active:', hasRightPanelActive, 'isSignUpMode:', isSignUpMode);
            return isSignUpMode;
        }

        // Función para cambiar a modo registro
        function switchToSignUp() {
            container.classList.add("right-panel-active");
            isSignUpMode = true;
            
            // ===== SOLUCIÓN MÓVIL: Control directo del display =====
            const signUpContainer = document.querySelector('.sign-up-container');
            const signInContainer = document.querySelector('.sign-in-container');
            
            if (isMobile()) {
                // En móvil: controlar display directamente
                if (signUpContainer) signUpContainer.style.display = 'block';
                if (signInContainer) signInContainer.style.display = 'none';
                console.log('Móvil: Mostrando registro, ocultando login');
            }
            
            // Cambiar el título de la página
            document.title = 'Registrarse - Mercado Huasteco';
            
            // Scroll al top en móvil
            if (isMobile()) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            
            // Enfocar el primer input del formulario de registro
            setTimeout(() => {
                const firstInput = document.querySelector('.sign-up-container input[type="text"]');
                if (firstInput && !isMobile()) {
                    firstInput.focus();
                }
            }, isMobile() ? 300 : 600);
            
            // Anunciar cambio para accesibilidad
            if (typeof announceToScreenReader === 'function') {
                announceToScreenReader('Formulario de registro activado');
            }
            
            console.log('Cambiado a modo registro - isSignUpMode:', isSignUpMode);
        }

        // Función para cambiar a modo inicio de sesión
        function switchToSignIn() {
            container.classList.remove("right-panel-active");
            isSignUpMode = false;
            
            // ===== SOLUCIÓN MÓVIL: Control directo del display =====
            const signUpContainer = document.querySelector('.sign-up-container');
            const signInContainer = document.querySelector('.sign-in-container');
            
            if (isMobile()) {
                // En móvil: controlar display directamente
                if (signUpContainer) signUpContainer.style.display = 'none';
                if (signInContainer) signInContainer.style.display = 'block';
                console.log('Móvil: Mostrando login, ocultando registro');
            }
            
            // Cambiar el título de la página
            document.title = 'Iniciar Sesión - Mercado Huasteco';
            
            // Scroll al top en móvil
            if (isMobile()) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            
            // Enfocar el primer input del formulario de inicio de sesión
            setTimeout(() => {
                const firstInput = document.querySelector('.sign-in-container input[type="email"]');
                if (firstInput && !isMobile()) {
                    firstInput.focus();
                }
            }, isMobile() ? 300 : 600);
            
            // Anunciar cambio para accesibilidad
            if (typeof announceToScreenReader === 'function') {
                announceToScreenReader('Formulario de inicio de sesión activado');
            }
            
            console.log('Cambiado a modo login - isSignUpMode:', isSignUpMode);
        }

        // Event listeners para los botones principales
        if (signUpButton) signUpButton.addEventListener('click', switchToSignUp);
        if (signInButton) signInButton.addEventListener('click', switchToSignIn);

        // Función para manejar efectos de input
        function setupInputEffects() {
            const inputs = document.querySelectorAll('input');
            
            inputs.forEach(input => {
                // Mejorar UX en móvil
                if (isMobile()) {
                    input.addEventListener('focus', function() {
                        // Scroll al input en móvil con delay
                        setTimeout(() => {
                            this.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }, 300);
                    });
                }
                
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
                
                input.addEventListener('input', function() {
                    if (this.type === 'email') {
                        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (emailRegex.test(this.value)) {
                            this.style.borderColor = '#28a745';
                        } else if (this.value.length > 0) {
                            this.style.borderColor = '#dc3545';
                        } else {
                            this.style.borderColor = '';
                        }
                    }
                    
                    if (this.type === 'password') {
                        if (this.value.length >= 6) {
                            this.style.borderColor = '#28a745';
                        } else if (this.value.length > 0) {
                            this.style.borderColor = '#ffc107';
                        } else {
                            this.style.borderColor = '';
                        }
                    }
                });
                
                // Mejorar experiencia táctil
                if (isMobile()) {
                    input.addEventListener('touchstart', function() {
                        this.classList.add('touch-active');
                    });
                    
                    input.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.classList.remove('touch-active');
                        }, 150);
                    });
                }
            });
        }

        // Mejorar botones para móvil
        function setupButtonEffects() {
            const buttons = document.querySelectorAll('.btn, button[type="submit"]');
            
            buttons.forEach(button => {
                if (isMobile()) {
                    // Efecto táctil
                    button.addEventListener('touchstart', function(e) {
                        this.classList.add('touch-active');
                        
                        // Crear efecto de ondas
                        const ripple = document.createElement('span');
                        const rect = this.getBoundingClientRect();
                        const size = Math.max(rect.width, rect.height);
                        const x = e.touches[0].clientX - rect.left - size / 2;
                        const y = e.touches[0].clientY - rect.top - size / 2;
                        
                        ripple.style.cssText = `
                            position: absolute;
                            width: ${size}px;
                            height: ${size}px;
                            left: ${x}px;
                            top: ${y}px;
                            background: rgba(255, 255, 255, 0.4);
                            border-radius: 50%;
                            transform: scale(0);
                            animation: ripple 0.6s linear;
                            pointer-events: none;
                        `;
                        
                        this.appendChild(ripple);
                        
                        setTimeout(() => {
                            ripple.remove();
                        }, 600);
                    });
                    
                    button.addEventListener('touchend', function() {
                        setTimeout(() => {
                            this.classList.remove('touch-active');
                        }, 150);
                    });
                    
                    button.addEventListener('touchcancel', function() {
                        this.classList.remove('touch-active');
                    });
                }
            });
        }

        // Función para anunciar cambios a lectores de pantalla
        function announceToScreenReader(message) {
            const announcement = document.createElement('div');
            announcement.setAttribute('aria-live', 'polite');
            announcement.setAttribute('aria-atomic', 'true');
            announcement.style.cssText = `
                position: absolute;
                left: -10000px;
                width: 1px;
                height: 1px;
                overflow: hidden;
            `;
            announcement.textContent = message;
            document.body.appendChild(announcement);
            
            setTimeout(() => {
                document.body.removeChild(announcement);
            }, 1000);
        }

        // Función de inicialización
        function init() {
            setupInputEffects();
            setupButtonEffects();
            
            // ===== INICIALIZACIÓN MÓVIL =====
            if (isMobile()) {
                const signUpContainer = document.querySelector('.sign-up-container');
                const signInContainer = document.querySelector('.sign-in-container');
                
                // Verificar si estamos en modo registro
                const isRegisterMode = container.classList.contains('right-panel-active');
                
                if (isRegisterMode) {
                    // Mostrar registro, ocultar login
                    if (signUpContainer) signUpContainer.style.display = 'block';
                    if (signInContainer) signInContainer.style.display = 'none';
                    console.log('Inicialización móvil: Modo registro');
                } else {
                    // Mostrar login, ocultar registro
                    if (signUpContainer) signUpContainer.style.display = 'none';
                    if (signInContainer) signInContainer.style.display = 'block';
                    console.log('Inicialización móvil: Modo login');
                }
            }
            
            // Si hay un error de registro, mostrar el formulario de registro
            <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                switchToSignUp();
            <?php endif; ?>
            
            // Auto-ocultar mensajes después de 5 segundos
            const mensajes = document.querySelectorAll('.mensaje');
            mensajes.forEach(mensaje => {
                // Hacer mensajes clickeables para cerrar
                mensaje.style.cursor = 'pointer';
                mensaje.addEventListener('click', function() {
                    this.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        this.remove();
                    }, 300);
                });
                
                setTimeout(() => {
                    if (mensaje.parentNode) {
                        mensaje.style.animation = 'slideOut 0.3s ease';
                        setTimeout(() => {
                            if (mensaje.parentNode) {
                                mensaje.remove();
                            }
                        }, 300);
                    }
                }, 5000);
            });
            
            // Validación adicional del formulario de registro
            const registerForm = document.querySelector('.sign-up-container form');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    const rolInputs = this.querySelectorAll('input[name="rol"]');
                    let rolSelected = false;
                    
                    rolInputs.forEach(input => {
                        if (input.checked) {
                            rolSelected = true;
                            console.log('Rol seleccionado:', input.value);
                        }
                    });
                    
                    if (!rolSelected) {
                        e.preventDefault();
                        
                        // Mostrar alerta más amigable en móvil
                        if (isMobile()) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'mensaje error';
                            alertDiv.innerHTML = `
                                <div class="mensaje-content">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span>Por favor selecciona un rol (Cliente o Vendedor)</span>
                                </div>
                            `;
                            document.body.appendChild(alertDiv);
                            
                            setTimeout(() => {
                                alertDiv.remove();
                            }, 3000);
                        } else {
                            alert('Por favor selecciona un rol (Cliente o Vendedor)');
                        }
                        
                        // Scroll a la selección de rol
                        const roleSelection = document.querySelector('.role-selection');
                        if (roleSelection) {
                            roleSelection.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                        
                        return false;
                    }
                });
            }
            
            // Mejorar selección de rol en móvil
            const roleCards = document.querySelectorAll('.role-card');
            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Feedback háptico en dispositivos compatibles
                    if (navigator.vibrate) {
                        navigator.vibrate(50);
                    }
                    
                    // Anunciar selección
                    const roleText = this.querySelector('.role-info h4').textContent;
                    announceToScreenReader(`Rol seleccionado: ${roleText}`);
                });
            });
        }

        // Manejar cambios de orientación en móvil
        function handleOrientationChange() {
            if (isMobile()) {
                // Ajustar viewport height
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
                
                // Scroll al top después de cambio de orientación
                setTimeout(() => {
                    window.scrollTo(0, 0);
                }, 100);
            }
        }

        // Event listeners
        window.addEventListener('orientationchange', handleOrientationChange);
        window.addEventListener('resize', handleOrientationChange);

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function() {
            init();
            
            // Verificar estado inicial
            checkCurrentMode();
            
            // Manejar hash de registro desde header
            const hash = window.location.hash;
            if (hash === '#registro' || hash === '#register') {
                console.log('Hash detectado:', hash, '- Cambiando a formulario de registro');
                setTimeout(() => {
                    switchToSignUp();
                    // Limpiar el hash de la URL
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState(null, null, window.location.pathname);
                    }
                }, 200);
            }
            
            // ===== ASEGURAR QUE LOS ENLACES MÓVILES FUNCIONEN =====
            const mobileLinks = document.querySelectorAll('.mobile-switch a');
            mobileLinks.forEach((link, index) => {
                // Remover cualquier listener previo y agregar uno nuevo
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Click en enlace móvil', index, 'texto:', this.textContent.trim());
                    
                    // Determinar qué función llamar basado en el texto
                    const linkText = this.textContent.trim().toLowerCase();
                    
                    if (linkText.includes('inicia sesión') || linkText.includes('iniciar sesión')) {
                        console.log('Llamando a switchToSignIn()');
                        switchToSignIn();
                    } else if (linkText.includes('regístrate') || linkText.includes('registrarse')) {
                        console.log('Llamando a switchToSignUp()');
                        switchToSignUp();
                    }
                    
                    return false;
                });
            });
        });

        // Agregar estilos CSS adicionales
        const additionalStyles = document.createElement('style');
        additionalStyles.textContent = `
            .input-group.focused {
                transform: translateY(-2px);
            }
            
            .touch-active {
                transform: scale(0.98);
                opacity: 0.9;
                transition: all 0.1s ease;
            }
            
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
            
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            /* Mejorar área táctil en móvil */
            @media (max-width: 768px) {
                .btn, button, input[type="submit"] {
                    -webkit-tap-highlight-color: rgba(0, 102, 102, 0.2);
                    -webkit-touch-callout: none;
                    -webkit-user-select: none;
                    user-select: none;
                }
                
                .role-card {
                    -webkit-tap-highlight-color: rgba(0, 102, 102, 0.1);
                }
                
                .mensaje {
                    -webkit-tap-highlight-color: rgba(255, 255, 255, 0.2);
                }
            }
            
            /* Usar viewport height real en móvil */
            @media (max-width: 768px) {
                .container {
                    min-height: calc(var(--vh, 1vh) * 100);
                }
            }
        `;
        document.head.appendChild(additionalStyles);
    </script>
</body>
</html>