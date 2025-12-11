<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

// Procesar cambio de membresía Premium
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id']) && isset($_POST['es_premium'])) {
    $usuario_id = (int)$_POST['usuario_id'];
    $es_premium = (int)$_POST['es_premium'];
    
    // Prevenir que el admin se modifique a sí mismo desde aquí
    if ($usuario_id === $_SESSION['user_id']) {
        $_SESSION['error_premium'] = "No puedes modificar tu propia membresía desde aquí.";
        header("Location: gestionar_usuarios.php");
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // Actualizar el estado premium del usuario
        $stmt = $pdo->prepare("UPDATE usuarios SET es_premium = ? WHERE id = ?");
        $stmt->execute([$es_premium, $usuario_id]);
        
        if ($stmt->rowCount() > 0) {
            // Si el usuario es vendedor, actualizar su tienda como destacada
            $stmt_check = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
            $stmt_check->execute([$usuario_id]);
            $usuario = $stmt_check->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario && $usuario['rol'] === 'vendedor') {
                // Actualizar tienda como destacada si es Premium
                $stmt_tienda = $pdo->prepare("UPDATE tiendas SET es_destacado = ? WHERE vendedor_id = ?");
                $stmt_tienda->execute([$es_premium, $usuario_id]);
                
                // Log detallado
                error_log("Tienda actualizada - Vendedor ID: $usuario_id, es_destacado: $es_premium, Filas afectadas: " . $stmt_tienda->rowCount());
            }
            
            $pdo->commit();
            
            $accion = $es_premium ? 'ascendido a Premium' : 'removido de Premium';
            $_SESSION['mensaje_premium'] = "Usuario $accion exitosamente.";
            if ($usuario && $usuario['rol'] === 'vendedor') {
                $_SESSION['mensaje_premium'] .= " Su tienda ahora está " . ($es_premium ? "destacada" : "normal") . ".";
            }
            
            // Log de la acción
            error_log("Membresía Premium actualizada - Usuario ID: $usuario_id, Premium: $es_premium, Admin: " . $_SESSION['user_id']);
        } else {
            $pdo->rollBack();
            $_SESSION['error_premium'] = "No se pudo actualizar la membresía. Verifica que el usuario exista.";
        }
        
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error_premium'] = "Error al actualizar la membresía: " . $e->getMessage();
        error_log("Error al actualizar membresía Premium - Usuario ID: $usuario_id - Error: " . $e->getMessage());
    }
    
    // Redirigir de vuelta a gestionar usuarios
    header("Location: gestionar_usuarios.php" . (isset($_GET['rol']) ? "?rol=" . $_GET['rol'] : ""));
    exit();
} else {
    // Si no hay datos POST válidos, redirigir
    header("Location: gestionar_usuarios.php");
    exit();
}
?>
