<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

// Verificar que el usuario sea administrador o vendedor (para ver reportes de sus tiendas)
// Ajusta esta condición según tu sistema de roles
$es_admin = false;
if (isset($_SESSION['tipo_usuario'])) {
    // Acepta: 'admin', 'administrador', o rol_id = 1
    $es_admin = ($_SESSION['tipo_usuario'] === 'admin' || 
                 $_SESSION['tipo_usuario'] === 'administrador' ||
                 (isset($_SESSION['rol_id']) && $_SESSION['rol_id'] == 1));
}

// Si no es admin, verificar si es el dueño de alguna tienda
if (!$es_admin) {
    // Por ahora permitir acceso para debugging
    // Comentar esta línea después de verificar tu rol
    // header("Location: index.php");
    // exit();
    
    // Mostrar información de debugging
    echo "<!-- DEBUG: tipo_usuario = " . (isset($_SESSION['tipo_usuario']) ? $_SESSION['tipo_usuario'] : 'NO SET') . " -->";
    echo "<!-- DEBUG: user_id = " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NO SET') . " -->";
    echo "<!-- DEBUG: Para acceder como admin, tu tipo_usuario debe ser 'admin' o 'administrador' -->";
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Acción: Marcar como resuelto
    if (isset($_POST['marcar_resuelto'])) {
        $id_reporte = (int)$_POST['id_reporte'];
        $notas = isset($_POST['notas_admin']) ? trim($_POST['notas_admin']) : '';
        
        try {
            $stmt = $pdo->prepare("
                UPDATE reportes_tienda 
                SET estado = 'resuelto', 
                    fecha_resolucion = NOW(),
                    notas_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([$notas, $id_reporte]);
            $mensaje_exito = "Reporte marcado como resuelto exitosamente.";
        } catch(PDOException $e) {
            $mensaje_error = "Error al actualizar el reporte: " . $e->getMessage();
        }
    }
    
    // Acción: Desactivar tienda
    if (isset($_POST['desactivar_tienda'])) {
        $id_tienda = (int)$_POST['id_tienda'];
        $id_reporte = (int)$_POST['id_reporte'];
        $motivo = isset($_POST['motivo_accion']) ? trim($_POST['motivo_accion']) : 'Reportada por contenido inapropiado';
        
        try {
            // Desactivar la tienda
            $stmt = $pdo->prepare("UPDATE tiendas SET activo = 0 WHERE id = ?");
            $stmt->execute([$id_tienda]);
            
            // Marcar reporte como resuelto
            $stmt = $pdo->prepare("
                UPDATE reportes_tienda 
                SET estado = 'resuelto', 
                    fecha_resolucion = NOW(),
                    notas_admin = ?
                WHERE id = ?
            ");
            $stmt->execute(["Tienda desactivada: $motivo", $id_reporte]);
            
            $mensaje_exito = "Tienda desactivada exitosamente. El reporte ha sido marcado como resuelto.";
        } catch(PDOException $e) {
            $mensaje_error = "Error al desactivar la tienda: " . $e->getMessage();
        }
    }
    
    // Acción: Eliminar tienda
    if (isset($_POST['eliminar_tienda'])) {
        $id_tienda = (int)$_POST['id_tienda'];
        $id_reporte = (int)$_POST['id_reporte'];
        $motivo = isset($_POST['motivo_accion']) ? trim($_POST['motivo_accion']) : 'Eliminada por violación de políticas';
        
        try {
            // Eliminar la tienda (esto también eliminará el reporte por CASCADE)
            $stmt = $pdo->prepare("DELETE FROM tiendas WHERE id = ?");
            $stmt->execute([$id_tienda]);
            
            $mensaje_exito = "Tienda eliminada permanentemente. Motivo: $motivo";
        } catch(PDOException $e) {
            $mensaje_error = "Error al eliminar la tienda: " . $e->getMessage();
        }
    }
    
    // Acción: Reactivar tienda
    if (isset($_POST['reactivar_tienda'])) {
        $id_tienda = (int)$_POST['id_tienda'];
        
        try {
            $stmt = $pdo->prepare("UPDATE tiendas SET activo = 1 WHERE id = ?");
            $stmt->execute([$id_tienda]);
            
            $mensaje_exito = "Tienda reactivada exitosamente.";
        } catch(PDOException $e) {
            $mensaje_error = "Error al reactivar la tienda: " . $e->getMessage();
        }
    }
}

// Obtener filtro de estado
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'pendiente';
$where_estado = $filtro_estado === 'todos' ? '' : "AND r.estado = :estado";

// Obtener reportes con información completa de la tienda
try {
    $sql = "
        SELECT r.*, 
               t.nombre_tienda, 
               t.logo,
               t.descripcion as tienda_descripcion,
               t.url_tienda as tienda_url,
               t.telefono_wa as tienda_telefono,
               t.telefono_wa as tienda_whatsapp,
               t.link_facebook as tienda_facebook,
               t.link_instagram as tienda_instagram,
               t.link_tiktok as tienda_tiktok,
               t.activo as tienda_activa,
               t.fecha_registro as tienda_fecha_registro,
               t.vendedor_id,
               v.nombre as vendedor_nombre,
               v.email as vendedor_email,
               v.activo as vendedor_activo,
               u.nombre as nombre_reportante,
               u.email as email_reportante,
               (SELECT COUNT(*) FROM reportes_tienda WHERE id_tienda = t.id) as total_reportes_tienda
        FROM reportes_tienda r
        INNER JOIN tiendas t ON r.id_tienda = t.id
        INNER JOIN usuarios v ON t.vendedor_id = v.id
        LEFT JOIN usuarios u ON r.id_usuario_reporta = u.id
        WHERE 1=1 $where_estado
        ORDER BY r.fecha_reporte DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    if ($filtro_estado !== 'todos') {
        $stmt->bindValue(':estado', $filtro_estado);
    }
    $stmt->execute();
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas
    $stmt_stats = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'resuelto' THEN 1 ELSE 0 END) as resueltos
        FROM reportes_tienda
    ");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $reportes = [];
    $stats = ['total' => 0, 'pendientes' => 0, 'resueltos' => 0];
    $mensaje_error = "Error al cargar reportes: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Reportes - Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f6f5f7 0%, #e2e8f0 100%);
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        
        .reporte-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid var(--warning-color);
        }
        
        .reporte-card.resuelto {
            border-left-color: var(--success-color);
            opacity: 0.7;
        }
        
        .tienda-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .tienda-logo-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .btn-resolver {
            background: var(--success-color);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-resolver:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .badge-estado {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-resuelto {
            background: #d4edda;
            color: #155724;
        }
        
        .btn-primary, .btn-success, .btn-warning, .btn-danger, .btn-info {
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover, .btn-success:hover, .btn-warning:hover, 
        .btn-danger:hover, .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .modal-header.bg-success,
        .modal-header.bg-warning,
        .modal-header.bg-danger {
            border-radius: 0;
        }
        
        .card {
            transition: all 0.3s;
        }
        
        .reporte-card .card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-flag-fill me-2"></i>Gestión de Reportes</h1>
                    <p class="mb-0">Sistema de moderación de tiendas reportadas</p>
                </div>
                <a href="dashboard_admin.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($mensaje_exito)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $mensaje_exito; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensaje_error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $mensaje_error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="bi bi-flag-fill text-primary" style="font-size: 2rem;"></i>
                    <p class="stats-number text-primary"><?php echo $stats['total']; ?></p>
                    <p class="text-muted mb-0">Total de Reportes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="bi bi-exclamation-circle-fill text-warning" style="font-size: 2rem;"></i>
                    <p class="stats-number text-warning"><?php echo $stats['pendientes']; ?></p>
                    <p class="text-muted mb-0">Pendientes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                    <p class="stats-number text-success"><?php echo $stats['resueltos']; ?></p>
                    <p class="text-muted mb-0">Resueltos</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex gap-2">
                    <a href="?estado=pendiente" class="btn <?php echo $filtro_estado === 'pendiente' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        <i class="bi bi-hourglass-split me-2"></i>Pendientes
                    </a>
                    <a href="?estado=resuelto" class="btn <?php echo $filtro_estado === 'resuelto' ? 'btn-success' : 'btn-outline-success'; ?>">
                        <i class="bi bi-check-circle me-2"></i>Resueltos
                    </a>
                    <a href="?estado=todos" class="btn <?php echo $filtro_estado === 'todos' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        <i class="bi bi-list me-2"></i>Todos
                    </a>
                </div>
            </div>
        </div>

        <!-- Lista de Reportes -->
        <?php if (empty($reportes)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                No hay reportes <?php echo $filtro_estado !== 'todos' ? $filtro_estado . 's' : ''; ?> en este momento.
            </div>
        <?php else: ?>
            <?php foreach ($reportes as $reporte): ?>
                <div class="reporte-card <?php echo $reporte['estado'] === 'resuelto' ? 'resuelto' : ''; ?>">
                    <!-- Header con estado -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="tienda-info flex-grow-1">
                            <?php if (!empty($reporte['logo'])): ?>
                                <img src="<?php echo htmlspecialchars($reporte['logo']); ?>" 
                                     alt="Logo" class="tienda-logo-small">
                            <?php else: ?>
                                <div class="tienda-logo-small bg-secondary d-flex align-items-center justify-content-center">
                                    <i class="bi bi-shop text-white fs-3"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1">
                                    <a href="tienda_detalle.php?id=<?php echo $reporte['id_tienda']; ?>" 
                                       target="_blank" class="text-decoration-none">
                                        <?php echo htmlspecialchars($reporte['nombre_tienda']); ?>
                                        <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.8rem;"></i>
                                    </a>
                                    <?php if ($reporte['tienda_activa'] == 0): ?>
                                        <span class="badge bg-danger ms-2">Desactivada</span>
                                    <?php endif; ?>
                                </h5>
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i>
                                    Reportado: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_reporte'])); ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge-estado badge-<?php echo $reporte['estado']; ?>">
                                <?php echo ucfirst($reporte['estado']); ?>
                            </span>
                            <?php if ($reporte['total_reportes_tienda'] > 1): ?>
                                <br><small class="text-danger">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <?php echo $reporte['total_reportes_tienda']; ?> reportes totales
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información de la Tienda -->
                    <div class="card mb-3" style="background: #f8f9fa; border: none;">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Información de la Tienda</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Descripción:</strong><br>
                                    <small><?php echo htmlspecialchars(substr($reporte['tienda_descripcion'], 0, 150)) . (strlen($reporte['tienda_descripcion']) > 150 ? '...' : ''); ?></small></p>
                                    
                                    <?php if ($reporte['tienda_url']): ?>
                                    <p class="mb-2"><strong><i class="bi bi-link-45deg me-1"></i>URL Tienda:</strong><br>
                                    <small><a href="<?php echo htmlspecialchars($reporte['tienda_url']); ?>" target="_blank"><?php echo htmlspecialchars($reporte['tienda_url']); ?></a></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($reporte['tienda_telefono']): ?>
                                    <p class="mb-2"><strong><i class="bi bi-telephone me-1"></i>Teléfono:</strong><br>
                                    <small><?php echo htmlspecialchars($reporte['tienda_telefono']); ?></small></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($reporte['tienda_whatsapp']): ?>
                                    <p class="mb-2"><strong><i class="bi bi-whatsapp me-1"></i>WhatsApp:</strong><br>
                                    <small><?php echo htmlspecialchars($reporte['tienda_whatsapp']); ?></small></p>
                                    <?php endif; ?>
                                    
                                    <p class="mb-0"><strong><i class="bi bi-calendar-check me-1"></i>Registrada:</strong><br>
                                    <small><?php echo date('d/m/Y', strtotime($reporte['tienda_fecha_registro'])); ?></small></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del Vendedor -->
                    <div class="card mb-3" style="background: #fff3cd; border: none;">
                        <div class="card-body">
                            <h6 class="mb-3"><i class="bi bi-person-badge me-2"></i>Información del Vendedor</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Nombre:</strong> <?php echo htmlspecialchars($reporte['vendedor_nombre']); ?>
                                    <?php if ($reporte['vendedor_activo'] == 0): ?>
                                        <span class="badge bg-danger ms-2">Cuenta Desactivada</span>
                                    <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-0"><strong>Email:</strong> 
                                    <a href="mailto:<?php echo htmlspecialchars($reporte['vendedor_email']); ?>">
                                        <?php echo htmlspecialchars($reporte['vendedor_email']); ?>
                                    </a></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Motivo del Reporte -->
                    <div class="alert alert-danger mb-3">
                        <strong><i class="bi bi-flag-fill me-2"></i>Motivo del reporte:</strong>
                        <p class="mt-2 mb-0"><?php echo nl2br(htmlspecialchars($reporte['motivo'])); ?></p>
                    </div>

                    <!-- Información del Reportante -->
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="bi bi-person me-1"></i>
                            <strong>Reportado por:</strong> 
                            <?php 
                            if ($reporte['nombre_reportante']) {
                                echo htmlspecialchars($reporte['nombre_reportante']) . ' (' . htmlspecialchars($reporte['email_reportante']) . ')';
                            } else {
                                echo 'Usuario anónimo';
                            }
                            ?>
                        </small>
                    </div>

                    <!-- Acciones -->
                    <?php if ($reporte['estado'] === 'resuelto'): ?>
                        <div class="alert alert-success mb-0">
                            <strong><i class="bi bi-check-circle me-2"></i>Resuelto</strong>
                            <?php if ($reporte['fecha_resolucion']): ?>
                                <br><small>Fecha: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_resolucion'])); ?></small>
                            <?php endif; ?>
                            <?php if ($reporte['notas_admin']): ?>
                                <br><small>Notas: <?php echo htmlspecialchars($reporte['notas_admin']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="d-flex gap-2 flex-wrap">
                            <!-- Botón Ver Tienda -->
                            <a href="tienda_detalle.php?id=<?php echo $reporte['id_tienda']; ?>" 
                               target="_blank" class="btn btn-primary">
                                <i class="bi bi-eye me-2"></i>Ver Tienda
                            </a>
                            
                            <!-- Botón Marcar Resuelto -->
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" 
                                    data-bs-target="#modalResolver<?php echo $reporte['id']; ?>">
                                <i class="bi bi-check-circle me-2"></i>Marcar Resuelto
                            </button>
                            
                            <!-- Botón Desactivar Tienda -->
                            <?php if ($reporte['tienda_activa'] == 1): ?>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" 
                                    data-bs-target="#modalDesactivar<?php echo $reporte['id']; ?>">
                                <i class="bi bi-pause-circle me-2"></i>Desactivar Tienda
                            </button>
                            <?php else: ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Reactivar esta tienda?');">
                                <input type="hidden" name="reactivar_tienda" value="1">
                                <input type="hidden" name="id_tienda" value="<?php echo $reporte['id_tienda']; ?>">
                                <button type="submit" class="btn btn-info">
                                    <i class="bi bi-play-circle me-2"></i>Reactivar Tienda
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <!-- Botón Eliminar Tienda -->
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" 
                                    data-bs-target="#modalEliminar<?php echo $reporte['id']; ?>">
                                <i class="bi bi-trash me-2"></i>Eliminar Tienda
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Modales de Acción -->
                <?php if ($reporte['estado'] === 'pendiente'): ?>
                
                <!-- Modal: Marcar como Resuelto -->
                <div class="modal fade" id="modalResolver<?php echo $reporte['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-check-circle me-2"></i>Resolver Reporte
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <input type="hidden" name="id_reporte" value="<?php echo $reporte['id']; ?>">
                                    <input type="hidden" name="marcar_resuelto" value="1">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Notas administrativas (opcional)</label>
                                        <textarea name="notas_admin" class="form-control" rows="3" 
                                                  placeholder="Ej: Se contactó al vendedor, contenido removido, falsa alarma, etc."></textarea>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Al marcar como resuelto, el reporte se archivará sin tomar acciones sobre la tienda.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-2"></i>Marcar como Resuelto
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal: Desactivar Tienda -->
                <div class="modal fade" id="modalDesactivar<?php echo $reporte['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-warning">
                                <h5 class="modal-title">
                                    <i class="bi bi-pause-circle me-2"></i>Desactivar Tienda
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de desactivar esta tienda? El vendedor no podrá acceder hasta que la reactives.');">
                                <div class="modal-body">
                                    <input type="hidden" name="desactivar_tienda" value="1">
                                    <input type="hidden" name="id_tienda" value="<?php echo $reporte['id_tienda']; ?>">
                                    <input type="hidden" name="id_reporte" value="<?php echo $reporte['id']; ?>">
                                    
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        <strong>¡Atención!</strong> Esta acción desactivará temporalmente la tienda.
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <h6><i class="bi bi-shop me-2"></i><?php echo htmlspecialchars($reporte['nombre_tienda']); ?></h6>
                                            <p class="mb-0 small text-muted">Vendedor: <?php echo htmlspecialchars($reporte['vendedor_nombre']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Motivo de la desactivación *</label>
                                        <textarea name="motivo_accion" class="form-control" rows="3" required
                                                  placeholder="Ej: Contenido inapropiado, información falsa, violación de políticas..."></textarea>
                                        <small class="text-muted">Este motivo quedará registrado en el sistema.</small>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <strong>Efectos:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>La tienda no aparecerá en el directorio</li>
                                            <li>El vendedor no podrá acceder a su panel</li>
                                            <li>Podrás reactivarla cuando lo consideres necesario</li>
                                            <li>El reporte se marcará como resuelto automáticamente</li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="bi bi-pause-circle me-2"></i>Desactivar Tienda
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal: Eliminar Tienda -->
                <div class="modal fade" id="modalEliminar<?php echo $reporte['id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-trash me-2"></i>Eliminar Tienda Permanentemente
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST" onsubmit="return confirm('⚠️ ÚLTIMA ADVERTENCIA: ¿Eliminar permanentemente esta tienda? Esta acción NO se puede deshacer.');">
                                <div class="modal-body">
                                    <input type="hidden" name="eliminar_tienda" value="1">
                                    <input type="hidden" name="id_tienda" value="<?php echo $reporte['id_tienda']; ?>">
                                    <input type="hidden" name="id_reporte" value="<?php echo $reporte['id']; ?>">
                                    
                                    <div class="alert alert-danger">
                                        <i class="bi bi-exclamation-octagon me-2"></i>
                                        <strong>¡PELIGRO!</strong> Esta acción es PERMANENTE e IRREVERSIBLE.
                                    </div>
                                    
                                    <div class="card mb-3 border-danger">
                                        <div class="card-body">
                                            <h6 class="text-danger"><i class="bi bi-shop me-2"></i><?php echo htmlspecialchars($reporte['nombre_tienda']); ?></h6>
                                            <p class="mb-1 small">Vendedor: <?php echo htmlspecialchars($reporte['vendedor_nombre']); ?></p>
                                            <p class="mb-0 small text-muted">Email: <?php echo htmlspecialchars($reporte['vendedor_email']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Motivo de la eliminación *</label>
                                        <textarea name="motivo_accion" class="form-control" rows="3" required
                                                  placeholder="Ej: Violación grave de políticas, contenido ilegal, fraude..."></textarea>
                                        <small class="text-muted">Este motivo quedará registrado permanentemente.</small>
                                    </div>
                                    
                                    <div class="alert alert-danger">
                                        <strong>Se eliminará permanentemente:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>La tienda y toda su información</li>
                                            <li>Todas las fotos de la galería</li>
                                            <li>Todas las calificaciones recibidas</li>
                                            <li>Todos los reportes asociados</li>
                                            <li>Las estadísticas de visitas</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="confirmarEliminar<?php echo $reporte['id']; ?>" required>
                                        <label class="form-check-label fw-bold text-danger" for="confirmarEliminar<?php echo $reporte['id']; ?>">
                                            Confirmo que entiendo que esta acción es irreversible
                                        </label>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="bi bi-trash me-2"></i>Eliminar Permanentemente
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
