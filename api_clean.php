<?php
// API limpio para test
ob_start();

require_once 'config.php';

// Limpiar cualquier output previo
ob_clean();

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    // Obtener parámetros de búsqueda
    $categoria = $_GET['categoria'] ?? '';
    $busqueda = trim($_GET['busqueda'] ?? '');
    
    // Construir consulta con filtros
    $sql = "
        SELECT t.id, t.nombre_tienda, t.descripcion, t.logo, t.categoria, 
               t.clics, t.es_destacado, t.fecha_registro,
               u.nombre as vendedor_nombre
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
    ";
    
    $params = [];
    
    // Agregar filtro de búsqueda si existe
    if (!empty($busqueda)) {
        $sql .= " AND (t.nombre_tienda LIKE ? OR t.descripcion LIKE ? OR t.categoria LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    // Agregar filtro de categoría si existe
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " ORDER BY t.es_destacado DESC, t.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tiendas_procesadas = [];
    foreach ($tiendas as $tienda) {
        $tiendas_procesadas[] = [
            'id' => (int)$tienda['id'],
            'nombre_tienda' => $tienda['nombre_tienda'],
            'descripcion' => $tienda['descripcion'],
            'logo' => $tienda['logo'],
            'categoria' => $tienda['categoria'],
            'clics' => (int)$tienda['clics'],
            'es_destacado' => (bool)$tienda['es_destacado'],
            'vendedor_nombre' => $tienda['vendedor_nombre'],
            'fecha_registro' => date('d/m/Y', strtotime($tienda['fecha_registro']))
        ];
    }
    
    $response = [
        'success' => true,
        'tiendas' => $tiendas_procesadas,
        'estadisticas' => [
            'total_tiendas' => count($tiendas_procesadas),
            'vendedores_unicos' => count(array_unique(array_column($tiendas, 'vendedor_id'))),
            'total_clics' => array_sum(array_column($tiendas, 'clics'))
        ],
        'info_busqueda' => [
            'tipo_busqueda' => !empty($busqueda) ? 'busqueda' : (!empty($categoria) ? 'categoria' : 'todas'),
            'termino_original' => $busqueda,
            'categoria_filtro' => $categoria,
            'mensaje' => !empty($busqueda) ? "Resultados para: '$busqueda'" : (!empty($categoria) ? "Categoría: $categoria" : 'Mostrando todas las tiendas')
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'tiendas' => [],
        'estadisticas' => [
            'total_tiendas' => 0,
            'vendedores_unicos' => 0,
            'total_clics' => 0
        ],
        'info_busqueda' => [
            'tipo_busqueda' => 'error',
            'termino_original' => $_GET['busqueda'] ?? '',
            'categoria_filtro' => $_GET['categoria'] ?? '',
            'mensaje' => 'Error en la búsqueda'
        ]
    ];
    
    echo json_encode($error_response, JSON_UNESCAPED_UNICODE);
}

ob_end_flush();
?>