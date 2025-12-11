<?php
require_once 'config.php';

// Capturar cualquier salida no deseada
ob_start();

// Headers anti-caché
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Obtener parámetros
$categoria = $_GET['categoria'] ?? '';
$busqueda = trim($_GET['busqueda'] ?? '');

try {
    // Limpiar cualquier salida previa
    ob_clean();
    
    $resultado = busquedaInteligente($busqueda, $categoria, $pdo);
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // Limpiar cualquier salida previa
    ob_clean();
    
    echo json_encode([
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
            'termino_original' => $busqueda,
            'mensaje' => 'Error en la búsqueda'
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// Finalizar el buffer de salida
ob_end_flush();

function busquedaInteligente($busqueda, $categoria, $pdo) {
    // Verificar si el usuario está logueado y es cliente para favoritos
    $mostrar_favoritos = false;
    $favoritos_usuario = [];
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id']) && $_SESSION['rol'] === 'cliente') {
        $mostrar_favoritos = true;
        try {
            $stmt_favoritos = $pdo->prepare("SELECT tienda_id FROM favoritos WHERE usuario_id = ? AND activo = 1");
            $stmt_favoritos->execute([$_SESSION['user_id']]);
            $favoritos_usuario = $stmt_favoritos->fetchAll(PDO::FETCH_COLUMN);
        } catch(PDOException $e) {
            $favoritos_usuario = [];
        }
    }
    
    $tiendas = [];
    $info_busqueda = [
        'tipo_busqueda' => 'directa',
        'termino_original' => $busqueda,
        'termino_usado' => $busqueda,
        'mensaje' => ''
    ];
    
    // PASO 1: Búsqueda directa (exacta o por categoría)
    if (empty($busqueda)) {
        // Solo filtro por categoría
        $tiendas = buscarPorCategoria($categoria, $pdo);
        $info_busqueda['tipo_busqueda'] = 'categoria';
        $info_busqueda['mensaje'] = $categoria ? "Mostrando tiendas de la categoría: $categoria" : "Mostrando todas las tiendas";
    } else {
        // Búsqueda directa por término
        $tiendas = buscarDirecto($busqueda, $categoria, $pdo);
        
        if (empty($tiendas)) {
            // PASO 2: Búsqueda por sinónimos
            $sinonimos = obtenerSinonimos($busqueda, $pdo);
            
            foreach ($sinonimos as $sinonimo) {
                $tiendas = buscarDirecto($sinonimo['termino_principal'], $categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'sinonimo';
                    $info_busqueda['termino_usado'] = $sinonimo['termino_principal'];
                    $info_busqueda['mensaje'] = "Resultados para '{$sinonimo['termino_principal']}' (relacionado con '$busqueda')";
                    break;
                }
            }
            
            // PASO 3: Búsqueda por categorías relacionadas
            if (empty($tiendas)) {
                $categoria_relacionada = buscarCategoriaRelacionada($busqueda, $pdo);
                
                if ($categoria_relacionada) {
                    $tiendas = buscarPorCategoria($categoria_relacionada, $pdo);
                    if (!empty($tiendas)) {
                        $info_busqueda['tipo_busqueda'] = 'categoria_relacionada';
                        $info_busqueda['termino_usado'] = $categoria_relacionada;
                        $info_busqueda['mensaje'] = "Tiendas relacionadas con '$busqueda' en la categoría: $categoria_relacionada";
                    }
                }
            }
            
            // PASO 4: Búsqueda por etiquetas
            if (empty($tiendas)) {
                $tiendas = buscarPorEtiquetas($busqueda, $categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'etiquetas';
                    $info_busqueda['mensaje'] = "Tiendas etiquetadas con términos relacionados a '$busqueda'";
                }
            }
            
            // PASO 5: Búsqueda parcial/flexible
            if (empty($tiendas)) {
                $tiendas = busquedaFlexible($busqueda, $categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'flexible';
                    $info_busqueda['mensaje'] = "Resultados que contienen partes de '$busqueda'";
                }
            }
            
            // PASO 6: Búsqueda por similitud fonética
            if (empty($tiendas)) {
                $tiendas = busquedaSimilitud($busqueda, $categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'similitud';
                    $info_busqueda['mensaje'] = "Resultados similares a '$busqueda'";
                }
            }
            
            // PASO 7: Búsqueda por categorías populares (fallback inteligente)
            if (empty($tiendas)) {
                $tiendas = busquedaFallbackInteligente($busqueda, $categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'fallback_inteligente';
                    $info_busqueda['mensaje'] = "Te podría interesar (relacionado con '$busqueda')";
                }
            }
            
            // PASO 8: Último recurso - mostrar tiendas populares/destacadas
            if (empty($tiendas)) {
                $tiendas = busquedaUltimoRecurso($categoria, $pdo);
                if (!empty($tiendas)) {
                    $info_busqueda['tipo_busqueda'] = 'populares';
                    $info_busqueda['mensaje'] = "No encontramos '$busqueda', pero estas tiendas podrían interesarte";
                }
            }
        } else {
            $info_busqueda['mensaje'] = "Resultados directos para '$busqueda'";
        }
    }
    
    // Procesar tiendas para mostrar
    $tiendas_procesadas = [];
    $vendedores_unicos = [];
    $total_clics = 0;
    
    foreach ($tiendas as $tienda) {
        $tienda_procesada = procesarTienda($tienda, $mostrar_favoritos, $favoritos_usuario);
        $tiendas_procesadas[] = $tienda_procesada;
        
        $vendedores_unicos[$tienda['vendedor_id']] = true;
        $total_clics += (int)$tienda['clics'];
    }
    
    $estadisticas = [
        'total_tiendas' => count($tiendas_procesadas),
        'vendedores_unicos' => count($vendedores_unicos),
        'total_clics' => $total_clics
    ];
    
    return [
        'success' => true,
        'tiendas' => $tiendas_procesadas,
        'estadisticas' => $estadisticas,
        'info_busqueda' => $info_busqueda
    ];
}

function buscarDirecto($busqueda, $categoria, $pdo) {
    $sql = "
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
    ";
    
    $params = [];
    
    if (!empty($busqueda)) {
        $sql .= " AND (t.nombre_tienda LIKE ? OR t.descripcion LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }
    
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPorCategoria($categoria, $pdo) {
    if (empty($categoria)) {
        // Todas las tiendas
        $sql = "
            SELECT t.*, u.nombre as vendedor_nombre,
                   COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
                   COUNT(c.id) as total_calificaciones
            FROM tiendas t 
            INNER JOIN usuarios u ON t.vendedor_id = u.id 
            LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
            WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
            GROUP BY t.id, u.nombre 
            ORDER BY t.es_destacado DESC, t.fecha_registro DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        $sql = "
            SELECT t.*, u.nombre as vendedor_nombre,
                   COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
                   COUNT(c.id) as total_calificaciones
            FROM tiendas t 
            INNER JOIN usuarios u ON t.vendedor_id = u.id 
            LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
            WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1 AND t.categoria = ?
            GROUP BY t.id, u.nombre 
            ORDER BY t.es_destacado DESC, t.fecha_registro DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$categoria]);
    }
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerSinonimos($termino, $pdo) {
    $sql = "
        SELECT DISTINCT termino_principal, categoria 
        FROM sinonimos 
        WHERE sinonimo LIKE ? AND activo = 1
        ORDER BY categoria
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%"]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarCategoriaRelacionada($termino, $pdo) {
    $sql = "
        SELECT categoria 
        FROM categoria_terminos 
        WHERE termino_relacionado LIKE ? AND activo = 1
        ORDER BY relevancia DESC 
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$termino%"]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    return $resultado ? $resultado['categoria'] : null;
}

function buscarPorEtiquetas($busqueda, $categoria, $pdo) {
    $sql = "
        SELECT DISTINCT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        INNER JOIN tienda_etiquetas te ON t.id = te.tienda_id
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1 
        AND te.activo = 1 AND te.etiqueta LIKE ?
    ";
    
    $params = ["%$busqueda%"];
    
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function busquedaFlexible($busqueda, $categoria, $pdo) {
    // Dividir búsqueda en palabras
    $palabras = explode(' ', $busqueda);
    $condiciones = [];
    $params = [];
    
    foreach ($palabras as $palabra) {
        $palabra = trim($palabra);
        if (strlen($palabra) >= 3) { // Solo palabras de 3+ caracteres
            $condiciones[] = "(t.nombre_tienda LIKE ? OR t.descripcion LIKE ?)";
            $params[] = "%$palabra%";
            $params[] = "%$palabra%";
        }
    }
    
    if (empty($condiciones)) {
        return [];
    }
    
    $sql = "
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
        AND (" . implode(' OR ', $condiciones) . ")
    ";
    
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function busquedaSimilitud($busqueda, $categoria, $pdo) {
    // Buscar por similitud usando SOUNDEX y LIKE con wildcards más amplios
    $sql = "
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
        AND (
            SOUNDEX(t.nombre_tienda) = SOUNDEX(?) OR
            SOUNDEX(t.descripcion) = SOUNDEX(?) OR
            t.nombre_tienda LIKE ? OR
            t.descripcion LIKE ?
        )
    ";
    
    $params = [$busqueda, $busqueda, "%$busqueda%", "%$busqueda%"];
    
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function busquedaFallbackInteligente($busqueda, $categoria, $pdo) {
    // Mapeo inteligente de términos a categorías
    $mapeo_categorias = [
        // Anime/Entretenimiento
        'goku' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        'naruto' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        'anime' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        'manga' => ['Libros y Papelería', 'Ropa y Accesorios'],
        'dragon ball' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        'pokemon' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        'otaku' => ['Libros y Papelería', 'Ropa y Accesorios', 'Tecnología'],
        
        // Calzado
        'zapato' => ['Ropa y Accesorios'],
        'zapatos' => ['Ropa y Accesorios'],
        'tenis' => ['Ropa y Accesorios', 'Deportes'],
        'zapatillas' => ['Ropa y Accesorios', 'Deportes'],
        'botas' => ['Ropa y Accesorios'],
        'calzado' => ['Ropa y Accesorios'],
        'nike' => ['Ropa y Accesorios', 'Deportes'],
        'adidas' => ['Ropa y Accesorios', 'Deportes'],
        
        // Tecnología
        'gaming' => ['Tecnología'],
        'gamer' => ['Tecnología'],
        'videojuegos' => ['Tecnología'],
        'consola' => ['Tecnología'],
        'pc' => ['Tecnología'],
        'computadora' => ['Tecnología'],
        'laptop' => ['Tecnología'],
        'celular' => ['Tecnología'],
        'telefono' => ['Tecnología'],
        'iphone' => ['Tecnología'],
        'samsung' => ['Tecnología'],
        
        // Comida
        'comida' => ['Comida y Bebidas'],
        'restaurante' => ['Comida y Bebidas'],
        'pizza' => ['Comida y Bebidas'],
        'hamburguesa' => ['Comida y Bebidas'],
        'sushi' => ['Comida y Bebidas'],
        'tacos' => ['Comida y Bebidas'],
        'cafe' => ['Comida y Bebidas'],
        'desayuno' => ['Comida y Bebidas'],
        'almuerzo' => ['Comida y Bebidas'],
        'cena' => ['Comida y Bebidas'],
        
        // Belleza
        'belleza' => ['Salud y Belleza'],
        'maquillaje' => ['Salud y Belleza'],
        'skincare' => ['Salud y Belleza'],
        'peluqueria' => ['Salud y Belleza', 'Servicios'],
        'spa' => ['Salud y Belleza', 'Servicios'],
        'uñas' => ['Salud y Belleza'],
        'cabello' => ['Salud y Belleza'],
        
        // Ropa
        'ropa' => ['Ropa y Accesorios'],
        'moda' => ['Ropa y Accesorios'],
        'fashion' => ['Ropa y Accesorios'],
        'boutique' => ['Ropa y Accesorios'],
        'camisa' => ['Ropa y Accesorios'],
        'pantalon' => ['Ropa y Accesorios'],
        'vestido' => ['Ropa y Accesorios'],
        'jeans' => ['Ropa y Accesorios'],
        
        // Deportes
        'gym' => ['Deportes', 'Salud y Belleza'],
        'fitness' => ['Deportes', 'Salud y Belleza'],
        'deporte' => ['Deportes'],
        'ejercicio' => ['Deportes', 'Salud y Belleza'],
        'futbol' => ['Deportes'],
        'basketball' => ['Deportes'],
        
        // Hogar
        'casa' => ['Hogar y Jardín'],
        'hogar' => ['Hogar y Jardín'],
        'muebles' => ['Hogar y Jardín'],
        'decoracion' => ['Hogar y Jardín'],
        'jardin' => ['Hogar y Jardín'],
        'plantas' => ['Hogar y Jardín'],
        
        // Servicios
        'reparacion' => ['Servicios'],
        'mantenimiento' => ['Servicios'],
        'limpieza' => ['Servicios'],
        'delivery' => ['Servicios'],
        'domicilio' => ['Servicios'],
        'transporte' => ['Servicios']
    ];
    
    $busqueda_lower = strtolower($busqueda);
    $categorias_relacionadas = [];
    
    // Buscar coincidencias exactas
    if (isset($mapeo_categorias[$busqueda_lower])) {
        $categorias_relacionadas = $mapeo_categorias[$busqueda_lower];
    } else {
        // Buscar coincidencias parciales
        foreach ($mapeo_categorias as $termino => $cats) {
            if (strpos($busqueda_lower, $termino) !== false || strpos($termino, $busqueda_lower) !== false) {
                $categorias_relacionadas = array_merge($categorias_relacionadas, $cats);
            }
        }
        
        // Remover duplicados
        $categorias_relacionadas = array_unique($categorias_relacionadas);
    }
    
    // Buscar tiendas en las categorías relacionadas
    if (!empty($categorias_relacionadas)) {
        $placeholders = str_repeat('?,', count($categorias_relacionadas) - 1) . '?';
        $sql = "
            SELECT t.*, u.nombre as vendedor_nombre,
                   COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
                   COUNT(c.id) as total_calificaciones
            FROM tiendas t 
            INNER JOIN usuarios u ON t.vendedor_id = u.id 
            LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
            WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1 
            AND t.categoria IN ($placeholders)
        ";
        
        if (!empty($categoria)) {
            $sql .= " AND t.categoria = ?";
            $categorias_relacionadas[] = $categoria;
        }
        
        $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.fecha_registro DESC LIMIT 15";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($categorias_relacionadas);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return [];
        }
    }
    
    return [];
}

function busquedaUltimoRecurso($categoria, $pdo) {
    // Mostrar tiendas destacadas como último recurso
    $sql = "
        SELECT t.*, u.nombre as vendedor_nombre,
               COALESCE(AVG(c.estrellas), 0) as promedio_estrellas,
               COUNT(c.id) as total_calificaciones
        FROM tiendas t 
        INNER JOIN usuarios u ON t.vendedor_id = u.id 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.activo = 1 AND t.estado = 1 AND u.activo = 1
    ";
    
    $params = [];
    
    if (!empty($categoria)) {
        $sql .= " AND t.categoria = ?";
        $params[] = $categoria;
    }
    
    $sql .= " GROUP BY t.id, u.nombre ORDER BY t.es_destacado DESC, t.clics DESC LIMIT 12";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function procesarTienda($tienda, $mostrar_favoritos, $favoritos_usuario) {
    // Función para mostrar estrellas
    function mostrar_estrellas($promedio, $total_calificaciones = 0) {
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
    
    // Generar botón de favoritos si es necesario
    $boton_favorito = '';
    if ($mostrar_favoritos) {
        $es_favorito = in_array($tienda['id'], $favoritos_usuario);
        $clase_btn = $es_favorito ? 'btn-danger' : 'btn-outline-danger';
        $icono = $es_favorito ? 'bi-heart-fill' : 'bi-heart';
        $titulo = $es_favorito ? 'Quitar de favoritos' : 'Agregar a favoritos';
        
        $boton_favorito = '<button type="button" class="btn ' . $clase_btn . ' btn-favorito" data-tienda-id="' . $tienda['id'] . '" data-es-favorito="' . ($es_favorito ? 'true' : 'false') . '" title="' . $titulo . '"><i class="bi ' . $icono . '"></i></button>';
    }
    
    return [
        'id' => $tienda['id'],
        'nombre_tienda' => $tienda['nombre_tienda'],
        'descripcion' => $tienda['descripcion'],
        'logo' => $tienda['logo'],
        'categoria' => $tienda['categoria'],
        'clics' => $tienda['clics'],
        'es_destacado' => (bool)$tienda['es_destacado'],
        'vendedor_nombre' => $tienda['vendedor_nombre'],
        'fecha_registro' => date('d/m/Y', strtotime($tienda['fecha_registro'])),
        'estrellas_html' => mostrar_estrellas($tienda['promedio_estrellas'], $tienda['total_calificaciones']),
        'boton_favorito' => $boton_favorito,
        'mostrar_favoritos' => $mostrar_favoritos
    ];
}
?>