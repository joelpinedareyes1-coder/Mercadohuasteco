<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$mensaje = '';
$error = '';

// Procesar acciones de gestión de usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && isset($_POST['usuario_id'])) {
        $usuario_id = (int)$_POST['usuario_id'];
        $accion = $_POST['accion'];
        
        // Prevenir que el admin se modifique a sí mismo
        if ($usuario_id === $_SESSION['user_id']) {
            $error = "No puedes modificar tu propio usuario.";
        } else {
            try {
                if ($accion === 'cambiar_rol') {
                    $nuevo_rol = $_POST['nuevo_rol'];
                    if (in_array($nuevo_rol, ['cliente', 'vendedor', 'admin'])) {
                        $stmt = $pdo->prepare("UPDATE usuarios SET rol = ? WHERE id = ?");
                        $stmt->execute([$nuevo_rol, $usuario_id]);
                        $mensaje = "Rol de usuario actualizado exitosamente.";
                    } else {
                        $error = "Rol no válido.";
                    }
                } elseif ($accion === 'activar') {
                    $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $mensaje = "Usuario activado exitosamente.";
                } elseif ($accion === 'desactivar') {
                    // Usar las funciones que ya creamos
                    desactivarCuenta($usuario_id, $pdo);
                    $mensaje = "Usuario desactivado exitosamente.";
                } elseif ($accion === 'reactivar') {
                    // Usar las funciones que ya creamos
                    reactivarCuenta($usuario_id, $pdo);
                    $mensaje = "Usuario reactivado exitosamente.";
                } elseif ($accion === 'eliminar') {
                    // Eliminar usuario permanentemente
                    $pdo->beginTransaction();
                    
                    try {
                        // Obtener información del usuario
                        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                        $stmt->execute([$usuario_id]);
                        $usuario_info = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $tienda_eliminada = false;
                        $reseñas_tienda_eliminadas = 0;
                        
                        // Si es vendedor, eliminar primero su tienda y datos relacionados
                        if ($usuario_info['rol'] === 'vendedor') {
                            // 1. Obtener ID de la tienda
                            $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE vendedor_id = ?");
                            $stmt->execute([$usuario_id]);
                            $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($tienda) {
                                $tienda_id = $tienda['id'];
                                
                                // 2. Eliminar reseñas de la tienda
                                $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE tienda_id = ?");
                                $stmt->execute([$tienda_id]);
                                $reseñas_tienda_eliminadas = $stmt->rowCount();
                                
                                // 3. Eliminar fotos de la galería
                                $stmt = $pdo->prepare("DELETE FROM galeria_tiendas WHERE tienda_id = ?");
                                $stmt->execute([$tienda_id]);
                                
                                // 4. Eliminar la tienda
                                $stmt = $pdo->prepare("DELETE FROM tiendas WHERE id = ?");
                                $stmt->execute([$tienda_id]);
                                $tienda_eliminada = true;
                            }
                        }
                        
                        // 5. Eliminar todas las reseñas hechas por el usuario
                        $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE user_id = ?");
                        $stmt->execute([$usuario_id]);
                        $reseñas_usuario_eliminadas = $stmt->rowCount();
                        
                        // 6. Eliminar el usuario permanentemente
                        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                        $stmt->execute([$usuario_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            $pdo->commit();
                            $mensaje = "Usuario eliminado permanentemente.";
                            if ($tienda_eliminada) {
                                $mensaje .= " Se eliminó su tienda y $reseñas_tienda_eliminadas reseñas de la tienda.";
                            }
                            if ($reseñas_usuario_eliminadas > 0) {
                                $mensaje .= " Se eliminaron $reseñas_usuario_eliminadas reseñas escritas por el usuario.";
                            }
                        } else {
                            throw new Exception("No se pudo eliminar el usuario.");
                        }
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        throw $e;
                    }
                }
            } catch(PDOException $e) {
                $error = "Error al actualizar el usuario: " . $e->getMessage();
            }
        }
    }
}

// Mensajes de Premium
if (isset($_SESSION['mensaje_premium'])) {
    $mensaje = $_SESSION['mensaje_premium'];
    unset($_SESSION['mensaje_premium']);
}
if (isset($_SESSION['error_premium'])) {
    $error = $_SESSION['error_premium'];
    unset($_SESSION['error_premium']);
}

