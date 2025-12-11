<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

// Obtener estadísticas generales
try {
    // Total usuarios
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
    $stmt->execute();
    $total_usuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total tiendas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tiendas WHERE activo = 1");
    $stmt->execute();
    $total_tiendas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total calificaciones
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM calificaciones WHERE activo = 1");
    $stmt->execute();
    $total_calificaciones = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total fotos en galería
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE activo = 1");
    $stmt->execute();
    $total_fotos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // ACCIONES PENDIENTES
    // Reseñas pendientes de moderación
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM calificaciones WHERE esta_aprobada = 0 AND activo = 1");
    $stmt->execute();
    $reseñas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Tiendas pendientes de aprobación
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tiendas WHERE activo = 0");
    $stmt->execute();
    $tiendas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Reportes de tiendas pendientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reportes_tienda WHERE estado = 'pendiente'");
    $stmt->execute();
    $reportes_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch(PDOException $e) {
    $total_usuarios = $total_tiendas = $total_calificaciones = $total_fotos = 0;
    $reseñas_pendientes = $tiendas_pendientes = $reportes_pendientes = 0;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - Mercado Huasteco</title>
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
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            border-left: 5px solid #dc3545;
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            border: none;
            color: white;
            font-weight: bold;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            background: linear-gradient(45deg, #c82333, #e66b00);
            transform: translateY(-2px);
            color: white;
        }
        
        /* Estilos para Acciones Pendientes */
        .alert {
            border-radius: 12px;
            border: none;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border-left: 4px solid #ffc107;
        }
        
        .alert-info {
            background: linear-gradient(135deg, #d1ecf1, #a8e6cf);
            border-left: 4px solid #17a2b8;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #ffb3ba);
            border-left: 4px solid #dc3545;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border-left: 4px solid #28a745;
        }
        
        .badge.fs-6 {
            font-size: 1.1rem !important;
            padding: 0.5rem 0.75rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: #dc3545;
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
                        <i class="bi bi-shield-check"></i> Panel de Administrador
                    </h1>
                    <p class="mb-0 opacity-75">Gestión completa de Mercado Huasteco</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="directorio.php">
                                <i class="bi bi-grid"></i> Ver Directorio
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas generales -->
        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <i class="bi bi-people text-primary" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $total_usuarios; ?></div>
                    <p class="text-muted mb-0">Usuarios Totales</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <i class="bi bi-shop text-success" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $total_tiendas; ?></div>
                    <p class="text-muted mb-0">Tiendas Registradas</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <i class="bi bi-star text-warning" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $total_calificaciones; ?></div>
                    <p class="text-muted mb-0">Reseñas Publicadas</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stats-card text-center">
                    <i class="bi bi-images text-info" style="font-size: 3rem;"></i>
                    <div class="stat-number"><?php echo $total_fotos; ?></div>
                    <p class="text-muted mb-0">Fotos en Galerías</p>
                </div>
            </div>
        </div>

        <!-- Acciones Pendientes -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="admin-card">
                    <h3 class="mb-4 text-warning">
                        <i class="bi bi-exclamation-triangle"></i> Acciones Pendientes
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-warning d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-chat-square-text" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-1">Reseñas por Moderar</h5>
                                    <p class="mb-2">
                                        <span class="badge bg-warning text-dark fs-6"><?php echo $reseñas_pendientes; ?></span>
                                        reseñas esperando moderación
                                    </p>
                                    <a href="moderar_reseñas.php" class="btn btn-warning btn-sm">
                                        <i class="bi bi-eye"></i> Moderar Reseñas
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="alert alert-info d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-shop" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-1">Tiendas por Aprobar</h5>
                                    <p class="mb-2">
                                        <span class="badge bg-info fs-6"><?php echo $tiendas_pendientes; ?></span>
                                        tiendas esperando aprobación
                                    </p>
                                    <a href="gestionar_tiendas.php" class="btn btn-info btn-sm">
                                        <i class="bi bi-check-circle"></i> Gestionar Tiendas
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reportes de Tiendas -->
                    <?php if ($reportes_pendientes > 0): ?>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="alert alert-danger d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="bi bi-flag-fill" style="font-size: 2rem;"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading mb-1">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Reportes de Tiendas Pendientes
                                    </h5>
                                    <p class="mb-2">
                                        <span class="badge bg-danger fs-6"><?php echo $reportes_pendientes; ?></span>
                                        reporte(s) de tiendas esperando revisión
                                    </p>
                                    <a href="admin_ver_reportes.php" class="btn btn-danger">
                                        <i class="bi bi-eye-fill me-2"></i>Ver Reportes Ahora
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($reseñas_pendientes > 0 || $tiendas_pendientes > 0 || $reportes_pendientes > 0): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-danger text-center">
                                    <i class="bi bi-bell"></i>
                                    <strong>¡Atención!</strong> Tienes <?php echo ($reseñas_pendientes + $tiendas_pendientes + $reportes_pendientes); ?> acciones pendientes que requieren tu atención.
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-success text-center">
                                    <i class="bi bi-check-circle"></i>
                                    <strong>¡Excelente!</strong> No tienes acciones pendientes en este momento.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Panel de administración -->
        <div class="row">
            <div class="col-md-12">
                <div class="admin-card">
                    <h3 class="mb-4">
                        <i class="bi bi-gear"></i> Herramientas de Administración
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="gestionar_usuarios.php" class="btn btn-admin">
                                    <i class="bi bi-people"></i> Gestionar Usuarios
                                </a>
                            </div>
                            <small class="text-muted">Cambiar roles, activar y administrar usuarios</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="gestionar_tiendas.php" class="btn btn-admin">
                                    <i class="bi bi-shop"></i> Gestionar Tiendas
                                </a>
                            </div>
                            <small class="text-muted">Destacar, activar y administrar tiendas</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="moderar_reseñas.php" class="btn btn-admin">
                                    <i class="bi bi-chat-dots"></i> Moderar Reseñas
                                </a>
                            </div>
                            <small class="text-muted">Aprobar, ocultar y gestionar comentarios</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="admin_ver_reportes.php" class="btn btn-admin position-relative">
                                    <i class="bi bi-flag-fill"></i> Reportes de Tiendas
                                    <?php if ($reportes_pendientes > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?php echo $reportes_pendientes; ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <small class="text-muted">Gestionar reportes de contenido inapropiado</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="gestionar_chispitas.php" class="btn btn-admin">
                                    <i class="bi bi-robot"></i> Gestionar Chispitas
                                </a>
                            </div>
                            <small class="text-muted">Configurar diálogos del asistente virtual</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="reportes.php" class="btn btn-admin">
                                    <i class="bi bi-bar-chart"></i> Reportes
                                </a>
                            </div>
                            <small class="text-muted">Estadísticas y análisis detallados</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="./configuracion.php" class="btn btn-admin" onclick="console.log('Clic en configuración'); return true;">
                                    <i class="bi bi-gear"></i> Configuración
                                </a>
                            </div>
                            <small class="text-muted">Ajustes del sistema y configuración del sitio</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="mi_perfil.php" class="btn btn-admin">
                                    <i class="bi bi-person-circle"></i> Mi Perfil
                                </a>
                            </div>
                            <small class="text-muted">Configurar pregunta secreta y perfil</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="gestionar_busqueda.php" class="btn btn-admin">
                                    <i class="bi bi-search"></i> Búsqueda Inteligente
                                </a>
                            </div>
                            <small class="text-muted">Configurar sinónimos y términos relacionados</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <div class="d-grid">
                                <a href="directorio.php" class="btn btn-admin">
                                    <i class="bi bi-eye"></i> Ver Sitio Público
                                </a>
                            </div>
                            <small class="text-muted">Ver el directorio como usuario</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del administrador -->
        <div class="row">
            <div class="col-md-12">
                <div class="admin-card">
                    <h4 class="mb-3">
                        <i class="bi bi-person-badge"></i> Información del Administrador
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($_SESSION['nombre']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                            <p><strong>Rol:</strong> <span class="badge bg-danger">Administrador</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Última sesión:</strong> <?php echo date('d/m/Y H:i'); ?></p>
                            <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
                            <p><strong>Permisos:</strong> Control total del sistema</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>