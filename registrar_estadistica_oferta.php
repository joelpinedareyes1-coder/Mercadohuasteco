<?php
/**
 * Registra estadísticas de ofertas (vistas y clics)
 */

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['tipo'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos inválidos']);
    exit();
}

try {
    if ($data['tipo'] === 'vista' && isset($data['ofertas']) && is_array($data['ofertas'])) {
        // Registrar vistas para múltiples ofertas
        foreach ($data['ofertas'] as $oferta_id) {
            $oferta_id = (int)$oferta_id;
            if ($oferta_id > 0) {
                $stmt = $pdo->prepare("UPDATE cupones_ofertas SET vistas = vistas + 1 WHERE id = ?");
                $stmt->execute([$oferta_id]);
            }
        }
        echo json_encode(['success' => true, 'message' => 'Vistas registradas']);
        
    } elseif ($data['tipo'] === 'clic' && isset($data['oferta_id'])) {
        // Registrar clic en una oferta específica
        $oferta_id = (int)$data['oferta_id'];
        if ($oferta_id > 0) {
            $stmt = $pdo->prepare("UPDATE cupones_ofertas SET clics = clics + 1 WHERE id = ?");
            $stmt->execute([$oferta_id]);
            echo json_encode(['success' => true, 'message' => 'Clic registrado']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'ID de oferta inválido']);
        }
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo de estadística no válido']);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar estadística']);
}
?>
