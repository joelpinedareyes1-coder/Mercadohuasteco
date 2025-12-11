<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

$page_title = "Pago Fallido";
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            animation: shake 0.5s ease-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="fas fa-times-circle error-icon"></i>
        <h1 class="mt-4 mb-3">Pago No Completado</h1>
        <p class="text-muted mb-4">
            Hubo un problema al procesar tu pago.<br>
            No te preocupes, no se realizó ningún cargo.
        </p>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Puedes intentar nuevamente cuando quieras
        </div>
        <div class="d-grid gap-2">
            <a href="crear_pago_mp.php" class="btn btn-primary btn-lg">
                <i class="fas fa-redo me-2"></i>Intentar Nuevamente
            </a>
            <a href="panel_vendedor.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-2"></i>Volver al Panel
            </a>
        </div>
    </div>
</body>
</html>
