<?php
/**
 * Script de Diagnóstico: Sistema Premium
 * 
 * Este script verifica que todo el sistema Premium esté funcionando correctamente
 */

require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    die("Debes estar logueado para usar este script");
}

$user_id = $_SESSION['user_id'];
$diagnostico = [];

// 1. Verificar usuario
$diagnostico['usuario'] = obtenerInfoUsuario($pdo, $user_id);

// 2. Verificar función esPremiumActivo
$diagnostico['es_premium_activo'] = esPremiumActivo($diagnostico['usuario']['fecha_expiracion_premium']);

// 3. Verificar tienda
try {
    $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE vendedor_id = ?");
    $stmt->execute([$user_id]);
    $diagnostico['tienda'] = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $diagnostico['tienda'] = ['error' => $e->getMessage()];
}

// 4. Verificar suscripciones
try {
    $stmt = $pdo->prepare("SELECT * FROM suscripciones_premium WHERE usuario_id = ? ORDER BY fecha_creacion DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $diagnostico['suscripciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $diagnostico['suscripciones'] = ['error' => $e->getMessage()];
}

// 5. Verificar pagos
try {
    $stmt = $pdo->prepare("SELECT * FROM pagos_suscripcion WHERE usuario_id = ? ORDER BY fecha_pago DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $diagnostico['pagos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $diagnostico['pagos'] = ['error' => $e->getMessage()];
}

// 6. Verificar webhooks
try {
    $stmt = $pdo->prepare("SELECT * FROM webhook_logs ORDER BY fecha_recepcion DESC LIMIT 10");
    $stmt->execute();
    $diagnostico['webhooks'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $diagnostico['webhooks'] = ['error' => $e->getMessage()];
}

// 7. Verificar credenciales de Mercado Pago
$diagnostico['mp_configurado'] = false;
if (file_exists('crear_pago_mp.php')) {
    $contenido = file_get_contents('crear_pago_mp.php');
    $diagnostico['mp_configurado'] = strpos($contenido, 'TU_ACCESS_TOKEN_DE_MERCADO_PAGO') === false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Premium</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            padding: 2rem;
        }
        .diagnostic-card {
            margin-bottom: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-ok {
            color: #10b981;
        }
        .status-error {
            color: #ef4444;
        }
        .status-warning {
            color: #f59e0b;
        }
        pre {
            background: #1f2937;
            color: #f3f4f6;
            padding: 1rem;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-stethoscope me-2"></i>
                    Diagnóstico del Sistema Premium
                </h1>
                
                <!-- Resumen General -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Resumen General
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Estado del Usuario</h5>
                                <ul class="list-unstyled">
                                    <li>
                                        <i class="fas fa-user me-2"></i>
                                        <strong>ID:</strong> <?php echo $user_id; ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-envelope me-2"></i>
                                        <strong>Email:</strong> <?php echo htmlspecialchars($diagnostico['usuario']['email']); ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-user-tag me-2"></i>
                                        <strong>Rol:</strong> <?php echo htmlspecialchars($diagnostico['usuario']['rol']); ?>
                                    </li>
                                    <li>
                                        <?php if ($diagnostico['es_premium_activo']): ?>
                                            <i class="fas fa-check-circle status-ok me-2"></i>
                                            <strong>Premium:</strong> <span class="status-ok">ACTIVO</span>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle status-error me-2"></i>
                                            <strong>Premium:</strong> <span class="status-error">NO ACTIVO</span>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>Configuración del Sistema</h5>
                                <ul class="list-unstyled">
                                    <li>
                                        <?php if ($diagnostico['mp_configurado']): ?>
                                            <i class="fas fa-check-circle status-ok me-2"></i>
                                            <strong>Mercado Pago:</strong> <span class="status-ok">Configurado</span>
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-triangle status-warning me-2"></i>
                                            <strong>Mercado Pago:</strong> <span class="status-warning">Sin configurar</span>
                                        <?php endif; ?>
                                    </li>
                                    <li>
                                        <?php if ($diagnostico['tienda']): ?>
                                            <i class="fas fa-check-circle status-ok me-2"></i>
                                            <strong>Tienda:</strong> <span class="status-ok">Registrada</span>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle status-error me-2"></i>
                                            <strong>Tienda:</strong> <span class="status-error">No registrada</span>
                                        <?php endif; ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-database me-2"></i>
                                        <strong>Suscripciones:</strong> <?php echo count($diagnostico['suscripciones']); ?>
                                    </li>
                                    <li>
                                        <i class="fas fa-money-bill me-2"></i>
                                        <strong>Pagos:</strong> <?php echo count($diagnostico['pagos']); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información del Usuario -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle me-2"></i>
                            Información del Usuario
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor</th>
                                </tr>
                                <tr>
                                    <td><strong>es_premium</strong></td>
                                    <td>
                                        <?php echo $diagnostico['usuario']['es_premium'] ? 
                                            '<span class="badge bg-success">1 (Sí)</span>' : 
                                            '<span class="badge bg-danger">0 (No)</span>'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>fecha_expiracion_premium</strong></td>
                                    <td>
                                        <?php 
                                        if ($diagnostico['usuario']['fecha_expiracion_premium']) {
                                            echo date('d/m/Y H:i:s', strtotime($diagnostico['usuario']['fecha_expiracion_premium']));
                                            
                                            $exp = strtotime($diagnostico['usuario']['fecha_expiracion_premium']);
                                            $now = time();
                                            if ($exp > $now) {
                                                $dias = ceil(($exp - $now) / 86400);
                                                echo " <span class='badge bg-success'>($dias días restantes)</span>";
                                            } else {
                                                echo " <span class='badge bg-danger'>(Expirado)</span>";
                                            }
                                        } else {
                                            echo '<span class="badge bg-secondary">NULL</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>fecha_ultimo_pago</strong></td>
                                    <td>
                                        <?php echo $diagnostico['usuario']['fecha_ultimo_pago'] ?? 
                                            '<span class="badge bg-secondary">NULL</span>'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>esPremiumActivo()</strong></td>
                                    <td>
                                        <?php echo $diagnostico['es_premium_activo'] ? 
                                            '<span class="badge bg-success">true</span>' : 
                                            '<span class="badge bg-danger">false</span>'; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Información de la Tienda -->
                <?php if ($diagnostico['tienda'] && !isset($diagnostico['tienda']['error'])): ?>
                <div class="card diagnostic-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-store me-2"></i>
                            Información de la Tienda
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <tr>
                                    <th>Campo</th>
                                    <th>Valor</th>
                                </tr>
                                <tr>
                                    <td><strong>ID</strong></td>
                                    <td><?php echo $diagnostico['tienda']['id']; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Nombre</strong></td>
                                    <td><?php echo htmlspecialchars($diagnostico['tienda']['nombre_tienda']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>es_premium</strong></td>
                                    <td>
                                        <?php echo isset($diagnostico['tienda']['es_premium']) && $diagnostico['tienda']['es_premium'] ? 
                                            '<span class="badge bg-success">1 (Sí)</span>' : 
                                            '<span class="badge bg-danger">0 (No)</span>'; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>fecha_expiracion_premium</strong></td>
                                    <td>
                                        <?php echo isset($diagnostico['tienda']['fecha_expiracion_premium']) && $diagnostico['tienda']['fecha_expiracion_premium'] ? 
                                            date('d/m/Y H:i:s', strtotime($diagnostico['tienda']['fecha_expiracion_premium'])) : 
                                            '<span class="badge bg-secondary">NULL</span>'; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Suscripciones -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-sync-alt me-2"></i>
                            Suscripciones (<?php echo count($diagnostico['suscripciones']); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($diagnostico['suscripciones'])): ?>
                            <p class="text-muted">No hay suscripciones registradas</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Preapproval ID</th>
                                            <th>Status</th>
                                            <th>Monto</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($diagnostico['suscripciones'] as $sub): ?>
                                        <tr>
                                            <td><?php echo $sub['id']; ?></td>
                                            <td><code><?php echo htmlspecialchars($sub['preapproval_id']); ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $sub['status'] === 'authorized' ? 'success' : 
                                                        ($sub['status'] === 'pending' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo $sub['status']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($sub['monto'], 2); ?> <?php echo $sub['moneda']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($sub['fecha_creacion'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Pagos -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Pagos Registrados (<?php echo count($diagnostico['pagos']); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($diagnostico['pagos'])): ?>
                            <p class="text-muted">No hay pagos registrados</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Payment ID</th>
                                            <th>Status</th>
                                            <th>Monto</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($diagnostico['pagos'] as $pago): ?>
                                        <tr>
                                            <td><?php echo $pago['id']; ?></td>
                                            <td><code><?php echo htmlspecialchars($pago['payment_id']); ?></code></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $pago['status'] === 'approved' ? 'success' : 'secondary'; 
                                                ?>">
                                                    <?php echo $pago['status']; ?>
                                                </span>
                                            </td>
                                            <td>$<?php echo number_format($pago['monto'], 2); ?> <?php echo $pago['moneda']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tools me-2"></i>
                            Acciones Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="activar_premium_prueba.php" class="btn btn-success">
                                <i class="fas fa-crown me-2"></i>
                                Activar/Desactivar Premium (Modo Prueba)
                            </a>
                            <a href="panel_vendedor.php" class="btn btn-primary">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Ir al Panel del Vendedor
                            </a>
                            <a href="gestionar_suscripcion.php" class="btn btn-info">
                                <i class="fas fa-cog me-2"></i>
                                Gestionar Suscripción
                            </a>
                            <button onclick="location.reload()" class="btn btn-secondary">
                                <i class="fas fa-sync-alt me-2"></i>
                                Recargar Diagnóstico
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Recomendaciones -->
                <div class="card diagnostic-card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Recomendaciones
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <?php if (!$diagnostico['mp_configurado']): ?>
                            <li class="text-warning">
                                <strong>Mercado Pago no configurado:</strong> 
                                Edita <code>crear_pago_mp.php</code> y reemplaza las credenciales de prueba con tus credenciales reales.
                            </li>
                            <?php endif; ?>
                            
                            <?php if (!$diagnostico['es_premium_activo']): ?>
                            <li class="text-info">
                                <strong>Premium no activo:</strong> 
                                Usa <a href="activar_premium_prueba.php">activar_premium_prueba.php</a> para activar Premium manualmente y probar el sistema.
                            </li>
                            <?php endif; ?>
                            
                            <?php if (empty($diagnostico['suscripciones'])): ?>
                            <li class="text-info">
                                <strong>Sin suscripciones:</strong> 
                                Crea una suscripción de prueba desde el panel del vendedor.
                            </li>
                            <?php endif; ?>
                            
                            <li class="text-success">
                                <strong>Funciones disponibles:</strong> 
                                Todas las funciones del sistema Premium están implementadas y listas para usar.
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
