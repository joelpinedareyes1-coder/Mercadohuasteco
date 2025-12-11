<?php
session_start();
require_once 'config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

$vendedor_id = $_SESSION['user_id'];
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'];
    $tienda_id = (int)$_POST['tienda_id'];
    
    // Verificar que la tienda pertenece al vendedor
    try {
        $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE id = ? AND vendedor_id = ?");
        $stmt->execute([$tienda_id, $vendedor_id]);
        $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tienda) {
            $error = "Error: No tienes permisos para gestionar esta tienda.";
        } else {
            if ($accion === 'reactivar_tienda') {
                // Reactivar tienda
                try {
                    // Intentar con columna estado primero
                    $stmt = $pdo->prepare("UPDATE tiendas SET estado = 1 WHERE id = ? AND vendedor_id = ?");
                    $stmt->execute([$tienda_id, $vendedor_id]);
                    
                    // Redirigir con mensaje de éxito
                    header("Location: panel_vendedor.php?mensaje=" . urlencode("Tu tienda ha sido reactivada exitosamente y ya aparece en el directorio público."));
                    exit();
                } catch (PDOException $e) {
                    // Si falla (columna estado no existe), usar columna activo
                    try {
                        $stmt = $pdo->prepare("UPDATE tiendas SET activo = 1 WHERE id = ? AND vendedor_id = ?");
                        $stmt->execute([$tienda_id, $vendedor_id]);
                        
                        // Redirigir con mensaje de éxito
                        header("Location: panel_vendedor.php?mensaje=" . urlencode("Tu tienda ha sido reactivada exitosamente y ya aparece en el directorio público."));
                        exit();
                    } catch (PDOException $e2) {
                        $error = "Error al reactivar la tienda: " . $e2->getMessage();
                        error_log("Error reactivando tienda: " . $e2->getMessage());
                    }
                }
            } elseif ($accion === 'desactivar_tienda') {
                $password_actual = $_POST['password_actual'];
                $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : '';
                
                if (empty($password_actual)) {
                    $error = "Debes ingresar tu contraseña para confirmar.";
                } else {
                    // Verificar contraseña
                    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
                    $stmt->execute([$vendedor_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$usuario || !password_verify($password_actual, $usuario['password'])) {
                        $error = "Contraseña incorrecta.";
                    } else {
                        // Intentar desactivar tienda usando la columna estado
                        try {
                            $stmt = $pdo->prepare("UPDATE tiendas SET estado = 0 WHERE id = ? AND vendedor_id = ?");
                            $stmt->execute([$tienda_id, $vendedor_id]);
                            
                            if ($stmt->rowCount() > 0) {
                                // Redirigir a página de confirmación
                                header("Location: tienda_desactivada.php?nombre=" . urlencode($tienda['nombre_tienda']));
                                exit();
                            } else {
                                $error = "No se pudo desactivar la tienda. Verifica que la tienda esté activa.";
                            }
                        } catch (PDOException $e) {
                            // Si falla (columna estado no existe), usar columna activo
                            try {
                                $stmt = $pdo->prepare("UPDATE tiendas SET activo = 0 WHERE id = ? AND vendedor_id = ?");
                                $stmt->execute([$tienda_id, $vendedor_id]);
                                
                                if ($stmt->rowCount() > 0) {
                                    // Redirigir a página de confirmación
                                    header("Location: tienda_desactivada.php?nombre=" . urlencode($tienda['nombre_tienda']));
                                    exit();
                                } else {
                                    $error = "No se pudo desactivar la tienda. Verifica que la tienda esté activa.";
                                }
                            } catch (PDOException $e2) {
                                $error = "Error al desactivar la tienda: " . $e2->getMessage();
                                error_log("Error desactivando tienda: " . $e2->getMessage());
                            }
                        }
                    }
                }
            } elseif ($accion === 'eliminar_tienda') {
                $password_actual = $_POST['password_actual'];
                
                if (empty($password_actual)) {
                    $error = "Debes ingresar tu contraseña para confirmar.";
                } else {
                    // Verificar contraseña
                    $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
                    $stmt->execute([$vendedor_id]);
                    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$usuario || !password_verify($password_actual, $usuario['password'])) {
                        $error = "Contraseña incorrecta.";
                    } else {
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
                            $stmt = $pdo->prepare("DELETE FROM tiendas WHERE id = ? AND vendedor_id = ?");
                            $stmt->execute([$tienda_id, $vendedor_id]);
                            
                            if ($stmt->rowCount() > 0) {
                                $pdo->commit();
                                
                                // Redirigir a página de confirmación
                                header("Location: tienda_eliminada.php?nombre=" . urlencode($tienda['nombre_tienda']) . "&reseñas=$reseñas_eliminadas&fotos=$fotos_eliminadas");
                                exit();
                            } else {
                                throw new Exception("No se pudo eliminar la tienda.");
                            }
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error = "Error al eliminar la tienda: " . $e->getMessage();
                            error_log("Error eliminando tienda: " . $e->getMessage());
                        }
                    }
                }
            } else {
                $error = "Acción no válida.";
            }
        }
        
    } catch (PDOException $e) {
        $error = "Error al procesar la solicitud: " . $e->getMessage();
        error_log("Error en gestionar_tienda.php: " . $e->getMessage());
    }
}

// Redirigir de vuelta al panel con mensaje
$redirect_url = "panel_vendedor.php";

if ($error) {
    $redirect_url .= "?error=" . urlencode($error);
} elseif ($mensaje) {
    $redirect_url .= "?mensaje=" . urlencode($mensaje);
}

header("Location: $redirect_url");
exit();
?>