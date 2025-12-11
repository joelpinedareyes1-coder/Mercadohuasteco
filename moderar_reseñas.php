<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$mensaje = '';
$error = '';

// Procesar acciones de moderación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && isset($_POST['reseña_id'])) {
        $reseña_id = (int)$_POST['reseña_id'];
        $accion = $_POST['accion'];
        
        try {
            if ($accion === 'aprobar') {
                $stmt = $pdo->prepare("UPDATE calificaciones SET esta_aprobada = 1 WHERE id = ?");
                $stmt->execute([$reseña_id]);
                $mensaje = "Reseña aprobada exitosamente.";
            } elseif ($accion === 'ocultar') {
                $stmt = $pdo->prepare("UPDATE calificaciones SET esta_aprobada = 0 WHERE id = ?");
                $stmt->execute([$reseña_id]);
                $mensaje = "Reseña ocultada exitosamente.";
            } elseif ($accion === 'eliminar') {
                $stmt = $pdo->prepare("UPDATE calificaciones SET activo = 0 WHERE id = ?");
                $stmt->execute([$reseña_id]);
                $mensaje = "Reseña eliminada exitosamente.";
            }
        } catch(PDOException $e) {
            $error = "Error al moderar la reseña: " . $e->getMessage();
        }
    }
}

// Obtener filtro de estado
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'todas';

// Obtener todas las reseñas con información completa
try {
    $sql = "
        SELECT c.*, u.nombre as usuario_nombre, u.email as usuario_email,
               t.nombre_tienda, t.categoria, v.nombre as vendedor_nombre
        FROM calificaciones c
        INNER JOIN usuarios u ON c.user_id = u.id
        INNER JOIN tiendas t ON c.tienda_id = t.id
        INNER JOIN usuarios v ON t.vendedor_id = v.id
        WHERE c.activo = 1
    ";
    
    // Agregar filtro según estado seleccionado
    if ($filtro_estado === 'aprobadas') {
        $sql .= " AND c.esta_aprobada = 1";
    } elseif ($filtro_estado === 'ocultas') {
        $sql .= " AND c.esta_aprobada = 0";
    }
    
    $sql .= " ORDER BY c.fecha_calificacion DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $reseñas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN esta_aprobada = 1 THEN 1 ELSE 0 END) as aprobadas,
            SUM(CASE WHEN esta_aprobada = 0 THEN 1 ELSE 0 END) as ocultas
        FROM calificaciones 
        WHERE activo = 1
    ");
    $stmt_stats->execute();
    $estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $reseñas = [];
    $estadisticas = ['total' => 0, 'aprobadas' => 0, 'ocultas' => 0];
    $error = "Error al cargar las reseñas: " . $e->getMessage();
}

