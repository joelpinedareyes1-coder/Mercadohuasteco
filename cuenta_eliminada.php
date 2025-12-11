<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Eliminada - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #28a745;
            margin-bottom: 1rem;
        }
        
        .btn-home {
            background: linear-gradient(45deg, #667eea, #764ba2);
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
            <i class="bi bi-check-circle-fill"></i>
        </div>
        
        <h1 class="mb-4">Cuenta Eliminada Exitosamente</h1>
        
        <p class="lead mb-4">
            Tu cuenta ha sido eliminada permanentemente de Mercado Huasteco. 
            Lamentamos verte partir y esperamos que hayas tenido una buena experiencia con nosotros.
        </p>
        
        <?php 
        $rol = isset($_GET['rol']) ? $_GET['rol'] : 'cliente';
        $reseñas_usuario = isset($_GET['reseñas_usuario']) ? (int)$_GET['reseñas_usuario'] : 0;
        $tienda_eliminada = isset($_GET['tienda']) && $_GET['tienda'] == 1;
        $reseñas_tienda = isset($_GET['reseñas_tienda']) ? (int)$_GET['reseñas_tienda'] : 0;
        ?>
        
        <div class="stats-box">
            <h6><i class="bi bi-info-circle text-info"></i> Información de Eliminación</h6>
            
            <?php if ($rol === 'vendedor'): ?>
                <p class="mb-1">
                    <strong>Cuenta de Vendedor eliminada</strong>
                </p>
                <?php if ($tienda_eliminada): ?>
                    <p class="mb-1">✓ Tu tienda fue eliminada del directorio</p>
                    <?php if ($reseñas_tienda > 0): ?>
                        <p class="mb-1">✓ Se eliminaron <strong><?php echo $reseñas_tienda; ?> reseñas</strong> de tu tienda</p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($reseñas_usuario > 0): ?>
                    <p class="mb-0">✓ Se eliminaron <strong><?php echo $reseñas_usuario; ?> reseñas</strong> que habías escrito</p>
                <?php endif; ?>
            <?php else: ?>
                <p class="mb-1">
                    <strong>Cuenta de Cliente eliminada</strong>
                </p>
                <?php if ($reseñas_usuario > 0): ?>
                    <p class="mb-0">✓ Se eliminaron <strong><?php echo $reseñas_usuario; ?> reseñas</strong> que habías escrito</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="alert alert-info">
            <h6><i class="bi bi-lightbulb"></i> ¿Cambio de opinión?</h6>
            <p class="mb-0">
                Si deseas volver a usar Mercado Huasteco en el futuro, puedes crear una nueva cuenta 
                en cualquier momento. Sin embargo, no podremos recuperar la información de esta cuenta eliminada.
            </p>
        </div>
        
        <div class="mt-4">
            <a href="directorio.php" class="btn btn-home me-3">
                <i class="bi bi-house-fill"></i> Ir al Directorio
            </a>
            <a href="auth.php" class="btn btn-outline-primary">
                <i class="bi bi-person-plus"></i> Crear Nueva Cuenta
            </a>
        </div>
        
        <hr class="my-4">
        
        <div class="text-muted">
            <small>
                <i class="bi bi-shield-check"></i> 
                Tu información ha sido eliminada de forma segura y permanente de nuestros servidores.
            </small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>