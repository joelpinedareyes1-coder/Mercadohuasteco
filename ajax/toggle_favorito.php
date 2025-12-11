<?php
require_once '../config.php';

// Verificar que el usuario esté logueado y sea cliente
if (!esta_logueado() || $_SESSION['rol'] !== 'cliente') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['tienda_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$tienda_id = (int)$input['tienda_id'];
$action = $input['action'];
$user_id = $_SESSION['user_id'];

try {
    if ($action === 'add') {
        // Verificar que la tienda existe y está activa
        $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE id = ? AND activo = 1");
        $stmt->execute([$tienda_id]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Tienda no encontrada']);
            exit;
        }
        
        // Verificar si ya está en favoritos
        $stmt = $pdo->prepare("SELECT id FROM favoritos WHERE usuario_id = ? AND tienda_id = ?");
        $stmt->execute([$user_id, $tienda_id]);
        
        if ($stmt->fetch()) {
            // Ya existe, solo activarlo
            $stmt = $pdo->prepare("UPDATE favoritos SET activo = 1, fecha_agregado = NOW() WHERE usuario_id = ? AND tienda_id = ?");
            $stmt->execute([$user_id, $tienda_id]);
        } else {
            // Crear nuevo favorito
            $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, tienda_id, fecha_agregado) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, $tienda_id]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Agregado a favoritos']);
        
    } elseif ($action === 'remove') {
        // Desactivar favorito
        $stmt = $pdo->prepare("UPDATE favoritos SET activo = 0 WHERE usuario_id = ? AND tienda_id = ?");
        $stmt->execute([$user_id, $tienda_id]);
        
        echo json_encode(['success' => true, 'message' => 'Removido de favoritos']);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch(PDOException $e) {
    error_log("Error en toggle_favorito.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error del servidor']);
}
?>