// Función para mostrar estrellas
function mostrar_estrellas_moderacion($estrellas) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $estrellas) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderar Reseñas - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .reseña-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .reseña-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .reseña-aprobada {
            border-left: 5px solid #28a745;
            background: linear-gradient(90deg, #d4edda, #ffffff);
        }
        
        .reseña-oculta {
            border-left: 5px solid #dc3545;
            background: linear-gradient(90deg, #f8d7da, #ffffff);
            opacity: 0.8;
        }
        
        .badge-aprobada {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .badge-oculta {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
        }
        
        .btn-admin {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            margin: 0.125rem;
        }
        
        .comentario-texto {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            font-style: italic;
        }
        
        .info-tienda {
            background: #e9ecef;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .filtros-moderacion {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
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
                        <i class="bi bi-chat-dots"></i> Moderar Reseñas
                    </h1>
                    <p class="mb-0 opacity-75">Gestión y moderación de comentarios de usuarios</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Mensajes -->
        <?php if ($mensaje): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $mensaje; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="admin-card">
            <div class="row text-center">
                <div class="col-md-4">
                    <h4 class="text-primary"><?php echo $estadisticas['total']; ?></h4>
                    <p class="text-muted mb-0">Total Reseñas</p>
                </div>
                <div class="col-md-4">
                    <h4 class="text-success"><?php echo $estadisticas['aprobadas']; ?></h4>
                    <p class="text-muted mb-0">Aprobadas</p>
                </div>
                <div class="col-md-4">
                    <h4 class="text-danger"><?php echo $estadisticas['ocultas']; ?></h4>
                    <p class="text-muted mb-0">Ocultas</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="admin-card">
            <div class="filtros-moderacion">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Filtrar Reseñas
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 justify-content-end">
                            <a href="moderar_reseñas.php?estado=todas" 
                               class="btn <?php echo $filtro_estado === 'todas' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                                <i class="bi bi-list"></i> Todas
                            </a>
                            <a href="moderar_reseñas.php?estado=aprobadas" 
                               class="btn <?php echo $filtro_estado === 'aprobadas' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                                <i class="bi bi-check-circle"></i> Aprobadas
                            </a>
                            <a href="moderar_reseñas.php?estado=ocultas" 
                               class="btn <?php echo $filtro_estado === 'ocultas' ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                                <i class="bi bi-eye-slash"></i> Ocultas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de reseñas -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="bi bi-chat-square-text"></i> 
                    Reseñas 
                    <?php if ($filtro_estado !== 'todas'): ?>
                        <span class="badge bg-secondary"><?php echo ucfirst($filtro_estado); ?></span>
                    <?php endif; ?>
                </h3>
                <small class="text-muted">
                    Mostrando <?php echo count($reseñas); ?> reseñas
                </small>
            </div>
            
            <?php if (empty($reseñas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots" style="font-size: 4rem; color: #dee2e6;"></i>
                    <h4 class="mt-3 text-muted">No hay reseñas para mostrar</h4>
                    <p class="text-muted">
                        <?php if ($filtro_estado === 'todas'): ?>
                            Las reseñas aparecerán aquí cuando los usuarios las escriban
                        <?php else: ?>
                            No hay reseñas <?php echo $filtro_estado; ?> en este momento
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($reseñas as $reseña): ?>
                    <div class="reseña-card <?php echo $reseña['esta_aprobada'] ? 'reseña-aprobada' : 'reseña-oculta'; ?>">
                        <div class="row">
                            <!-- Información de la reseña -->
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2">
                                    <h6 class="mb-0 me-2"><?php echo htmlspecialchars($reseña['usuario_nombre']); ?></h6>
                                    
                                    <?php if ($reseña['esta_aprobada']): ?>
                                        <span class="badge badge-aprobada">
                                            <i class="bi bi-check-circle"></i> APROBADA
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-oculta">
                                            <i class="bi bi-eye-slash"></i> OCULTA
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-2">
                                    <?php echo mostrar_estrellas_moderacion($reseña['estrellas']); ?>
                                    <span class="ms-2 text-muted">(<?php echo $reseña['estrellas']; ?> estrellas)</span>
                                </div>
                                
                                <div class="comentario-texto mb-3">
                                    "<?php echo nl2br(htmlspecialchars($reseña['comentario'])); ?>"
                                </div>
                                
                                <div class="info-tienda">
                                    <strong>Tienda:</strong> <?php echo htmlspecialchars($reseña['nombre_tienda']); ?> 
                                    <span class="text-muted">
                                        (<?php echo htmlspecialchars($reseña['categoria']); ?>) - 
                                        Vendedor: <?php echo htmlspecialchars($reseña['vendedor_nombre']); ?>
                                    </span>
                                </div>
                                
                                <small class="text-muted">
                                    <i class="bi bi-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($reseña['fecha_calificacion'])); ?> - 
                                    <i class="bi bi-envelope"></i> 
                                    <?php echo htmlspecialchars($reseña['usuario_email']); ?>
                                </small>
                            </div>
                            
                            <!-- Acciones de moderación -->
                            <div class="col-md-4">
                                <div class="d-flex flex-column gap-2">
                                    <!-- Ver tienda -->
                                    <a href="tienda_detalle.php?id=<?php echo $reseña['tienda_id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-info btn-admin">
                                        <i class="bi bi-eye"></i> Ver Tienda
                                    </a>
                                    
                                    <!-- Aprobar/Ocultar -->
                                    <?php if ($reseña['esta_aprobada']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>">
                                            <input type="hidden" name="accion" value="ocultar">
                                            <button type="submit" class="btn btn-warning btn-admin w-100" 
                                                    onclick="return confirm('¿Ocultar esta reseña del público?')">
                                                <i class="bi bi-eye-slash"></i> Ocultar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>">
                                            <input type="hidden" name="accion" value="aprobar">
                                            <button type="submit" class="btn btn-success btn-admin w-100">
                                                <i class="bi bi-check-circle"></i> Aprobar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Eliminar permanentemente -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="reseña_id" value="<?php echo $reseña['id']; ?>">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <button type="submit" class="btn btn-outline-danger btn-admin w-100" 
                                                onclick="return confirm('¿ELIMINAR permanentemente esta reseña? Esta acción no se puede deshacer.')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>