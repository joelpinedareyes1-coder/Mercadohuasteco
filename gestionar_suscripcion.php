<?php
require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Configuración de Mercado Pago
define('MP_ACCESS_TOKEN', 'TU_ACCESS_TOKEN_DE_MERCADO_PAGO');

$page_title = "Gestionar Suscripción Premium";
$mensaje = '';
$error = '';

// Obtener información del usuario
$usuario = obtenerInfoUsuario($pdo, $_SESSION['user_id']);

// Obtener suscripción activa
$suscripcion = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM suscripciones_premium WHERE usuario_id = ? AND status IN ('authorized', 'pending', 'paused') ORDER BY fecha_creacion DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al obtener suscripción: " . $e->getMessage());
}

// Obtener historial de pagos
$pagos = [];
if ($suscripcion) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM pagos_suscripcion WHERE suscripcion_id = ? ORDER BY fecha_pago DESC");
        $stmt->execute([$suscripcion['id']]);
        $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener pagos: " . $e->getMessage());
    }
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $suscripcion) {
    $action = $_POST['action'];
    $preapproval_id = $suscripcion['preapproval_id'];
    
    if ($action === 'cancelar') {
        // Cancelar suscripción en Mercado Pago
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/preapproval/$preapproval_id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['status' => 'cancelled']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MP_ACCESS_TOKEN
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            // Actualizar en la base de datos
            try {
                $stmt = $pdo->prepare("UPDATE suscripciones_premium SET status = 'cancelled', fecha_fin = NOW() WHERE id = ?");
                $stmt->execute([$suscripcion['id']]);
                $mensaje = "Suscripción cancelada exitosamente. Tu Premium seguirá activo hasta la fecha de expiración.";
                
                // Recargar suscripción
                $stmt = $pdo->prepare("SELECT * FROM suscripciones_premium WHERE id = ?");
                $stmt->execute([$suscripcion['id']]);
                $suscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $error = "Error al actualizar suscripción: " . $e->getMessage();
            }
        } else {
            $error = "Error al cancelar suscripción en Mercado Pago.";
        }
    }
}

// Incluir template del dashboard
include 'includes/vendor_dashboard_template.php';
?>

<!-- Contenido específico de la página -->
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <?php if ($mensaje): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Estado Premium -->
            <div class="card-modern mb-4">
                <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3 class="card-title" style="color: white;">
                        <i class="fas fa-crown"></i>
                        Estado de Membresía Premium
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Estado Actual:</h5>
                            <?php if (esPremiumActivo($usuario['fecha_expiracion_premium'])): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Premium Activo</strong>
                                </div>
                                <p><strong>Expira:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario['fecha_expiracion_premium'])); ?></p>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Premium Inactivo</strong>
                                </div>
                                <a href="crear_pago_mp.php" class="btn-modern">
                                    <i class="fas fa-crown"></i>
                                    Activar Premium
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5>Suscripción:</h5>
                            <?php if ($suscripcion): ?>
                                <p><strong>Estado:</strong> 
                                    <?php 
                                    $status_labels = [
                                        'authorized' => '<span class="badge bg-success">Activa</span>',
                                        'pending' => '<span class="badge bg-warning">Pendiente</span>',
                                        'paused' => '<span class="badge bg-info">Pausada</span>',
                                        'cancelled' => '<span class="badge bg-danger">Cancelada</span>'
                                    ];
                                    echo $status_labels[$suscripcion['status']] ?? $suscripcion['status'];
                                    ?>
                                </p>
                                <p><strong>Monto:</strong> $<?php echo number_format($suscripcion['monto'], 2); ?> <?php echo $suscripcion['moneda']; ?>/mes</p>
                                <p><strong>Fecha de inicio:</strong> <?php echo $suscripcion['fecha_inicio'] ? date('d/m/Y', strtotime($suscripcion['fecha_inicio'])) : 'Pendiente'; ?></p>
                            <?php else: ?>
                                <p class="text-muted">No tienes una suscripción activa</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones de Suscripción -->
            <?php if ($suscripcion && in_array($suscripcion['status'], ['authorized', 'pending'])): ?>
            <div class="card-modern mb-4">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i>
                        Gestionar Suscripción
                    </h3>
                </div>
                <div class="card-body">
                    <p>Puedes cancelar tu suscripción en cualquier momento. Tu Premium seguirá activo hasta la fecha de expiración actual.</p>
                    <form method="POST" onsubmit="return confirm('¿Estás seguro de que deseas cancelar tu suscripción? No se realizarán más cobros automáticos.');">
                        <input type="hidden" name="action" value="cancelar">
                        <button type="submit" class="btn-modern btn-danger">
                            <i class="fas fa-times-circle"></i>
                            Cancelar Suscripción
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Historial de Pagos -->
            <?php if (!empty($pagos)): ?>
            <div class="card-modern">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i>
                        Historial de Pagos
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>ID de Pago</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                                    <td><code><?php echo htmlspecialchars($pago['payment_id']); ?></code></td>
                                    <td>$<?php echo number_format($pago['monto'], 2); ?> <?php echo $pago['moneda']; ?></td>
                                    <td>
                                        <?php if ($pago['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?php echo ucfirst($pago['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
