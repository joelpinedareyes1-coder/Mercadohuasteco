<?php
require_once 'config.php';

// Verificar que se recibió el producto_id
if (!isset($_GET['producto_id']) || empty($_GET['producto_id'])) {
    // Si no hay producto_id, redirigir al catálogo
    header("Location: catalogo.php");
    exit();
}

$producto_id = (int)$_GET['producto_id'];

// Validar que el producto_id sea un número válido
if ($producto_id <= 0) {
    header("Location: catalogo.php");
    exit();
}

try {
    // Buscar el producto en la base de datos
    $stmt = $pdo->prepare("SELECT url_compra, nombre FROM productos WHERE id = ? AND activo = 1");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        // Si el producto no existe o no está activo, redirigir al catálogo
        header("Location: catalogo.php");
        exit();
    }
    
    // Actualizar el contador de clics
    $stmt_update = $pdo->prepare("UPDATE productos SET clics = clics + 1 WHERE id = ?");
    $stmt_update->execute([$producto_id]);
    
    // Validar que la URL sea válida antes de redirigir
    $url_destino = $producto['url_compra'];
    
    if (!filter_var($url_destino, FILTER_VALIDATE_URL)) {
        // Si la URL no es válida, mostrar error
        $error = "La URL del producto no es válida.";
    } else {
        // Redirigir al usuario a la URL real del producto
        header("Location: " . $url_destino);
        exit();
    }
    
} catch(PDOException $e) {
    // En caso de error de base de datos, redirigir al catálogo
    error_log("Error en redirigir.php: " . $e->getMessage());
    header("Location: catalogo.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo... - Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .error-container {
            background: white;
            padding: 3rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if (isset($error)): ?>
            <div class="text-danger mb-4">
                <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
            </div>
            <h3 class="text-danger mb-3">Error de Redirección</h3>
            <p class="text-muted mb-4"><?php echo htmlspecialchars($error); ?></p>
            <a href="catalogo.php" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Volver al Catálogo
            </a>
        <?php else: ?>
            <div class="spinner-border text-primary mb-4" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <h3 class="mb-3">Redirigiendo...</h3>
            <p class="text-muted">Te estamos llevando a la tienda del producto.</p>
            <small class="text-muted">Si no eres redirigido automáticamente, 
                <a href="catalogo.php">haz clic aquí</a>
            </small>
        <?php endif; ?>
    </div>
</body>
</html>