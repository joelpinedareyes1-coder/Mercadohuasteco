<?php
/**
 * Script de Prueba: Activar Premium Manualmente
 * 
 * Este script te permite activar Premium para un usuario sin necesidad de Mercado Pago
 * Útil para pruebas y desarrollo
 * 
 * IMPORTANTE: Eliminar o proteger este archivo en producción
 */

require_once 'config.php';
require_once 'funciones_config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    die("Debes estar logueado para usar este script");
}

$mensaje = '';
$error = '';

// Procesar activación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activar_premium'])) {
    $user_id = $_SESSION['user_id'];
    $dias = isset($_POST['dias']) ? (int)$_POST['dias'] : 30;
    
    try {
        // Calcular fecha de expiración
        $fecha_expiracion = date('Y-m-d H:i:s', strtotime("+$dias days"));
        
        // Activar Premium
        $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, fecha_expiracion_premium = ?, fecha_ultimo_pago = NOW() WHERE id = ?");
        $stmt->execute([$fecha_expiracion, $user_id]);
        
        // Si es vendedor, activar su tienda también
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario && $usuario['rol'] === 'vendedor') {
            $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 1, fecha_expiracion_premium = ? WHERE vendedor_id = ?");
            $stmt->execute([$fecha_expiracion, $user_id]);
        }
        
        $mensaje = "✅ Premium activado exitosamente hasta: " . date('d/m/Y H:i', strtotime($fecha_expiracion));
        
    } catch (PDOException $e) {
        $error = "Error al activar Premium: " . $e->getMessage();
    }
}

// Procesar desactivación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_premium'])) {
    $user_id = $_SESSION['user_id'];
    
    try {
        // Desactivar Premium
        $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 0, fecha_expiracion_premium = NULL WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Desactivar tienda
        $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 0, fecha_expiracion_premium = NULL WHERE vendedor_id = ?");
        $stmt->execute([$user_id]);
        
        $mensaje = "✅ Premium desactivado exitosamente";
        
    } catch (PDOException $e) {
        $error = "Error al desactivar Premium: " . $e->getMessage();
    }
}

// Obtener información actual del usuario
$usuario_info = obtenerInfoUsuario($pdo, $_SESSION['user_id']);
$es_premium = $usuario_info && esPremiumActivo($usuario_info['fecha_expiracion_premium']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar Premium - Prueba</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .status-premium {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .status-no-premium {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-crown me-2"></i>
                            Activar Premium - Modo Prueba
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($mensaje): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $mensaje; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Estado Actual -->
                        <div class="<?php echo $es_premium ? 'status-premium' : 'status-no-premium'; ?>">
                            <h4 class="mb-3">
                                <?php if ($es_premium): ?>
                                    <i class="fas fa-check-circle me-2"></i>
                                    Estado: PREMIUM ACTIVO
                                <?php else: ?>
                                    <i class="fas fa-times-circle me-2"></i>
                                    Estado: NO PREMIUM
                                <?php endif; ?>
                            </h4>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Usuario ID:</strong> <?php echo $_SESSION['user_id']; ?><br>
                                    <strong>Nombre:</strong> <?php echo htmlspecialchars($usuario_info['nombre']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($usuario_info['email']); ?><br>
                                    <strong>Rol:</strong> <?php echo htmlspecialchars($usuario_info['rol']); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>es_premium:</strong> <?php echo $usuario_info['es_premium'] ? 'Sí (1)' : 'No (0)'; ?><br>
                                    <strong>Fecha Expiración:</strong> 
                                    <?php 
                                    if ($usuario_info['fecha_expiracion_premium']) {
                                        echo date('d/m/Y H:i', strtotime($usuario_info['fecha_expiracion_premium']));
                                        
                                        if ($es_premium) {
                                            $fecha_exp = new DateTime($usuario_info['fecha_expiracion_premium']);
                                            $fecha_actual = new DateTime();
                                            $dias_restantes = $fecha_actual->diff($fecha_exp)->days;
                                            echo "<br><strong>Días restantes:</strong> $dias_restantes días";
                                        }
                                    } else {
                                        echo 'No establecida';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulario de Activación -->
                        <?php if (!$es_premium): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-rocket me-2"></i>
                                Activar Premium
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Días de Premium:</label>
                                    <select name="dias" class="form-select">
                                        <option value="1">1 día (prueba rápida)</option>
                                        <option value="7">7 días (1 semana)</option>
                                        <option value="30" selected>30 días (1 mes)</option>
                                        <option value="90">90 días (3 meses)</option>
                                        <option value="365">365 días (1 año)</option>
                                    </select>
                                </div>
                                <button type="submit" name="activar_premium" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-crown me-2"></i>
                                    Activar Premium Ahora
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-times-circle me-2"></i>
                                Desactivar Premium
                            </h5>
                            <form method="POST" onsubmit="return confirm('¿Estás seguro de desactivar Premium?')">
                                <button type="submit" name="desactivar_premium" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-times me-2"></i>
                                    Desactivar Premium
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Botones de Navegación -->
                        <div class="d-grid gap-2">
                            <a href="panel_vendedor.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Panel del Vendedor
                            </a>
                            <a href="gestionar_suscripcion.php" class="btn btn-info">
                                <i class="fas fa-cog me-2"></i>
                                Ver Gestionar Suscripción
                            </a>
                        </div>
                        
                        <!-- Información de Debug -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2">
                                <i class="fas fa-bug me-2"></i>
                                Información de Debug
                            </h6>
                            <small class="text-muted">
                                <strong>Función esPremiumActivo():</strong> 
                                <?php echo $es_premium ? 'true' : 'false'; ?><br>
                                
                                <strong>Fecha actual:</strong> 
                                <?php echo date('Y-m-d H:i:s'); ?><br>
                                
                                <strong>Fecha expiración (raw):</strong> 
                                <?php echo $usuario_info['fecha_expiracion_premium'] ?? 'NULL'; ?><br>
                                
                                <strong>Comparación:</strong>
                                <?php 
                                if ($usuario_info['fecha_expiracion_premium']) {
                                    $exp = strtotime($usuario_info['fecha_expiracion_premium']);
                                    $now = time();
                                    echo $exp > $now ? 'Fecha futura (Premium activo)' : 'Fecha pasada (Premium expirado)';
                                } else {
                                    echo 'Sin fecha (No Premium)';
                                }
                                ?>
                            </small>
                        </div>
                        
                        <!-- Advertencia -->
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Advertencia:</strong> Este es un script de prueba. 
                            En producción, el Premium se activa automáticamente mediante el webhook de Mercado Pago.
                            <strong>Elimina o protege este archivo antes de lanzar a producción.</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
