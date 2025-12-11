<?php
/**
 * Versión Simplificada: Activar Premium sin Login
 * Útil para pruebas cuando hay problemas de sesión
 */

// Activar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'funciones_config.php';

$mensaje = '';
$error = '';
$user_id = null;

// Si está logueado, usar su ID
if (esta_logueado()) {
    $user_id = $_SESSION['user_id'];
} 

// Permitir especificar user_id manualmente para pruebas
if (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
}

// Procesar activación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activar_premium'])) {
    $user_id_post = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $user_id;
    $dias = isset($_POST['dias']) ? (int)$_POST['dias'] : 30;
    
    if (!$user_id_post) {
        $error = "Debes especificar un user_id";
    } else {
        try {
            // Calcular fecha de expiración
            $fecha_expiracion = date('Y-m-d H:i:s', strtotime("+$dias days"));
            
            // Activar Premium
            $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 1, fecha_expiracion_premium = ?, fecha_ultimo_pago = NOW() WHERE id = ?");
            $stmt->execute([$fecha_expiracion, $user_id_post]);
            
            if ($stmt->rowCount() > 0) {
                // Si es vendedor, activar su tienda también
                $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                $stmt->execute([$user_id_post]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario && $usuario['rol'] === 'vendedor') {
                    $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 1, fecha_expiracion_premium = ? WHERE vendedor_id = ?");
                    $stmt->execute([$fecha_expiracion, $user_id_post]);
                }
                
                $mensaje = "✅ Premium activado exitosamente para usuario ID $user_id_post hasta: " . date('d/m/Y H:i', strtotime($fecha_expiracion));
            } else {
                $error = "No se encontró el usuario con ID $user_id_post";
            }
            
        } catch (PDOException $e) {
            $error = "Error al activar Premium: " . $e->getMessage();
        }
    }
}

// Procesar desactivación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['desactivar_premium'])) {
    $user_id_post = isset($_POST['user_id']) ? (int)$_POST['user_id'] : $user_id;
    
    if (!$user_id_post) {
        $error = "Debes especificar un user_id";
    } else {
        try {
            // Desactivar Premium
            $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = 0, fecha_expiracion_premium = NULL WHERE id = ?");
            $stmt->execute([$user_id_post]);
            
            // Desactivar tienda
            $stmt = $pdo->prepare("UPDATE tiendas SET es_premium = 0, fecha_expiracion_premium = NULL WHERE vendedor_id = ?");
            $stmt->execute([$user_id_post]);
            
            $mensaje = "✅ Premium desactivado exitosamente para usuario ID $user_id_post";
            
        } catch (PDOException $e) {
            $error = "Error al desactivar Premium: " . $e->getMessage();
        }
    }
}

// Obtener información del usuario si hay ID
$usuario_info = null;
$es_premium = false;
if ($user_id) {
    $usuario_info = obtenerInfoUsuario($pdo, $user_id);
    if ($usuario_info) {
        $es_premium = esPremiumActivo($usuario_info['fecha_expiracion_premium']);
    }
}

// Obtener lista de usuarios vendedores
try {
    $stmt = $pdo->query("SELECT id, nombre, email, rol, es_premium, fecha_expiracion_premium FROM usuarios WHERE rol = 'vendedor' ORDER BY id DESC LIMIT 20");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $usuarios = [];
    $error = "Error al obtener usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activar Premium - Simple</title>
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
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .status-no-premium {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-crown me-2"></i>
                            Activar Premium - Versión Simple
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
                        
                        <!-- Información del Usuario Actual -->
                        <?php if ($usuario_info): ?>
                        <div class="<?php echo $es_premium ? 'status-premium' : 'status-no-premium'; ?>">
                            <h5>
                                <?php if ($es_premium): ?>
                                    <i class="fas fa-check-circle me-2"></i>
                                    Usuario Actual: PREMIUM ACTIVO
                                <?php else: ?>
                                    <i class="fas fa-times-circle me-2"></i>
                                    Usuario Actual: NO PREMIUM
                                <?php endif; ?>
                            </h5>
                            <p class="mb-0">
                                <strong>ID:</strong> <?php echo $user_id; ?> | 
                                <strong>Nombre:</strong> <?php echo htmlspecialchars($usuario_info['nombre']); ?> | 
                                <strong>Email:</strong> <?php echo htmlspecialchars($usuario_info['email']); ?>
                                <?php if ($usuario_info['fecha_expiracion_premium']): ?>
                                    <br><strong>Expira:</strong> <?php echo date('d/m/Y H:i', strtotime($usuario_info['fecha_expiracion_premium'])); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Formulario de Activación -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">
                                    <i class="fas fa-rocket me-2"></i>
                                    Activar Premium
                                </h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">User ID:</label>
                                        <input type="number" name="user_id" class="form-control" value="<?php echo $user_id ?? ''; ?>" required>
                                        <small class="text-muted">ID del usuario a activar Premium</small>
                                    </div>
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
                                    <button type="submit" name="activar_premium" class="btn btn-success w-100">
                                        <i class="fas fa-crown me-2"></i>
                                        Activar Premium
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="mb-3">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Desactivar Premium
                                </h5>
                                <form method="POST" onsubmit="return confirm('¿Estás seguro de desactivar Premium?')">
                                    <div class="mb-3">
                                        <label class="form-label">User ID:</label>
                                        <input type="number" name="user_id" class="form-control" value="<?php echo $user_id ?? ''; ?>" required>
                                        <small class="text-muted">ID del usuario a desactivar Premium</small>
                                    </div>
                                    <button type="submit" name="desactivar_premium" class="btn btn-danger w-100 mt-4">
                                        <i class="fas fa-times me-2"></i>
                                        Desactivar Premium
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Lista de Usuarios Vendedores -->
                        <h5 class="mb-3">
                            <i class="fas fa-users me-2"></i>
                            Usuarios Vendedores (<?php echo count($usuarios); ?>)
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Premium</th>
                                        <th>Expira</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <?php if ($u['es_premium']): ?>
                                                <span class="badge bg-success">Sí</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($u['fecha_expiracion_premium']) {
                                                echo date('d/m/Y', strtotime($u['fecha_expiracion_premium']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="?user_id=<?php echo $u['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Botones de Navegación -->
                        <div class="d-grid gap-2 mt-4">
                            <a href="panel_vendedor.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Panel del Vendedor
                            </a>
                            <a href="test_error.php" class="btn btn-info">
                                <i class="fas fa-bug me-2"></i>
                                Probar Errores PHP
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
