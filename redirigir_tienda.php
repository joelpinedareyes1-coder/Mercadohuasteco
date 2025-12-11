<?php
require_once 'config.php';

// Verificar que se recibió el tienda_id
if (!isset($_GET['tienda_id']) || empty($_GET['tienda_id'])) {
    // Si no hay tienda_id, redirigir al directorio
    header("Location: directorio.php");
    exit();
}

$tienda_id = (int)$_GET['tienda_id'];

// Validar que el tienda_id sea un número válido
if ($tienda_id <= 0) {
    header("Location: directorio.php");
    exit();
}

try {
    // Buscar la tienda en la base de datos
    $stmt = $pdo->prepare("SELECT url_tienda, nombre_tienda FROM tiendas WHERE id = ? AND activo = 1");
    $stmt->execute([$tienda_id]);
    $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tienda) {
        // Si la tienda no existe o no está activa, redirigir al directorio
        header("Location: directorio.php");
        exit();
    }
    
    // Actualizar el contador de clics
    $stmt_update = $pdo->prepare("UPDATE tiendas SET clics = clics + 1 WHERE id = ?");
    $stmt_update->execute([$tienda_id]);
    
    // Validar que la URL sea válida antes de redirigir
    $url_destino = $tienda['url_tienda'];
    
    if (!filter_var($url_destino, FILTER_VALIDATE_URL)) {
        // Si la URL no es válida, mostrar error
        $error = "La URL de la tienda no es válida.";
    } else {
        // Redirigir al usuario a la URL real de la tienda
        header("Location: " . $url_destino);
        exit();
    }
    
} catch(PDOException $e) {
    // En caso de error de base de datos, redirigir al directorio
    error_log("Error en redirigir_tienda.php: " . $e->getMessage());
    header("Location: directorio.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo... - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        
        .spinner-border {
            width: 4rem;
            height: 4rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if (isset($error)): ?>
            <div class="error-icon mb-4">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h3 class="text-danger mb-3">Error de Redirección</h3>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
            <a href="directorio.php" class="btn btn-success btn-lg">
                <i class="bi bi-arrow-left"></i> Volver al Directorio
            </a>
        <?php else: ?>
            <div class="spinner-border text-success mb-4" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h3 class="mb-3">Redirigiendo a la tienda...</h3>
            <p class="text-muted mb-4">Te estamos llevando al sitio web de la tienda.</p>
            <small class="text-muted">
                Si no eres redirigido automáticamente, 
                <a href="directorio.php" class="text-decoration-none">haz clic aquí</a>
            </small>
        <?php endif; ?>
    </div>
</body>
</html>