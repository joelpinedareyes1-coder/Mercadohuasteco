<?php
require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

$page_title = "¡Pago Exitoso!";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        .crown-icon {
            color: #ffc107;
            font-size: 3rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="success-card">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="mt-4 mb-3">¡Pago Exitoso!</h1>
        <i class="fas fa-crown crown-icon"></i>
        <h3 class="text-warning mb-3">¡Ahora eres Premium!</h3>
        <p class="text-muted mb-4">
            Tu membresía Premium ha sido activada por 30 días.<br>
            Disfruta de todas las funciones exclusivas.
        </p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Tu cuenta se actualizará en unos segundos
        </div>
        <a href="panel_vendedor.php" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-home me-2"></i>Ir a Mi Panel
        </a>
    </div>
    
    <script>
        // Redirigir automáticamente después de 5 segundos
        setTimeout(() => {
            window.location.href = 'panel_vendedor.php';
        }, 5000);
    </script>
</body>
</html>
