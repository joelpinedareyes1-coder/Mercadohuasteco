<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Eliminada - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .elimination-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            max-width: 600px;
            margin: 2rem;
        }
        
        .elimination-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        
        .btn-home {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            color: white;
            font-weight: bold;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            color: white;
        }
        
        .stats-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="elimination-card">
        <div class="elimination-icon">
            <i class="bi bi-trash3-fill"></i>
        </div>
        
        <h1 class="mb-4">Tienda Eliminada Exitosamente</h1>
        
        <p class="lead mb-4">
            Tu tienda "<strong><?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : 'Sin nombre'; ?></strong>" 
            ha sido eliminada permanentemente de Mercado Huasteco.
        </p>
        
        <?php 
        $reseñas = isset($_GET['reseñas']) ? (int)$_GET['reseñas'] : 0;
        $fotos = isset($_GET['fotos']) ? (int)$_GET['fotos'] : 0;
        ?>
        
        <div class="stats-box">
            <h6><i class="bi bi-info-circle text-info"></i> Información de Eliminación</h6>
            <p class="mb-1">✓ Tu tienda fue eliminada del directorio</p>
            <?php if ($reseñas > 0): ?>
                <p class="mb-1">✓ Se eliminaron <strong><?php echo $reseñas; ?> reseñas</strong></p>
            <?php endif; ?>
            <?php if ($fotos > 0): ?>
                <p class="mb-1">✓ Se eliminaron <strong><?php echo $fotos; ?> fotos</strong> de la galería</p>
            <?php endif; ?>
            <p class="mb-0">✓ Todos los datos fueron eliminados permanentemente</p>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-lightbulb"></i> ¿Quieres volver a empezar?</h6>
            <p class="mb-0">
                Si cambias de opinión en el futuro, puedes registrar una nueva tienda 
                en cualquier momento desde tu panel de vendedor.
            </p>
        </div>
        
        <div class="mt-4">
            <a href="panel_vendedor.php" class="btn btn-home me-3">
                <i class="bi bi-shop"></i> Ir a Mi Panel
            </a>
            <a href="directorio.php" class="btn btn-outline-primary">
                <i class="bi bi-grid"></i> Ver Directorio
            </a>
        </div>
        
        <hr class="my-4">
        
        <div class="text-muted">
            <small>
                <i class="bi bi-shield-check"></i> 
                Tu tienda y toda su información han sido eliminadas de forma segura y permanente.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>