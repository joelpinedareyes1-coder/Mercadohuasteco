<?php
require_once 'config.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción.'
    ]);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido.'
    ]);
    exit();
}

// Obtener el ID de la foto
$foto_id = isset($_POST['foto_id']) ? (int)$_POST['foto_id'] : 0;

if ($foto_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de foto inválido.'
    ]);
    exit();
}

$vendedor_id = (int)$_SESSION['user_id'];

try {
    // Obtener el ID de la tienda del vendedor
    $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE vendedor_id = ?");
    $stmt->execute([$vendedor_id]);
    $tienda = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tienda) {
        echo json_encode([
            'success' => false,
            'message' => 'No tienes una tienda registrada.'
        ]);
        exit();
    }
    
    $tienda_id = $tienda['id'];
    
    // Obtener información de la foto antes de eliminar
    $stmt = $pdo->prepare("SELECT url_imagen FROM galeria_tiendas WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$foto_id, $tienda_id]);
    $foto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$foto) {
        echo json_encode([
            'success' => false,
            'message' => 'Foto no encontrada o no tienes permisos para eliminarla.'
        ]);
        exit();
    }
    
    // Eliminar de base de datos
    $stmt = $pdo->prepare("DELETE FROM galeria_tiendas WHERE id = ? AND tienda_id = ?");
    $stmt->execute([$foto_id, $tienda_id]);
    
    // Eliminar archivo físico
    if (file_exists($foto['url_imagen'])) {
        unlink($foto['url_imagen']);
    }
    
    // Obtener nuevo total de fotos
    $stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM galeria_tiendas WHERE tienda_id = ? AND activo = 1");
    $stmt_count->execute([$tienda_id]);
    $total_fotos = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Foto eliminada exitosamente.',
        'total_fotos' => $total_fotos
    ]);
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar la foto: ' . $e->getMessage()
    ]);
}
?>
