<?php
session_start();
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

// Solo permitir a clientes eliminar su cuenta
if ($_SESSION['rol'] !== 'cliente') {
    header("Location: mi_perfil.php?error=no_autorizado");
    exit();
}

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar_cuenta') {
    
    $user_id = $_SESSION['user_id'];
    $confirmacion = $_POST['confirmacion'];
    $password_actual = $_POST['password_actual'];
    
    // Validaciones
    if ($confirmacion !== 'ELIMINAR') {
        $error = "Debes escribir exactamente 'ELIMINAR' para confirmar.";
    } elseif (empty($password_actual)) {
        $error = "Debes ingresar tu contraseña actual.";
    } else {
        try {
            // Verificar contraseña actual
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ? AND activo = 1");
            $stmt->execute([$user_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario || !password_verify($password_actual, $usuario['password'])) {
                $error = "Contraseña incorrecta.";
            } else {
                // Iniciar transacción para eliminar todo de forma segura
                $pdo->beginTransaction();
                
                try {
                    // 1. Eliminar todas las reseñas del usuario
                    $stmt = $pdo->prepare("DELETE FROM calificaciones WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $reseñas_eliminadas = $stmt->rowCount();
                    
                    // 2. Eliminar el usuario permanentemente
                    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Confirmar transacción
                        $pdo->commit();
                        
                        // Destruir sesión
                        session_destroy();
                        
                        // Redirigir a página de confirmación
                        header("Location: cuenta_eliminada.php?reseñas=$reseñas_eliminadas");
                        exit();
                        
                    } else {
                        throw new Exception("No se pudo eliminar la cuenta.");
                    }
                    
                } catch (Exception $e) {
                    // Revertir transacción en caso de error
                    $pdo->rollBack();
                    throw $e;
                }
            }
            
        } catch (PDOException $e) {
            $error = "Error al eliminar la cuenta. Por favor, inténtalo más tarde.";
            error_log("Error eliminando cuenta usuario $user_id: " . $e->getMessage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Si llegamos aquí, hubo un error
header("Location: mi_perfil.php?error=" . urlencode($error));
exit();
?>