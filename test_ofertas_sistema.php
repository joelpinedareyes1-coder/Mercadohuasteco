<?php
/**
 * SCRIPT DE PRUEBA - Sistema de Ofertas Públicas
 * Verifica que la consulta SQL funcione correctamente
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test - Sistema de Ofertas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .test-card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        .warning { color: #ffc107; }
        pre { background: #f4f4f4; padding: 1rem; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'><i class='fas fa-vial me-2'></i>Test - Sistema de Ofertas Públicas</h1>";

// ============================================
// TEST 1: Verificar tabla cupones_ofertas
// ============================================
echo "<div class='test-card'>
        <h3><i class='fas fa-database me-2'></i>Test 1: Verificar Tabla cupones_ofertas</h3>";

try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'cupones_ofertas'");
    $tabla_existe = $stmt->rowCount() > 0;
    
    if ($tabla_existe) {
        echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ Tabla 'cupones_ofertas' existe</p>";
        
        // Verificar estructura
        $stmt = $pdo->query("DESCRIBE cupones_ofertas");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p class='info'><strong>Columnas encontradas:</strong></p>";
        echo "<pre>";
        foreach ($columnas as $col) {
            echo "- {$col['Field']} ({$col['Type']})\n";
        }
        echo "</pre>";
        
        // Contar ofertas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM cupones_ofertas");
        $total = $stmt->fetch()['total'];
        echo "<p class='info'><i class='fas fa-info-circle me-2'></i>Total de ofertas en la tabla: <strong>$total</strong></p>";
        
    } else {
        echo "<p class='error'><i class='fas fa-times-circle me-2'></i>❌ Tabla 'cupones_ofertas' NO existe</p>";
        echo "<p class='warning'>⚠️ Ejecuta el archivo 'crear_tabla_cupones.sql' primero</p>";
    }
} catch(PDOException $e) {
    echo "<p class='error'><i class='fas fa-exclamation-triangle me-2'></i>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================
// TEST 2: Verificar consulta de ofertas activas
// ============================================
echo "<div class='test-card'>
        <h3><i class='fas fa-search me-2'></i>Test 2: Consulta de Ofertas Activas</h3>";

try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.titulo,
            c.descripcion,
            c.fecha_expiracion,
            c.fecha_inicio,
            c.estado,
            t.id as tienda_id,
            t.nombre_tienda,
            t.logo,
            t.categoria,
            u.es_premium,
            u.nombre as vendedor_nombre
        FROM cupones_ofertas c
        INNER JOIN tiendas t ON c.id_tienda = t.id
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        WHERE c.estado = 'activo'
        AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion >= CURDATE())
        AND t.activo = 1
        AND u.es_premium = 1
        ORDER BY c.id DESC
    ");
    $stmt->execute();
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ Consulta ejecutada correctamente</p>";
    echo "<p class='info'><i class='fas fa-tags me-2'></i>Ofertas activas encontradas: <strong>" . count($ofertas) . "</strong></p>";
    
    if (count($ofertas) > 0) {
        echo "<h5 class='mt-4'>Ofertas Encontradas:</h5>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr>
                <th>ID</th>
                <th>Título</th>
                <th>Tienda</th>
                <th>Categoría</th>
                <th>Premium</th>
                <th>Expira</th>
                <th>Estado</th>
              </tr></thead><tbody>";
        
        foreach ($ofertas as $oferta) {
            $premium_badge = $oferta['es_premium'] ? '<span class="badge bg-warning">Premium</span>' : '<span class="badge bg-secondary">No</span>';
            $fecha_exp = $oferta['fecha_expiracion'] ? date('d/m/Y', strtotime($oferta['fecha_expiracion'])) : 'Sin límite';
            
            echo "<tr>
                    <td>{$oferta['id']}</td>
                    <td>{$oferta['titulo']}</td>
                    <td>{$oferta['nombre_tienda']}</td>
                    <td>{$oferta['categoria']}</td>
                    <td>$premium_badge</td>
                    <td>$fecha_exp</td>
                    <td><span class='badge bg-success'>{$oferta['estado']}</span></td>
                  </tr>";
        }
        
        echo "</tbody></table></div>";
    } else {
        echo "<p class='warning'><i class='fas fa-exclamation-triangle me-2'></i>⚠️ No hay ofertas activas de tiendas Premium</p>";
        echo "<p class='info'>Para probar el sistema, necesitas:</p>";
        echo "<ol>
                <li>Una tienda con usuario Premium (es_premium = 1)</li>
                <li>Al menos una oferta activa en cupones_ofertas</li>
                <li>La fecha de expiración debe ser futura o NULL</li>
              </ol>";
    }
    
} catch(PDOException $e) {
    echo "<p class='error'><i class='fas fa-exclamation-triangle me-2'></i>Error en consulta: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================
// TEST 3: Verificar tiendas Premium
// ============================================
echo "<div class='test-card'>
        <h3><i class='fas fa-crown me-2'></i>Test 3: Tiendas Premium Disponibles</h3>";

try {
    $stmt = $pdo->query("
        SELECT u.id, u.nombre, u.es_premium, t.id as tienda_id, t.nombre_tienda
        FROM usuarios u
        LEFT JOIN tiendas t ON u.id = t.vendedor_id
        WHERE u.rol = 'vendedor' AND u.es_premium = 1 AND u.activo = 1
    ");
    $premium_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'><i class='fas fa-users me-2'></i>Vendedores Premium encontrados: <strong>" . count($premium_users) . "</strong></p>";
    
    if (count($premium_users) > 0) {
        echo "<div class='table-responsive'>";
        echo "<table class='table table-striped'>";
        echo "<thead><tr>
                <th>Usuario ID</th>
                <th>Nombre Vendedor</th>
                <th>Tienda ID</th>
                <th>Nombre Tienda</th>
              </tr></thead><tbody>";
        
        foreach ($premium_users as $user) {
            echo "<tr>
                    <td>{$user['id']}</td>
                    <td>{$user['nombre']}</td>
                    <td>" . ($user['tienda_id'] ?? 'Sin tienda') . "</td>
                    <td>" . ($user['nombre_tienda'] ?? 'N/A') . "</td>
                  </tr>";
        }
        
        echo "</tbody></table></div>";
    } else {
        echo "<p class='warning'><i class='fas fa-exclamation-triangle me-2'></i>⚠️ No hay vendedores Premium en el sistema</p>";
        echo "<p class='info'>Para activar Premium en un usuario:</p>";
        echo "<pre>UPDATE usuarios SET es_premium = 1 WHERE id = [ID_USUARIO];</pre>";
    }
    
} catch(PDOException $e) {
    echo "<p class='error'><i class='fas fa-exclamation-triangle me-2'></i>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// ============================================
// TEST 4: Verificar archivo ofertas.php
// ============================================
echo "<div class='test-card'>
        <h3><i class='fas fa-file-code me-2'></i>Test 4: Verificar Archivos del Sistema</h3>";

$archivos = [
    'ofertas.php' => 'Página principal de ofertas',
    'css/ofertas-styles.css' => 'Estilos de la página',
    'includes/header.php' => 'Header con enlace a ofertas'
];

foreach ($archivos as $archivo => $descripcion) {
    if (file_exists($archivo)) {
        echo "<p class='success'><i class='fas fa-check-circle me-2'></i>✅ $archivo - $descripcion</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times-circle me-2'></i>❌ $archivo - NO ENCONTRADO</p>";
    }
}

echo "</div>";

// ============================================
// RESUMEN Y ACCIONES
// ============================================
echo "<div class='test-card' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;'>
        <h3><i class='fas fa-clipboard-check me-2'></i>Resumen y Próximos Pasos</h3>";

if (count($ofertas ?? []) > 0) {
    echo "<p class='mb-3'><strong>✅ ¡Sistema funcionando correctamente!</strong></p>";
    echo "<p>Puedes acceder a la página de ofertas en:</p>";
    echo "<p><a href='ofertas.php' class='btn btn-light' target='_blank'>
            <i class='fas fa-external-link-alt me-2'></i>Ver Página de Ofertas
          </a></p>";
} else {
    echo "<p class='mb-3'><strong>⚠️ Sistema instalado pero sin datos de prueba</strong></p>";
    echo "<p>Para probar el sistema completo:</p>";
    echo "<ol>
            <li>Asegúrate de tener al menos un vendedor Premium</li>
            <li>Crea ofertas desde el panel del vendedor</li>
            <li>Las ofertas aparecerán automáticamente en ofertas.php</li>
          </ol>";
}

echo "<hr style='border-color: rgba(255,255,255,0.3);'>";
echo "<p><strong>Enlaces útiles:</strong></p>";
echo "<ul>
        <li><a href='index.php' class='text-white'>Inicio</a></li>
        <li><a href='directorio.php' class='text-white'>Directorio</a></li>
        <li><a href='ofertas.php' class='text-white'>Ofertas</a></li>
        <li><a href='SISTEMA_OFERTAS_PUBLICAS.md' class='text-white'>Documentación</a></li>
      </ul>";

echo "</div>";

echo "    </div>
</body>
</html>";
?>
