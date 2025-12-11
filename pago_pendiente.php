<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

$page_title = "Pago Pendiente";
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
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .pending-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .pending-icon {
            font-size: 5rem;
            color: #ffc107;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="pending-card">
        <i class="fas fa-clock pending-icon"></i>
        <h1 class="mt-4 mb-3">Pago Pendiente</h1>
        <p class="text-muted mb-4">
            Tu pago está siendo procesado.<br>
            Te notificaremos cuando se complete.
        </p>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Esto puede tomar unos minutos
        </div>
        <a href="panel_vendedor.php" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-home me-2"></i>Volver al Panel
        </a>
    </div>
</body>
</html>
