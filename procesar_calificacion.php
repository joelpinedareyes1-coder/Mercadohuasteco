<?php
require_once 'config.php';

// Función para procesar nueva calificación
function procesarNuevaCalificacion($user_id, $tienda_id, $estrellas, $comentario, $pdo) {
    try {
        // Verificar que el usuario sea cliente
        $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ? AND activo = 1");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$usuario || $usuario['rol'] !== 'cliente') {
            return ['success' => false, 'message' => 'Solo los clientes pueden calificar tiendas.'];
        }
        
        // Verificar que la tienda existe y está activa
        $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE id = ? AND activo = 1 AND estado = 1");
        $stmt->execute([$tienda_id]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'La tienda no existe o no está disponible.'];
        }
        
        // Verificar si el usuario ya calificó esta tienda
        $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE user_id = ? AND tienda_id = ?");
        $stmt->execute([$user_id, $tienda_id]);
        $calificacion_existente = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($calificacion_existente) {
            // Actualizar calificación existente
            $stmt = $pdo->prepare("
                UPDATE calificaciones 
                SET estrellas = ?, comentario = ?, fecha_calificacion = NOW(), activo = 1, esta_aprobada = 1
                WHERE user_id = ? AND tienda_id = ?
            ");
            $stmt->execute([$estrellas, $comentario, $user_id, $tienda_id]);
            $message = "Tu calificación ha sido actualizada exitosamente.";
        } else {
            // Crear nueva calificación
            $stmt = $pdo->prepare("
                INSERT INTO calificaciones (user_id, tienda_id, estrellas, comentario, fecha_calificacion, activo, esta_aprobada) 
                VALUES (?, ?, ?, ?, NOW(), 1, 1)
            ");
            $stmt->execute([$user_id, $tienda_id, $estrellas, $comentario]);
            $message = "Tu calificación ha sido registrada exitosamente.";
        }
        
        return ['success' => true, 'message' => $message];
        
    } catch (PDOException $e) {
        error_log("Error al procesar calificación: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor. Inténtalo de nuevo.'];
    }
}

// Función para obtener calificaciones de una tienda
function obtenerCalificacionesTienda($tienda_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, u.nombre as usuario_nombre
            FROM calificaciones c
            INNER JOIN usuarios u ON c.user_id = u.id
            WHERE c.tienda_id = ? AND c.activo = 1 AND c.esta_aprobada = 1
            ORDER BY c.fecha_calificacion DESC
        ");
        $stmt->execute([$tienda_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener calificaciones: " . $e->getMessage());
        return [];
    }
}

// Función para obtener estadísticas de calificaciones
function obtenerEstadisticasCalificaciones($tienda_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_calificaciones,
                COALESCE(AVG(estrellas), 0) as promedio_estrellas,
                COUNT(CASE WHEN estrellas = 5 THEN 1 END) as cinco_estrellas,
                COUNT(CASE WHEN estrellas = 4 THEN 1 END) as cuatro_estrellas,
                COUNT(CASE WHEN estrellas = 3 THEN 1 END) as tres_estrellas,
                COUNT(CASE WHEN estrellas = 2 THEN 1 END) as dos_estrellas,
                COUNT(CASE WHEN estrellas = 1 THEN 1 END) as una_estrella
            FROM calificaciones 
            WHERE tienda_id = ? AND activo = 1 AND esta_aprobada = 1
        ");
        $stmt->execute([$tienda_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        return [
            'total_calificaciones' => 0,
            'promedio_estrellas' => 0,
            'cinco_estrellas' => 0,
            'cuatro_estrellas' => 0,
            'tres_estrellas' => 0,
            'dos_estrellas' => 0,
            'una_estrella' => 0
        ];
    }
}

// Función para verificar si un usuario ya calificó una tienda
function usuarioYaCalificoTienda($user_id, $tienda_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT estrellas, comentario, fecha_calificacion 
            FROM calificaciones 
            WHERE user_id = ? AND tienda_id = ? AND activo = 1
        ");
        $stmt->execute([$user_id, $tienda_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error al verificar calificación: " . $e->getMessage());
        return false;
    }
}

// Si se llama directamente via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'cliente') {
        echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión como cliente.']);
        exit;
    }
    
    if ($_POST['action'] === 'nueva_calificacion') {
        $tienda_id = (int)$_POST['tienda_id'];
        $estrellas = (int)$_POST['estrellas'];
        $comentario = trim($_POST['comentario']);
        
        // Validaciones
        if ($estrellas < 1 || $estrellas > 5) {
            echo json_encode(['success' => false, 'message' => 'La calificación debe ser entre 1 y 5 estrellas.']);
            exit;
        }
        
        if (empty($comentario)) {
            echo json_encode(['success' => false, 'message' => 'El comentario es obligatorio.']);
            exit;
        }
        
        $resultado = procesarNuevaCalificacion($_SESSION['user_id'], $tienda_id, $estrellas, $comentario, $pdo);
        echo json_encode($resultado);
        exit;
    }
}
?>