// Obtener filtro de rol
$filtro_rol = isset($_GET['rol']) ? $_GET['rol'] : 'todos';

// Obtener todos los usuarios con estadísticas
try {
    $sql = "
        SELECT u.*, 
               (SELECT COUNT(*) FROM tiendas t WHERE t.vendedor_id = u.id AND t.activo = 1) as total_tiendas,
               (SELECT COUNT(*) FROM calificaciones c WHERE c.user_id = u.id AND c.activo = 1) as total_reseñas
        FROM usuarios u
        WHERE 1=1
    ";
    
    // Agregar filtro por rol si se especifica
    if ($filtro_rol !== 'todos') {
        $sql .= " AND u.rol = ?";
        $params = [$filtro_rol];
    } else {
        $params = [];
    }
    
    $sql .= " ORDER BY u.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estadísticas generales
    $stmt_stats = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN rol = 'cliente' THEN 1 ELSE 0 END) as clientes,
            SUM(CASE WHEN rol = 'vendedor' THEN 1 ELSE 0 END) as vendedores,
            SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) as admins,
            SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos,
            SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) as inactivos,
            SUM(CASE WHEN es_premium = 1 THEN 1 ELSE 0 END) as premium
        FROM usuarios
    ");
    $stmt_stats->execute();
    $estadisticas = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $usuarios = [];
    $estadisticas = ['total' => 0, 'clientes' => 0, 'vendedores' => 0, 'admins' => 0, 'activos' => 0, 'inactivos' => 0, 'premium' => 0];
    $error = "Error al cargar los usuarios: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Panel Admin</title>
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
        
        .usuario-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .usuario-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .usuario-admin {
            border-left: 5px solid #dc3545;
            background: linear-gradient(90deg, #f8d7da, #ffffff);
        }
        
        .usuario-vendedor {
            border-left: 5px solid #28a745;
            background: linear-gradient(90deg, #d4edda, #ffffff);
        }
        
        .usuario-cliente {
            border-left: 5px solid #007bff;
            background: linear-gradient(90deg, #d1ecf1, #ffffff);
        }
        
        .usuario-inactivo {
            opacity: 0.6;
            background: #f8f9fa;
        }
        
        .badge-admin {
            background: linear-gradient(45deg, #dc3545, #fd7e14);
            color: white;
        }
        
        .badge-vendedor {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        
        .badge-cliente {
            background: linear-gradient(45deg, #007bff, #6f42c1);
            color: white;
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
        
        .filtros-usuarios {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .avatar-usuario {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #6f42c1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .badge-premium {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #000;
            font-weight: bold;
            padding: 0.35rem 0.65rem;
            border: 2px solid #ffa500;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.4);
        }
        
        .btn-premium {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #000;
            border: 2px solid #ffa500;
            font-weight: bold;
        }
        
        .btn-premium:hover {
            background: linear-gradient(45deg, #ffed4e, #ffd700);
            color: #000;
            border-color: #ff8c00;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.5);
        }
        
        .btn-remove-premium {
            background: #6c757d;
            color: white;
            border: 2px solid #5a6268;
        }
        
        .btn-remove-premium:hover {
            background: #5a6268;
            color: white;
            border-color: #545b62;
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
                        <i class="bi bi-people"></i> Gestionar Usuarios
                    </h1>
                    <p class="mb-0 opacity-75">Administración completa de usuarios del sistema</p>
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
                <div class="col-md-2">
                    <h4 class="text-primary"><?php echo $estadisticas['total']; ?></h4>
                    <p class="text-muted mb-0">Total Usuarios</p>
                </div>
                <div class="col-md-2">
                    <h4 class="text-info"><?php echo $estadisticas['clientes']; ?></h4>
                    <p class="text-muted mb-0">Clientes</p>
                </div>
                <div class="col-md-2">
                    <h4 class="text-success"><?php echo $estadisticas['vendedores']; ?></h4>
                    <p class="text-muted mb-0">Vendedores</p>
                </div>
                <div class="col-md-2">
                    <h4 class="text-danger"><?php echo $estadisticas['admins']; ?></h4>
                    <p class="text-muted mb-0">Admins</p>
                </div>
                <div class="col-md-2">
                    <h4 class="text-success"><?php echo $estadisticas['activos']; ?></h4>
                    <p class="text-muted mb-0">Activos</p>
                </div>
                <div class="col-md-2">
                    <h4 style="color: #ffd700; text-shadow: 0 0 10px rgba(255,215,0,0.3);">
                        <i class="bi bi-star-fill"></i> <?php echo $estadisticas['premium']; ?>
                    </h4>
                    <p class="text-muted mb-0">Premium</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="admin-card">
            <div class="filtros-usuarios">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-funnel"></i> Filtrar por Rol
                        </h5>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <a href="gestionar_usuarios.php?rol=todos" 
                               class="btn <?php echo $filtro_rol === 'todos' ? 'btn-primary' : 'btn-outline-primary'; ?> btn-sm">
                                <i class="bi bi-people"></i> Todos
                            </a>
                            <a href="gestionar_usuarios.php?rol=cliente" 
                               class="btn <?php echo $filtro_rol === 'cliente' ? 'btn-info' : 'btn-outline-info'; ?> btn-sm">
                                <i class="bi bi-person"></i> Clientes
                            </a>
                            <a href="gestionar_usuarios.php?rol=vendedor" 
                               class="btn <?php echo $filtro_rol === 'vendedor' ? 'btn-success' : 'btn-outline-success'; ?> btn-sm">
                                <i class="bi bi-shop"></i> Vendedores
                            </a>
                            <a href="gestionar_usuarios.php?rol=admin" 
                               class="btn <?php echo $filtro_rol === 'admin' ? 'btn-danger' : 'btn-outline-danger'; ?> btn-sm">
                                <i class="bi bi-shield"></i> Admins
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de usuarios -->
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="mb-0">
                    <i class="bi bi-list"></i> 
                    Lista de Usuarios
                    <?php if ($filtro_rol !== 'todos'): ?>
                        <span class="badge bg-secondary"><?php echo ucfirst($filtro_rol); ?>s</span>
                    <?php endif; ?>
                </h3>
                <small class="text-muted">
                    Mostrando <?php echo count($usuarios); ?> usuarios
                </small>
            </div>
            
            <?php if (empty($usuarios)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-people" style="font-size: 4rem; color: #dee2e6;"></i>
                    <h4 class="mt-3 text-muted">No hay usuarios para mostrar</h4>
                    <p class="text-muted">
                        <?php if ($filtro_rol === 'todos'): ?>
                            No hay usuarios registrados en el sistema
                        <?php else: ?>
                            No hay usuarios con rol de <?php echo $filtro_rol; ?>
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="usuario-card usuario-<?php echo $usuario['rol']; ?> <?php echo !$usuario['activo'] ? 'usuario-inactivo' : ''; ?>">
                        <div class="row align-items-center">
                            <!-- Avatar y info básica -->
                            <div class="col-md-1">
                                <div class="avatar-usuario">
                                    <?php echo strtoupper(substr($usuario['nombre'], 0, 2)); ?>
                                </div>
                            </div>
                            
                            <!-- Información del usuario -->
                            <div class="col-md-4">
                                <div class="d-flex align-items-center mb-2">
                                    <h6 class="mb-0 me-2"><?php echo htmlspecialchars($usuario['nombre']); ?></h6>
                                    
                                    <?php if ($usuario['rol'] === 'admin'): ?>
                                        <span class="badge badge-admin">
                                            <i class="bi bi-shield"></i> ADMIN
                                        </span>
                                    <?php elseif ($usuario['rol'] === 'vendedor'): ?>
                                        <span class="badge badge-vendedor">
                                            <i class="bi bi-shop"></i> VENDEDOR
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-cliente">
                                            <i class="bi bi-person"></i> CLIENTE
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($usuario['es_premium']) && $usuario['es_premium']): ?>
                                        <span class="badge badge-premium ms-1">
                                            <i class="bi bi-star-fill"></i> PREMIUM
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!$usuario['activo']): ?>
                                        <span class="badge bg-secondary ms-1">INACTIVO</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($usuario['id'] === $_SESSION['user_id']): ?>
                                        <span class="badge bg-warning text-dark ms-1">TÚ</span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-muted mb-1">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($usuario['email']); ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-calendar"></i> 
                                    Registrado: <?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?>
                                </p>
                            </div>
                            
                            <!-- Estadísticas del usuario -->
                            <div class="col-md-3">
                                <?php if ($usuario['rol'] === 'vendedor'): ?>
                                    <div class="stats-mini">
                                        <i class="bi bi-shop"></i> <?php echo $usuario['total_tiendas']; ?> tiendas
                                    </div>
                                <?php endif; ?>
                                <div class="stats-mini">
                                    <i class="bi bi-chat"></i> <?php echo $usuario['total_reseñas']; ?> reseñas escritas
                                </div>
                                <div class="stats-mini">
                                    <i class="bi bi-clock"></i> 
                                    <?php 
                                    $dias = floor((time() - strtotime($usuario['fecha_registro'])) / (60*60*24));
                                    echo $dias; ?> días en el sistema
                                </div>
                            </div>
                            
                            <!-- Acciones -->
                            <div class="col-md-4">
                                <?php if ($usuario['id'] !== $_SESSION['user_id']): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <!-- Botón Premium/Quitar Premium -->
                                        <?php if (isset($usuario['es_premium']) && $usuario['es_premium']): ?>
                                            <form method="POST" action="procesar_premium.php<?php echo isset($_GET['rol']) ? '?rol=' . $_GET['rol'] : ''; ?>" style="display: inline;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="es_premium" value="0">
                                                <button type="submit" class="btn btn-remove-premium btn-admin" 
                                                        onclick="return confirm('¿Remover membresía Premium de este usuario?')">
                                                    <i class="bi bi-star"></i> Quitar Premium
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="procesar_premium.php<?php echo isset($_GET['rol']) ? '?rol=' . $_GET['rol'] : ''; ?>" style="display: inline;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="es_premium" value="1">
                                                <button type="submit" class="btn btn-premium btn-admin" 
                                                        onclick="return confirm('¿Ascender a este usuario a Premium? Tendrá beneficios adicionales.')">
                                                    <i class="bi bi-star-fill"></i> Hacer Premium
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Cambiar rol -->
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-admin dropdown-toggle" 
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-arrow-up-right-circle"></i> Cambiar Rol
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php if ($usuario['rol'] !== 'cliente'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="accion" value="cambiar_rol">
                                                            <input type="hidden" name="nuevo_rol" value="cliente">
                                                            <button type="submit" class="dropdown-item" 
                                                                    onclick="return confirm('¿Cambiar a Cliente?')">
                                                                <i class="bi bi-person"></i> Cliente
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($usuario['rol'] !== 'vendedor'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="accion" value="cambiar_rol">
                                                            <input type="hidden" name="nuevo_rol" value="vendedor">
                                                            <button type="submit" class="dropdown-item" 
                                                                    onclick="return confirm('¿Cambiar a Vendedor?')">
                                                                <i class="bi bi-shop"></i> Vendedor
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php if ($usuario['rol'] !== 'admin'): ?>
                                                    <li>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                            <input type="hidden" name="accion" value="cambiar_rol">
                                                            <input type="hidden" name="nuevo_rol" value="admin">
                                                            <button type="submit" class="dropdown-item" 
                                                                    onclick="return confirm('¿PROMOVER A ADMINISTRADOR? Esta acción le dará acceso completo al sistema.')">
                                                                <i class="bi bi-shield"></i> Admin
                                                            </button>
                                                        </form>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Activar/Desactivar -->
                                        <?php if ($usuario['activo']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="accion" value="desactivar">
                                                <button type="submit" class="btn btn-warning btn-admin" 
                                                        onclick="return confirm('¿Desactivar este usuario? Su cuenta se pausará temporalmente.')">
                                                    <i class="bi bi-pause-circle"></i> Desactivar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <input type="hidden" name="accion" value="reactivar">
                                                <button type="submit" class="btn btn-success btn-admin">
                                                    <i class="bi bi-check-circle"></i> Reactivar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Eliminar cuenta permanentemente -->
                                        <button type="button" class="btn btn-danger btn-admin" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalEliminarUsuario"
                                                data-usuario-id="<?php echo $usuario['id']; ?>"
                                                data-usuario-nombre="<?php echo htmlspecialchars($usuario['nombre']); ?>"
                                                data-usuario-email="<?php echo htmlspecialchars($usuario['email']); ?>"
                                                data-usuario-rol="<?php echo $usuario['rol']; ?>">
                                            <i class="bi bi-trash3"></i> Eliminar
                                        </button>
                                        
                                        <!-- Ver perfil (si es vendedor) -->
                                        <?php if ($usuario['rol'] === 'vendedor' && $usuario['total_tiendas'] > 0): ?>
                                            <a href="directorio.php" target="_blank" class="btn btn-outline-info btn-admin">
                                                <i class="bi bi-eye"></i> Ver Tiendas
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center">
                                        <span class="badge bg-warning text-dark fs-6">
                                            <i class="bi bi-person-check"></i> Tu cuenta
                                        </span>
                                        <p class="text-muted small mt-1">No puedes modificar tu propio usuario</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Eliminar Usuario -->
    <div class="modal fade" id="modalEliminarUsuario" tabindex="-1" aria-labelledby="modalEliminarUsuarioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEliminarUsuarioLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Eliminar Usuario Permanentemente
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6><i class="bi bi-exclamation-triangle-fill"></i> ¡ADVERTENCIA!</h6>
                        <p><strong>Esta acción NO se puede deshacer.</strong> El usuario y toda su información será eliminada permanentemente.</p>
                    </div>
                    
                    <h6>¿Estás seguro de que quieres eliminar este usuario?</h6>
                    <div class="alert alert-info">
                        <strong>Usuario:</strong> <span id="usuarioNombreEliminar"></span><br>
                        <strong>Email:</strong> <span id="usuarioEmailEliminar"></span><br>
                        <strong>Rol:</strong> <span id="usuarioRolEliminar"></span>
                    </div>
                    
                    <p>Se eliminarán:</p>
                    <ul>
                        <li>✗ Toda la información personal del usuario</li>
                        <li>✗ Todas las reseñas escritas por el usuario</li>
                        <li id="tiendaInfo" style="display: none;">✗ Su tienda y todas las reseñas de la tienda</li>
                        <li>✗ Historial de actividad y estadísticas</li>
                        <li>✗ El usuario desaparecerá completamente del sistema</li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>💡 Alternativa:</strong> Si solo quieres pausar temporalmente al usuario, 
                        usa el botón "Desactivar" en lugar de eliminar.
                    </div>
                    
                    <form id="formEliminarUsuario" method="POST">
                        <input type="hidden" name="usuario_id" id="usuarioIdEliminar">
                        <input type="hidden" name="accion" value="eliminar">
                        
                        <div class="mb-3">
                            <label for="confirmacionEliminarUsuario" class="form-label">
                                <strong>Para confirmar, escribe exactamente:</strong> <code>ELIMINAR</code>
                            </label>
                            <input type="text" class="form-control" id="confirmacionEliminarUsuario" 
                                   placeholder="Escribe ELIMINAR para confirmar" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="btnConfirmarEliminarUsuario" disabled>
                        <i class="bi bi-trash3-fill"></i> Sí, Eliminar Permanentemente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script para manejar el modal de eliminar usuario
        document.addEventListener('DOMContentLoaded', function() {
            const modalEliminar = document.getElementById('modalEliminarUsuario');
            const usuarioNombre = document.getElementById('usuarioNombreEliminar');
            const usuarioEmail = document.getElementById('usuarioEmailEliminar');
            const usuarioRol = document.getElementById('usuarioRolEliminar');
            const usuarioId = document.getElementById('usuarioIdEliminar');
            const tiendaInfo = document.getElementById('tiendaInfo');
            const confirmacionInput = document.getElementById('confirmacionEliminarUsuario');
            const btnConfirmar = document.getElementById('btnConfirmarEliminarUsuario');
            const formEliminar = document.getElementById('formEliminarUsuario');
            
            // Cuando se abre el modal
            modalEliminar.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const usuarioIdValue = button.getAttribute('data-usuario-id');
                const usuarioNombreValue = button.getAttribute('data-usuario-nombre');
                const usuarioEmailValue = button.getAttribute('data-usuario-email');
                const usuarioRolValue = button.getAttribute('data-usuario-rol');
                
                usuarioNombre.textContent = usuarioNombreValue;
                usuarioEmail.textContent = usuarioEmailValue;
                usuarioRol.textContent = usuarioRolValue.toUpperCase();
                usuarioId.value = usuarioIdValue;
                
                // Mostrar información adicional si es vendedor
                if (usuarioRolValue === 'vendedor') {
                    tiendaInfo.style.display = 'list-item';
                } else {
                    tiendaInfo.style.display = 'none';
                }
                
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