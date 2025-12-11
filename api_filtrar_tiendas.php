<?php
require_once 'config.php';

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Obtener parámetros de filtrado
$categoria_filtro = isset($_GET['categoria']) ? limpiar_entrada($_GET['categoria']) : '';
$busqueda = isset($_GET['busqueda']) ? limpiar_entrada($_GET['busqueda']) : '';

try {
    // Verificar si el usuario está logueado y es cliente para mostrar favoritos
    $mostrar_favoritos = (esta_logueado() && $_SESSION['rol'] === 'cliente');
    $favoritos_usuario = [];

    if ($mostrar_favoritos) {
        try {
            $stmt_favoritos = $pdo->prepare("SELECT tienda_id FROM favoritos WHERE usuario_id = ? AND activo = 1");
            $stmt_favoritos->execute([$_SESSION['user_id']]);
            $favoritos_usuario = $stmt_favoritos->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) {
            $favoritos_usuario = [];
        }
    }

    // Construir consulta base
    $sql = "
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND u.activo = 1
    ";
    
    // Intentar con columna estado si existe
    try {
        $sql_test = "SELECT estado FROM tiendas LIMIT 1";
        $pdo->query($sql_test);
        // Si llegamos aquí, la columna estado existe
        $sql = "
            SELECT t.*, u.nombre as vendedor_nombre,
                   COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
                   COUNT(c.id) as total_calificaciones
            FROM tiendas t 
            INNER JOIN usuarios u ON t.vendedor_id = u.id 
            LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
            WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
        ";
    } catch(PDOException $e) {
        // La columna estado no existe, usar consulta original
    }
    
    $params = [];
    
    // Agregar filtro por categoría si existe
    if (!empty($categoria_filtro)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria_filtro;
    }
    
    // Agregar filtro por búsqueda si existe
    if (!empty($busqueda)) {
        $sql .= " AND t.nombre_tienda LIKE ?";
        $params[] = '%' . $busqueda . '%';
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC";
    
    // Ejecutar consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Función para mostrar estrellas (versión para API)
    function generar_estrellas($promedio, $total_calificaciones = 0) {
        $estrellas_html = '';
        $promedio_redondeado = round($promedio, 1);
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $promedio_redondeado) {
                $estrellas_html .= '<i class="bi bi-star-fill text-warning"></i>';
            } elseif ($i - 0.5 <= $promedio_redondeado) {
                $estrellas_html .= '<i class="bi bi-star-half text-warning"></i>';
            } else {
                $estrellas_html .= '<i class="bi bi-star text-muted"></i>';
            }
        }
        
        if ($total_calificaciones > 0) {
            $estrellas_html .= ' <small class="text-muted">(' . $promedio_redondeado . ' - ' . $total_calificaciones . ' reseñas)</small>';
        } else {
            $estrellas_html .= ' <small class="text-muted">Sin reseñas</small>';
        }
        
        return $estrellas_html;
    }
    
    // Función para truncar texto
    function truncar_texto($texto, $limite = 100) {
        if (strlen($texto) > $limite) {
            return substr($texto, 0, $limite) . '...';
        }
        return $texto;
    }
    
    // Procesar datos para el frontend
    $tiendas_procesadas = [];
    foreach ($tiendas as $tienda) {
        // Verificar si la imagen existe
        $logo_valido = '';
        if (!empty($tienda['logo']) && file_exists($tienda['logo'])) {
            $logo_valido = $tienda['logo'];
        }
        
        // Generar botón de favoritos si el usuario es cliente
        $boton_favorito = '';
        if ($mostrar_favoritos) {
            $es_favorito = in_array($tienda['id'], $favoritos_usuario);
            $clase_btn = $es_favorito ? 'btn-danger' : 'btn-outline-danger';
            $icono = $es_favorito ? 'bi-heart-fill' : 'bi-heart';
            $titulo = $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos';
            
            $boton_favorito = '<button type="button" 
                                    class="btn ' . $clase_btn . ' btn-favorito"
                                    data-tienda-id="' . $tienda['id'] . '"
                                    data-es-favorito="' . ($es_favorito ? 'true' : 'false') . '"
                                    title="' . $titulo . '">
                                    <i class="bi ' . $icono . '"></i>
                                </button>';
        }
        
        $tiendas_procesadas[] = [
            'id' => $tienda['id'],
            'nombre_tienda' => htmlspecialchars($tienda['nombre_tienda']),
            'descripcion' => htmlspecialchars(truncar_texto($tienda['descripcion'], 100)),
            'categoria' => htmlspecialchars($tienda['categoria']),
            'vendedor_nombre' => htmlspecialchars($tienda['vendedor_nombre']),
            'clics' => $tienda['clics'],
            'promedio_estrellas' => round($tienda['promedio_estrellas'], 1),
            'total_calificaciones' => $tienda['total_calificaciones'],
            'estrellas_html' => generar_estrellas($tienda['promedio_estrellas'], $tienda['total_calificaciones']),
            'logo' => $logo_valido,
            'fecha_registro' => date('M Y', strtotime($tienda['fecha_registro'])),
            'es_destacado' => (bool)$tienda['es_destacado'],
            'boton_favorito' => $boton_favorito
        ];
    }
    
    // Obtener estadísticas
    $total_tiendas = count($tiendas_procesadas);
    $vendedores_unicos = count(array_unique(array_column($tiendas, 'vendedor_id')));
    $total_clics = array_sum(array_column($tiendas, 'clics'));
    
    // Respuesta JSON
    $response = [
        'success' => true,
        'tiendas' => $tiendas_procesadas,
        'estadisticas' => [
            'total_tiendas' => $total_tiendas,
            'vendedores_unicos' => $vendedores_unicos,
            'total_clics' => $total_clics
        ],
        'filtros' => [
            'categoria' => $categoria_filtro,
            'busqueda' => $busqueda
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    // Error en la consulta
    $response = [
        'success' => false,
        'error' => 'Error al obtener las tiendas: ' . $e->getMessage(),
        'tiendas' => [],
        'estadisticas' => [
            'total_tiendas' => 0,
            'vendedores_unicos' => 0,
            'total_clics' => 0
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>