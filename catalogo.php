<?php
require_once 'config.php';

// Obtener todos los productos activos con información del vendedor
try {
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre as vendedor_nombre 
        FROM productos p 
        INNER JOIN usuarios u ON p.vendedor_id = u.id 
        WHERE p.activo = 1 AND u.activo = 1 
        ORDER BY p.fecha_creacion DESC
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $productos = [];
    $error = "Error al cargar los productos: " . $e->getMessage();
}

// Función para truncar texto
function truncar_texto($texto, $limite = 100) {
    if (strlen($texto) > $limite) {
        return substr($texto, 0, $limite) . '...';
    }
    return $texto;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo - Marketplace Directorio</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        
        .product-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .product-image {
            height: 200px;
            object-fit: cover;
            background: #f8f9fa;
        }
        
        .product-image-placeholder {
            height: 200px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #6c757d;
        }
        
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        
        .vendor-badge {
            background: #6c757d;
            color: white;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 15px;
        }
        
        .btn-comprar {
            background: linear-gradient(45deg, #28a745, #20c997);
            border: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-comprar:hover {
            background: linear-gradient(45deg, #218838, #1ea080);
            transform: scale(1.05);
        }
        
        .stats-section {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-shop"></i> Marketplace Directorio
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="catalogo.php">
                            <i class="bi bi-grid"></i> Catálogo
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (esta_logueado()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if ($_SESSION['rol'] === 'vendedor'): ?>
                                    <li><a class="dropdown-item" href="panel_vendedor.php">
                                        <i class="bi bi-shop"></i> Mi Panel
                                    </a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="dashboard_cliente.php">
                                        <i class="bi bi-person"></i> Mi Dashboard
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth.php">
                                <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="auth.php">
                                <i class="bi bi-person-plus"></i> Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">
                <i class="bi bi-shop"></i> Descubre Productos Increíbles
            </h1>
            <p class="lead">Explora nuestra selección de productos de vendedores verificados</p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <h3 class="text-primary"><?php echo count($productos); ?></h3>
                    <p class="text-muted">Productos Disponibles</p>
                </div>
                <div class="col-md-4">
                    <h3 class="text-success">
                        <?php 
                        $vendedores_unicos = array_unique(array_column($productos, 'vendedor_id'));
                        echo count($vendedores_unicos); 
                        ?>
                    </h3>
                    <p class="text-muted">Vendedores Activos</p>
                </div>
                <div class="col-md-4">
                    <h3 class="text-info">100%</h3>
                    <p class="text-muted">Productos Verificados</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($productos)): ?>
            <div class="no-products">
                <i class="bi bi-inbox" style="font-size: 4rem; color: #dee2e6;"></i>
                <h3 class="mt-3">No hay productos disponibles</h3>
                <p>Sé el primero en agregar productos a nuestro marketplace.</p>
                <a href="auth.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-person-plus"></i> Únete como Vendedor
                </a>
            </div>
        <?php else: ?>
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-grid-3x3-gap"></i> 
                            Mostrando <?php echo count($productos); ?> productos
                        </h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <small class="text-muted">
                            <i class="bi bi-arrow-clockwise"></i> 
                            Actualizado recientemente
                        </small>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="row">
                <?php 
                // LOOP PRINCIPAL - Aquí recorremos todos los productos
                while ($producto = array_shift($productos)): 
                ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card">
                            <!-- Imagen del producto -->
                            <?php if (!empty($producto['foto']) && file_exists($producto['foto'])): ?>
                                <img src="<?php echo htmlspecialchars($producto['foto']); ?>" 
                                     class="card-img-top product-image" 
                                     alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <?php else: ?>
                                <div class="product-image-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-body d-flex flex-column">
                                <!-- Vendedor Badge -->
                                <div class="mb-2">
                                    <span class="vendor-badge">
                                        <i class="bi bi-person"></i> 
                                        <?php echo htmlspecialchars($producto['vendedor_nombre']); ?>
                                    </span>
                                </div>
                                
                                <!-- Nombre del producto -->
                                <h5 class="card-title">
                                    <?php echo htmlspecialchars($producto['nombre']); ?>
                                </h5>
                                
                                <!-- Descripción -->
                                <p class="card-text text-muted flex-grow-1">
                                    <?php echo htmlspecialchars(truncar_texto($producto['descripcion'], 120)); ?>
                                </p>
                                
                                <!-- Precio -->
                                <div class="price-tag mb-3">
                                    $<?php echo number_format($producto['precio'], 2); ?>
                                </div>
                                
                                <!-- Botón de compra - REDIRIGE A TRAVÉS DEL SCRIPT DE RASTREO -->
                                <a href="redirigir.php?producto_id=<?php echo $producto['id']; ?>" 
                                   target="_blank" 
                                   class="btn btn-success btn-comprar w-100">
                                    <i class="bi bi-cart-plus"></i> Ir a la Tienda
                                </a>
                                
                                <!-- Información adicional -->
                                <small class="text-muted mt-2 text-center">
                                    <i class="bi bi-calendar"></i> 
                                    Agregado: <?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">
                <i class="bi bi-shop"></i> 
                Marketplace Directorio &copy; <?php echo date('Y'); ?> - 
                Conectando compradores y vendedores
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>