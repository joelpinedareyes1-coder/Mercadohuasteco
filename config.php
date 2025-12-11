<?php
// Configuración Híbrida (Funciona en Railway y en tu PC)
// Si existe la variable de entorno (Railway), la usa. Si no, usa la de tu PC (derecha).

$host = getenv("MYSQLHOST") ?: 'localhost';
$dbname = getenv("MYSQLDATABASE") ?: 'directorio_tiendas';
$user = getenv("MYSQLUSER") ?: 'root';
$pass = getenv("MYSQLPASSWORD") ?: '';
$port = getenv("MYSQLPORT") ?: 3306;

// Configuración de sesiones
session_start();

// Conexión a la base de datos con PDO
try {
    // Fíjate que agregué ";port=$port" dentro de las comillas
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
    
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para limpiar datos de entrada
function limpiar_entrada($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para verificar si el usuario está logueado
function esta_logueado() {
    return isset($_SESSION['user_id']) && isset($_SESSION['rol']);
}

// Función para redirigir según el rol
function redirigir_por_rol() {
    if (esta_logueado()) {
        if ($_SESSION['rol'] === 'cliente') {
            header("Location: dashboard_cliente.php");
        } elseif ($_SESSION['rol'] === 'admin') {
            header("Location: dashboard_admin.php");
        } elseif ($_SESSION['rol'] === 'vendedor') {
            header("Location: panel_vendedor.php");
        } else {
            // Fallback por si hay un rol no reconocido
            header("Location: index.php");
        }
        exit();
    }
}

// Función para verificar si el usuario es admin
function es_admin() {
    return esta_logueado() && $_SESSION['rol'] === 'admin';
}

// Función para obtener la URL del dashboard según el rol
function obtener_dashboard_url() {
    if (!esta_logueado()) {
        return 'auth.php';
    }
    
    switch ($_SESSION['rol']) {
        case 'admin':
            return 'dashboard_admin.php';
        case 'cliente':
            return 'dashboard_cliente.php';
        case 'vendedor':
            return 'panel_vendedor.php';
        default:
            return 'index.php';
    }
}

// Función para verificar si el usuario sigue activo en el sistema
function verificar_usuario_activo() {
    global $pdo;
    
    if (esta_logueado()) {
        try {
            $stmt = $pdo->prepare("SELECT activo FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario || !$usuario['activo']) {
                // Usuario desactivado - marcar en sesión para mostrar banner
                $_SESSION['cuenta_desactivada'] = true;
                return false;
            } else {
                // Usuario activo - limpiar flag si existe
                unset($_SESSION['cuenta_desactivada']);
                return true;
            }
        } catch(PDOException $e) {
            // Error en consulta - por seguridad cerrar sesión
            session_destroy();
            header("Location: auth.php");
            exit();
        }
    }
    return true;
}

// Función para desactivar cuenta de usuario
function desactivarCuenta($user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Obtener información del usuario
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception("Usuario no encontrado");
        }
        
        // Desactivar usuario
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
        $resultado_usuario = $stmt->execute([$user_id]);
        
        if (!$resultado_usuario) {
            throw new Exception("Error al desactivar usuario");
        }
        
        // Si es vendedor, desactivar su tienda también
        if ($usuario['rol'] === 'vendedor') {
            // Intentar con columna estado primero, luego activo
            try {
                $stmt = $pdo->prepare("UPDATE tiendas SET estado = 0 WHERE vendedor_id = ?");
                $resultado_tienda = $stmt->execute([$user_id]);
            } catch (PDOException $e) {
                // Si falla (columna estado no existe), usar activo
                $stmt = $pdo->prepare("UPDATE tiendas SET activo = 0 WHERE vendedor_id = ?");
                $resultado_tienda = $stmt->execute([$user_id]);
            }
        }
        
        $pdo->commit();
        
        // Log de la acción
        error_log("Cuenta desactivada - Usuario ID: $user_id, Rol: " . $usuario['rol']);
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al desactivar cuenta ID $user_id: " . $e->getMessage());
        throw $e;
    }
}

// Función para reactivar cuenta de usuario
function reactivarCuenta($user_id, $pdo) {
    try {
        $pdo->beginTransaction();
        
        // Obtener información del usuario
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario) {
            throw new Exception("Usuario no encontrado");
        }
        
        // Reactivar usuario
        $stmt = $pdo->prepare("UPDATE usuarios SET activo = 1 WHERE id = ?");
        $resultado_usuario = $stmt->execute([$user_id]);
        
        if (!$resultado_usuario) {
            throw new Exception("Error al reactivar usuario");
        }
        
        // Si es vendedor, reactivar su tienda también
        if ($usuario['rol'] === 'vendedor') {
            // Intentar con columna estado primero, luego activo
            try {
                $stmt = $pdo->prepare("UPDATE tiendas SET estado = 1 WHERE vendedor_id = ?");
                $resultado_tienda = $stmt->execute([$user_id]);
            } catch (PDOException $e) {
                // Si falla (columna estado no existe), usar activo
                $stmt = $pdo->prepare("UPDATE tiendas SET activo = 1 WHERE vendedor_id = ?");
                $resultado_tienda = $stmt->execute([$user_id]);
            }
        }
        
        $pdo->commit();
        
        // Log de la acción
        error_log("Cuenta reactivada - Usuario ID: $user_id, Rol: " . $usuario['rol']);
        
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al reactivar cuenta ID $user_id: " . $e->getMessage());
        throw $e;
    }
}
?>