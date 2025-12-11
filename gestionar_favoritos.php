<?php
session_start();
require_once 'config.php';

// Headers para JSON
header('Content-Type: application/json');

// Verificar que el usuario esté logueado y sea cliente
if (!esta_logueado() || $_SESSION['rol'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $tienda_id = (int)($_POST['tienda_id'] ?? 0);
    
    if ($tienda_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de tienda inválido']);
        exit();
    }
    
    try {
        // Verificar que la tienda existe y está activa
        $stmt = $pdo->prepare("SELECT id, nombre_tienda FROM tiendas WHERE id = ? AND activo = 1");
        $stmt->execute([$tienda_id]);
        $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tienda) {
            echo json_encode(['success' => false, 'message' => 'Tienda no encontrada']);
            exit();
        }
        
        if ($accion === 'agregar') {
            // Agregar a favoritos
            $stmt = $pdo->prepare("INSERT IGNORE INTO favoritos (usuario_id, tienda_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $tienda_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tienda agregada a favoritos',
                'action' => 'added',
                'tienda_nombre' => $tienda['nombre_tienda']
            ]);
            
        } elseif ($accion === 'quitar') {
            // Quitar de favoritos
            $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND tienda_id = ?");
            $stmt->execute([$user_id, $tienda_id]);
            
            $rows_affected = $stmt->rowCount();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Tienda quitada de favoritos',
                'action' => 'removed',
                'tienda_nombre' => $tienda['nombre_tienda'],
                'rows_affected' => $rows_affected,
                'debug' => [
                    'user_id' => $user_id,
                    'tienda_id' => $tienda_id,
                    'accion' => $accion
                ]
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
        
    } catch(PDOException $e) {
        error_log("Error en gestionar_favoritos.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error del servidor']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? '';
    $tienda_id = (int)($_GET['tienda_id'] ?? 0);
    
    if ($tienda_id <= 0) {
        header("Location: mis_favoritos.php?error=ID de tienda inválido");
        exit();
    }
    
    if ($accion === 'quitar') {
        try {
            // Verificar que la tienda existe
            $stmt = $pdo->prepare("SELECT id, nombre_tienda FROM tiendas WHERE id = ? AND activo = 1");
            $stmt->execute([$tienda_id]);
            $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tienda) {
                header("Location: mis_favoritos.php?error=Tienda no encontrada");
                exit();
            }
            
            // Quitar de favoritos
            $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND tienda_id = ?");
            $stmt->execute([$user_id, $tienda_id]);
            
            $rows_affected = $stmt->rowCount();
            
            if ($rows_affected > 0) {
                header("Location: mis_favoritos.php?mensaje=Favorito eliminado exitosamente");
            } else {
                header("Location: mis_favoritos.php?mensaje=El favorito ya había sido eliminado");
            }
            exit();
            
        } catch(PDOException $e) {
            error_log("Error al quitar favorito: " . $e->getMessage());
            header("Location: mis_favoritos.php?error=Error del servidor");
            exit();
        }
    } else {
        // Obtener estado de favorito (funcionalidad original)
        try {
            $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND tienda_id = ?");
            $stmt->execute([$user_id, $tienda_id]);
            $favorito = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'is_favorite' => (bool)$favorito
            ]);
            
        } catch(PDOException $e) {
            error_log("Error al verificar favorito: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error del servidor']);
        }
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>