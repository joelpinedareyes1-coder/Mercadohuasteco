<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir la conexión a la base de datos
require_once '../config.php';

try {
    // Preparar la consulta
    $sql = "SELECT * FROM chispitas_dialogo WHERE esta_activo = 1 ORDER BY orden ASC";
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Obtener todos los resultados
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolver los resultados como JSON
    echo json_encode($resultados);
    
} catch (PDOException $e) {
    // En caso de error, devolver un JSON con el error
    http_response_code(500);
    echo json_encode([
        'error' => 'Error en la base de datos',
        'message' => $e->getMessage()
    ]);
}
?>