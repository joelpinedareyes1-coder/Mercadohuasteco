<?php
require_once 'config.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
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
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$tienda_id = (int)$input['tienda_id'];
$action = $input['action']; // 'add' o 'remove'
$usuario_id = $_SESSION['user_id'];

// Validar que la tienda existe
try {
    $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE id = ? AND activo = 1");
    $stmt->execute([$tienda_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tienda no encontrada']);
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar tienda']);
    exit;
}

try {
    if ($action === 'add') {
        // Agregar a favoritos
        $stmt = $pdo->prepare("INSERT IGNORE INTO favoritos (usuario_id, tienda_id) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $tienda_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Agregado a favoritos', 'action' => 'added']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Ya estaba en favoritos', 'action' => 'already_added']);
        }
        
    } elseif ($action === 'remove') {
        // Quitar de favoritos
        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE usuario_id = ? AND tienda_id = ?");
        $stmt->execute([$usuario_id, $tienda_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Quitado de favoritos', 'action' => 'removed']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No estaba en favoritos', 'action' => 'not_found']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>