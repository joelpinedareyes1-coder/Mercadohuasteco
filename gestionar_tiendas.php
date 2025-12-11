<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$mensaje = '';
$error = '';

// Procesar acciones de destacar/quitar destacado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && isset($_POST['tienda_id'])) {
        $tienda_id = (int)$_POST['tienda_id'];
        $accion = $_POST['accion'];
        
        try {
            if ($accion === 'destacar') {
                $stmt = $pdo->prepare("UPDATE tiendas SET es_destacado = 1 WHERE id = ?");
                $stmt->execute([$tienda_id]);
                $mensaje = "Tienda marcada como destacada exitosamente.";
            } elseif ($accion === 'quitar_destacado') {
                $stmt = $pdo->prepare("UPDATE tiendas SET es_destacado = 0 WHERE id = ?");
                $stmt->execute([$tienda_id]);
                $mensaje = "Destacado removido de la tienda exitosamente.";
            } elseif ($accion === 'activar') {
                // Intentar con columna estado primero, luego activo
                try {
                    $stmt = $pdo->prepare("UPDATE tiendas SET estado = 1 WHERE id = ?");
                    $stmt->execute([$tienda_id]);
                    $mensaje = "Tienda activada exitosamente.";
                } catch (PDOException $e) {
                    // Si falla (columna estado no existe), usar activo
                    $stmt = $pdo->prepare("UPDATE tiendas SET activo = 1 WHERE id = ?");
                    $stmt->execute([$tienda_id]);
                    $mensaje = "Tienda activada exitosamente.";
                }
            } elseif ($accion === 'desactivar') {
                // Intentar con columna estado primero, luego activo
                try {
                    $stmt = $pdo->prepare("UPDATE tiendas SET estado = 0 WHERE id = ?");
                    $stmt->execute([$tienda_id]);
                    $mensaje = "Tienda desactivada exitosamente.";
                } catch (PDOException $e) {
                    // Si falla (columna estado no existe), usar activo
                    $stmt = $pdo->prepare("UPDATE tiendas SET activo = 0 WHERE id = ?");
                    $stmt->execute([$tienda_id]);
                    $mensaje = "Tienda desactivada exitosamente.";
                }
            } elseif ($accion === 'eliminar') {
                // Eliminar tienda permanentemente
                $pdo->beginTransaction();
                
                try {
                    // 1. Eliminar reseñas de la tienda
                    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE tienda_id = ?");
                    $stmt->execute([$tienda_id]);
                    $reseñas_eliminadas = $stmt->rowCount();
                    
                    // 2. Eliminar fotos de la galería
                    $stmt = $pdo->prepare("DELETE FROM galeria_tiendas WHERE tienda_id = ?");
                    $stmt->execute([$tienda_id]);
                    $fotos_eliminadas = $stmt->rowCount();
                    
                    // 3. Eliminar la tienda
                    $stmt = $pdo->prepare("DELETE FROM tiendas WHERE id = ?");
                    $stmt->execute([$tienda_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        $pdo->commit();
                        $mensaje = "Tienda eliminada permanentemente. Se eliminaron $reseñas_eliminadas reseñas y $fotos_eliminadas fotos.";
                    } else {
                        throw new Exception("No se pudo eliminar la tienda.");
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
        } catch(PDOException $e) {
            $error = "Error al actualizar la tienda: " . $e->getMessage();
        }
    }
}

// Obtener todas las tiendas con información del vendedor
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.nombre as vendedor_nombre, u.email as vendedor_email,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones,
               (SELECT COUNT(*) FROM galeria_tiendas g WHERE g.tienda_id = t.id AND g.activo = 1) as total_fotos
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1
        GROUP BY t.id, u.nombre, u.email
        ORDER BY t.es_destacado DESC, t.fecha_registro DESC
    ");
    $stmt->execute();
    $tiendas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determinar estado activo para cada tienda (usar estado si existe, sino activo)
    $tiendas = [];
    foreach ($tiendas_raw as $tienda) {
        if (isset($tienda['estado'])) {
            $tienda['tienda_activa'] = ($tienda['estado'] == 1);
        } elseif (isset($tienda['activo'])) {
            $tienda['tienda_activa'] = ($tienda['activo'] == 1);
        } else {
            $tienda['tienda_activa'] = true; // Por defecto activa
        }
        $tiendas[] = $tienda;
    }
} catch(PDOException $e) {
    $tiendas = [];
    $error = "Error al cargar las tiendas: " . $e->getMessage();
}

// Función para mostrar estrellas
function mostrar_estrellas_admin($promedio) {
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
    
    return $estrellas_html . ' (' . $promedio_redondeado . ')';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Tiendas - Panel Admin</title>
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
        
        .tienda-row {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .tienda-row:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .tienda-destacada {
            border-left: 5px solid #ffc107;
            background: linear-gradient(90deg, #fff3cd, #ffffff);
        }
        
        .tienda-inactiva {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        .logo-tienda-admin {
            width: 80px;
            height: 80px;
            object-fit: cover;
            object-position: center;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .logo-tienda-admin:hover {
            transform: scale(1.1);
        }
        
        .logo-placeholder-admin {
            width: 80px;
            height: 80px;
            background: #e9ecef;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 2rem;
        }
        
        .btn-admin {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            margin: 0.125rem;
        }
        
        .stats-mini {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 5px;
            margin: 0.25rem 0;
            font-size: 0.875rem;
        }
        
        .badge-destacado {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: #000;
            font-weight: bold;
        }
        
        .table-actions {
            min-width: 200px;
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
                        <i class="bi bi-shop"></i> Gestionar Tiendas
                    </h1>
                    <p class="mb-0 opacity-75">Administración completa de tiendas registradas</p>
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

        <!-- Estadísticas rápidas -->
        <div class="admin-card">
            <div class="row text-center">
                <div class="col-md-3">
                    <h4 class="text-primary"><?php echo count($tiendas); ?></h4>
                    <p class="text-muted mb-0">Total Tiendas</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-warning">
                        <?php echo count(array_filter($tiendas, function($t) { return $t['es_destacado']; })); ?>
                    </h4>
                    <p class="text-muted mb-0">Destacadas</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-success">
                        <?php echo count(array_filter($tiendas, function($t) { return $t['tienda_activa']; })); ?>
                    </h4>
                    <p class="text-muted mb-0">Activas</p>
                </div>
                <div class="col-md-3">
                    <h4 class="text-danger">
                        <?php echo count(array_filter($tiendas, function($t) { return !$t['tienda_activa']; })); ?>
                    </h4>
                    <p class="text-muted mb-0">Inactivas</p>
                </div>
            </div>
        </div>

        <!-- Lista de tiendas -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="bi bi-list"></i> Lista de Tiendas
                </h3>
                <div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> 
                        Las tiendas destacadas aparecen primero
                    </small>
                </div>
            </div>
            
            <?php if (empty($tiendas)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-shop" style="font-size: 4rem; color: #dee2e6;"></i>
                    <h4 class="mt-3 text-muted">No hay tiendas registradas</h4>
                    <p class="text-muted">Las tiendas aparecerán aquí cuando los vendedores se registren</p>
                </div>
            <?php else: ?>
                <?php foreach ($tiendas as $tienda): ?>
                    <div class="tienda-row <?php echo $tienda['es_destacado'] ? 'tienda-destacada' : ''; ?> <?php echo !$tienda['tienda_activa'] ? 'tienda-inactiva' : ''; ?>">
                        <div class="row align-items-center">
                            <!-- Logo -->
                            <div class="col-md-1">
                                <?php if (!empty($tienda['logo']) && file_exists($tienda['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($tienda['logo']); ?>" 
                                         class="logo-tienda-admin" 
                                         alt="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                                <?php else: ?>
                                    <div class="logo-placeholder-admin">
                                        <i class="bi bi-shop"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Información de la tienda -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-2">
                                    <h5 class="mb-0 me-2"><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></h5>
                                    
                                    <?php if ($tienda['es_destacado']): ?>
                                        <span class="badge badge-destacado">
                                            <i class="bi bi-star-fill"></i> DESTACADA
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!$tienda['tienda_activa']): ?>
                                        <span class="badge bg-secondary ms-1">INACTIVA</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-muted mb-1">
                                    <i class="bi bi-tag"></i> <?php echo htmlspecialchars($tienda['categoria']); ?>
                                </p>
                                <p class="text-muted mb-1">
                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($tienda['vendedor_email']); ?>
                                </p>
                            </div>
                            
                            <!-- Estadísticas -->
                            <div class="col-md-3">
                                <div class="stats-mini">
                                    <i class="bi bi-mouse"></i> <?php echo $tienda['clics']; ?> visitas
                                </div>
                                <div class="stats-mini">
                                    <i class="bi bi-star"></i> <?php echo mostrar_estrellas_admin($tienda['promedio_estrellas']); ?>
                                </div>
                                <div class="stats-mini">
                                    <i class="bi bi-chat"></i> <?php echo $tienda['total_calificaciones']; ?> reseñas
                                </div>
                                <div class="stats-mini">
                                    <i class="bi bi-images"></i> <?php echo $tienda['total_fotos']; ?> fotos
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="col-md-4 table-actions">
                                <div class="d-flex flex-wrap justify-content-end">
                                    <!-- Ver tienda -->
                                    <a href="tienda_detalle.php?id=<?php echo $tienda['id']; ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-primary btn-admin">
                                        <i class="bi bi-eye"></i> Ver
                                    </a>
                                    
                                    <!-- Visitar sitio -->
                                    <a href="<?php echo htmlspecialchars($tienda['url_tienda']); ?>" 
                                       target="_blank" 
                                       class="btn btn-outline-success btn-admin">
                                        <i class="bi bi-box-arrow-up-right"></i> Sitio
                                    </a>
                                    
                                    <!-- Destacar/Quitar destacado -->
                                    <?php if ($tienda['es_destacado']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tienda_id" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="accion" value="quitar_destacado">
                                            <button type="submit" class="btn btn-warning btn-admin" 
                                                    onclick="return confirm('¿Quitar destacado de esta tienda?')">
                                                <i class="bi bi-star"></i> Quitar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tienda_id" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="accion" value="destacar">
                                            <button type="submit" class="btn btn-outline-warning btn-admin">
                                                <i class="bi bi-star"></i> Destacar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Activar/Desactivar -->
                                    <?php if ($tienda['tienda_activa']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tienda_id" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="accion" value="desactivar">
                                            <button type="submit" class="btn btn-danger btn-admin" 
                                                    onclick="return confirm('¿Desactivar esta tienda? Se ocultará del directorio público.')">
                                                <i class="bi bi-x-circle"></i> Desactivar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="tienda_id" value="<?php echo $tienda['id']; ?>">
                                            <input type="hidden" name="accion" value="activar">
                                            <button type="submit" class="btn btn-success btn-admin">
                                                <i class="bi bi-check-circle"></i> Activar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <!-- Eliminar -->
                                    <button type="button" class="btn btn-outline-dark btn-admin" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEliminarTienda"
                                            data-tienda-id="<?php echo $tienda['id']; ?>"
                                            data-tienda-nombre="<?php echo htmlspecialchars($tienda['nombre_tienda']); ?>">
                                        <i class="bi bi-trash3"></i> Eliminar
                                    </button>
                                </div>
                                
                                <small class="text-muted d-block mt-1">
                                    Registrada: <?php echo date('d/m/Y', strtotime($tienda['fecha_registro'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Eliminar Tienda -->
    <div class="modal fade" id="modalEliminarTienda" tabindex="-1" aria-labelledby="modalEliminarTiendaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarTiendaLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Eliminar Tienda Permanentemente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle-fill"></i> ¡ADVERTENCIA!</h6>
                        <p><strong>Esta acción NO se puede deshacer.</strong> La tienda y toda su información será eliminada permanentemente.</p>
                    </div>
                    
                    <h6>¿Estás seguro de que quieres eliminar esta tienda?</h6>
                    <p>Se eliminarán:</p>
                    <ul>
                        <li>✗ La tienda "<strong id="tiendaNombreEliminar"></strong>"</li>
                        <li>✗ Todas las reseñas y calificaciones de la tienda</li>
                        <li>✗ Todas las fotos de la galería</li>
                        <li>✗ Estadísticas y datos históricos</li>
                        <li>✗ La tienda desaparecerá completamente del sistema</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>💡 Alternativa:</strong> Si solo quieres ocultar temporalmente la tienda, 
                        usa el botón "Desactivar" en lugar de eliminar.
                    </div>
                    
                    <form id="formEliminarTienda" method="POST">
                        <input type="hidden" name="tienda_id" id="tiendaIdEliminar">
                        <input type="hidden" name="accion" value="eliminar">
                        
                        <div class="mb-3">
                            <label for="confirmacionEliminar" class="form-label">
                                <strong>Para confirmar, escribe exactamente:</strong> <code>ELIMINAR</code>
                            </label>
                            <input type="text" class="form-control" id="confirmacionEliminar" 
                                   placeholder="Escribe ELIMINAR para confirmar" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminar" disabled>
                        <i class="bi bi-trash3-fill"></i> Sí, Eliminar Permanentemente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script para manejar el modal de eliminar tienda
        document.addEventListener('DOMContentLoaded', function() {
            const modalEliminar = document.getElementById('modalEliminarTienda');
            const tiendaNombre = document.getElementById('tiendaNombreEliminar');
            const tiendaId = document.getElementById('tiendaIdEliminar');
            const confirmacionInput = document.getElementById('confirmacionEliminar');
            const btnConfirmar = document.getElementById('btnConfirmarEliminar');
            const formEliminar = document.getElementById('formEliminarTienda');
            
            // Cuando se abre el modal
            modalEliminar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const tiendaIdValue = button.getAttribute('data-tienda-id');
                const tiendaNombreValue = button.getAttribute('data-tienda-nombre');
                
                tiendaNombre.textContent = tiendaNombreValue;
                tiendaId.value = tiendaIdValue;
                
                // Limpiar campos
                confirmacionInput.value = '';
                btnConfirmar.disabled = true;
            });
            
            // Validar confirmación
            confirmacionInput.addEventListener('input', function() {
                const textoValido = confirmacionInput.value === 'ELIMINAR';
                btnConfirmar.disabled = !textoValido;
                
                if (textoValido) {
                    confirmacionInput.classList.remove('is-invalid');
                    confirmacionInput.classList.add('is-valid');
                } else {
                    confirmacionInput.classList.remove('is-valid');
                    if (confirmacionInput.value.length > 0) {
                        confirmacionInput.classList.add('is-invalid');
                    }
                }
            });
            
            // Confirmar eliminación
            btnConfirmar.addEventListener('click', function() {
                if (confirm('¿Estás ABSOLUTAMENTE seguro? Esta acción NO se puede deshacer.')) {
                    formEliminar.submit();
                }
            });
        });
    </script>
</body>
</html>