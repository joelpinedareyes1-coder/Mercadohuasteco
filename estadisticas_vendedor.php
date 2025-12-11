<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Obtener estadísticas del vendedor
try {
    // Total de productos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_productos FROM productos WHERE vendedor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_productos = $stmt->fetch(PDO::FETCH_ASSOC)['total_productos'];
    
    // Total de clics
    $stmt = $pdo->prepare("SELECT SUM(clics) as total_clics FROM productos WHERE vendedor_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total_clics = $stmt->fetch(PDO::FETCH_ASSOC)['total_clics'] ?: 0;
    
    // Producto más popular
    $stmt = $pdo->prepare("SELECT nombre, clics FROM productos WHERE vendedor_id = ? ORDER BY clics DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $producto_popular = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Productos con estadísticas detalladas
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE vendedor_id = ? ORDER BY clics DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error = "Error al cargar estadísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stats-card { 
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
        }
        .stats-card-secondary {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
            border: none;
        }
        .stats-card-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-graph-up"></i> Estadísticas de Vendedor
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="panel_vendedor.php">
                    <i class="bi bi-arrow-left"></i> Volver al Panel
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?php echo $total_productos; ?></h3>
                        <p class="mb-0">Productos Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card-secondary">
                    <div class="card-body text-center">
                        <i class="bi bi-mouse" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?php echo $total_clics; ?></h3>
                        <p class="mb-0">Clics Totales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card-warning">
                    <div class="card-body text-center">
                        <i class="bi bi-trophy" style="font-size: 3rem;"></i>
                        <h3 class="mt-2"><?php echo $producto_popular ? $producto_popular['clics'] : 0; ?></h3>
                        <p class="mb-0">Producto Más Popular</p>
                        <?php if ($producto_popular): ?>
                            <small><?php echo htmlspecialchars($producto_popular['nombre']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-bar-chart"></i> Rendimiento por Producto
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($productos)): ?>
                    <p class="text-muted text-center">No tienes productos para mostrar estadísticas.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Precio</th>
                                    <th>Clics</th>
                                    <th>Fecha Creación</th>
                                    <th>Rendimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)) . '...'; ?>
                                            </small>
                                        </td>
                                        <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-primary fs-6">
                                                <?php echo $producto['clics']; ?> clics
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($producto['fecha_creacion'])); ?></td>
                                        <td>
                                            <?php 
                                            $dias = max(1, (time() - strtotime($producto['fecha_creacion'])) / (60*60*24));
                                            $clics_por_dia = round($producto['clics'] / $dias, 2);
                                            ?>
                                            <small class="text-muted">
                                                <?php echo $clics_por_dia; ?> clics/día
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>