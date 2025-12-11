<?php
session_start();
require_once 'config.php';

// Verificar que el usuario esté logueado y sea cliente
if (!esta_logueado() || $_SESSION['rol'] !== 'cliente') {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Manejar mensajes de la URL
$mensaje = $_GET['mensaje'] ?? '';
$error = $_GET['error'] ?? '';

// Obtener tiendas favoritas del usuario
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre, f.fecha_agregado,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM favoritos f
        INNER JOIN tiendas t ON f.tienda_id = t.id
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE f.usuario_id = ? AND t.activo = 1
        GROUP BY t.id, u.nombre, f.fecha_agregado
        ORDER BY f.fecha_agregado DESC
    ");
    $stmt->execute([$user_id]);
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $favoritos = [];
    $error = "Error al cargar favoritos: " . $e->getMessage();
}

// Función para mostrar estrellas
function mostrar_estrellas($promedio, $total_calificaciones = 0) {
    $estrellas_html = '';
    $promedio_redondeado = round($promedio, 1);
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $promedio_redondeado) {
            $estrellas_html .= '<i class="bi bi-star-fill text-warning"></i>';
        } elseif ($i - 0.5 <= $promedio_redondeado) {
            $estrellas_html .= '<i class="bi bi-star-half text-warning"></i>';
        } else {
            $estrellas_html .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    
    if ($total_calificaciones > 0) {
        $estrellas_html .= ' <small class="text-muted">(' . $promedio_redondeado . ' - ' . $total_calificaciones . ' reseñas)</small>';
    } else {
        $estrellas_html .= ' <small class="text-muted">Sin reseñas</small>';
    }
    
    return $estrellas_html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Favoritos - Mercado Huasteco</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --color-principal: #006666;
            --color-secundario: #CC5500;
            --color-fondo: #F8F9FA;
            --color-tarjetas: #FFFFFF;
        }
        
        body {
            background-color: var(--color-fondo);
        }
        
        .header {
            background: linear-gradient(135deg, var(--color-principal) 0%, #004d4d 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .favorito-card {
            background: var(--color-tarjetas);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .favorito-card:hover {
            transform: translateY(-5px);
        }
        
        .logo-favorito {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .btn-quitar-favorito {
            background: #dc3545;
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-quitar-favorito:hover {
            background: #c82333;
            color: white;
        }
        
        .btn-visitar {
            background: linear-gradient(45deg, var(--color-principal), #004d4d);
            border: none;
            color: white;
        }
        
        .btn-visitar:hover {
            background: linear-gradient(45deg, #004d4d, #003333);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-heart-fill"></i> Mis Favoritos
                    </h1>
                    <p class="mb-0 opacity-75">Tiendas que has marcado como favoritas</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard_cliente.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($favoritos)): ?>
            <div class="text-center py-5">
                <i class="bi bi-heart" style="font-size: 5rem; color: #dee2e6;"></i>
                <h3 class="mt-4 text-muted">No tienes favoritos aún</h3>
                <p class="text-muted mb-4">Explora el directorio y marca las tiendas que más te gusten</p>
                <a href="directorio.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-grid-3x3-gap"></i> Explorar Directorio
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <h4 class="mb-4">
                        <i class="bi bi-heart-fill text-danger"></i> 
                        Tienes <?php echo count($favoritos); ?> tienda<?php echo count($favoritos) != 1 ? 's' : ''; ?> favorita<?php echo count($favoritos) != 1 ? 's' : ''; ?>
                    </h4>
                </div>
            </div>

            <?php foreach ($favoritos as $tienda): ?>
                <div class="favorito-card">
                    <div class="row align-items-center">
                        <!-- Logo -->
                        <div class="col-md-2">
                            <?php if (!empty($tienda['logo']) && file_exists($tienda['logo'])): ?>
                                <img src="<?php echo htmlspecialchars($tienda['logo']); ?>" 
                                     class="logo-favorito" 
                                     alt="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                            <?php else: ?>
                                <div class="logo-favorito bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-shop text-muted" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Información -->
                        <div class="col-md-6">
                            <h5 class="mb-2"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($tienda['descripcion']); ?></p>
                            <div class="mb-2">
                                <?php echo mostrar_estrellas($tienda['promedio_estrellas'], $tienda['total_calificaciones']); ?>
                            </div>
                            <small class="text-muted">
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?> • 
                                <i class="bi bi-heart-fill text-danger"></i> Agregado el <?php echo date('d/m/Y', strtotime($tienda['fecha_agregado'])); ?>
                            </small>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="col-md-4 text-end">
                            <div class="d-flex gap-2 justify-content-end">
                                <a href="redirigir_tienda.php?tienda_id=<?php echo $tienda['id']; ?>" 
                                   target="_blank" 
                                   class="btn btn-visitar">
                                    <i class="bi bi-box-arrow-up-right"></i> Visitar
                                </a>
                                
                                <a href="quitar_favorito.php?id=<?php echo $tienda['id']; ?>" class="btn btn-danger">Quitar</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function quitarFavorito(tiendaId, nombreTienda) {
            if (confirm(`¿Estás seguro de que quieres quitar "${nombreTienda}" de tus favoritos?`)) {
                fetch('gestionar_favoritos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `accion=quitar&tienda_id=${tiendaId}`
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        console.log('Favorito eliminado exitosamente');
                        // Recargar la página para actualizar la lista
                        location.reload();
                    } else {
                        console.error('Error del servidor:', data.message);
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al quitar de favoritos. Intentando método alternativo...');
                    
                    // Mostrar formulario de respaldo
                    const form = document.getElementById(`form-${tiendaId}`);
                    if (form) {
                        form.style.display = 'block';
                        form.querySelector('button').click();
                    }
                });
            }
        }
    </script>
</body>
</html>