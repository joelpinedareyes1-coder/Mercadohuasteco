<?php
/**
 * Script de Prueba del Sistema de Reportes
 * Verifica que todos los componentes estén funcionando correctamente
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test Sistema de Reportes</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .test-card { margin-bottom: 20px; }
        .test-success { background: #d4edda; border-left: 4px solid #28a745; }
        .test-error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .test-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <div class='container'>
        <h1 class='mb-4'><i class='bi bi-clipboard-check me-2'></i>Test del Sistema de Reportes</h1>
        <p class='lead'>Verificando componentes del sistema...</p>
        <hr>";

$tests_passed = 0;
$tests_failed = 0;
$tests_total = 0;

// Test 1: Verificar que la tabla existe
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-1-circle me-2'></i>Test 1: Verificar tabla 'reportes_tienda'</h5>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'reportes_tienda'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> La tabla 'reportes_tienda' existe.</div>";
        $tests_passed++;
    } else {
        echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ FALLÓ:</strong> La tabla 'reportes_tienda' NO existe. Ejecuta 'ejecutar_crear_reportes.php'</div>";
        $tests_failed++;
    }
} catch(PDOException $e) {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ ERROR:</strong> " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div></div>";

// Test 2: Verificar estructura de la tabla
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-2-circle me-2'></i>Test 2: Verificar estructura de la tabla</h5>";
try {
    $stmt = $pdo->query("DESCRIBE reportes_tienda");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['id', 'id_tienda', 'id_usuario_reporta', 'motivo', 'estado', 'fecha_reporte', 'fecha_resolucion', 'notas_admin'];
    $existing_columns = array_column($columns, 'Field');
    
    $missing_columns = array_diff($required_columns, $existing_columns);
    
    if (empty($missing_columns)) {
        echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> Todas las columnas requeridas existen.</div>";
        echo "<small class='text-muted'>Columnas: " . implode(', ', $existing_columns) . "</small>";
        $tests_passed++;
    } else {
        echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ FALLÓ:</strong> Faltan columnas: " . implode(', ', $missing_columns) . "</div>";
        $tests_failed++;
    }
} catch(PDOException $e) {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ ERROR:</strong> " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div></div>";

// Test 3: Verificar archivos PHP
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-3-circle me-2'></i>Test 3: Verificar archivos PHP</h5>";

$required_files = [
    'procesar_reporte.php' => 'Procesador de reportes',
    'admin_ver_reportes.php' => 'Panel de administración',
    'ejecutar_crear_reportes.php' => 'Instalador',
    'crear_tabla_reportes.sql' => 'Script SQL'
];

$missing_files = [];
foreach ($required_files as $file => $description) {
    if (!file_exists($file)) {
        $missing_files[] = "$file ($description)";
    }
}

if (empty($missing_files)) {
    echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> Todos los archivos necesarios existen.</div>";
    echo "<ul class='small text-muted mb-0'>";
    foreach ($required_files as $file => $description) {
        echo "<li>$file - $description</li>";
    }
    echo "</ul>";
    $tests_passed++;
} else {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ FALLÓ:</strong> Faltan archivos:</div>";
    echo "<ul>";
    foreach ($missing_files as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    $tests_failed++;
}
echo "</div></div>";

// Test 4: Verificar que tienda_detalle.php tiene el modal
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-4-circle me-2'></i>Test 4: Verificar modal en tienda_detalle.php</h5>";

if (file_exists('tienda_detalle.php')) {
    $content = file_get_contents('tienda_detalle.php');
    $has_modal = strpos($content, 'modal-reporte') !== false;
    $has_function = strpos($content, 'reportarTienda') !== false;
    $has_form = strpos($content, 'procesar_reporte.php') !== false;
    
    if ($has_modal && $has_function && $has_form) {
        echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> El modal y la función están implementados correctamente.</div>";
        $tests_passed++;
    } else {
        echo "<div class='alert test-warning'><i class='bi bi-exclamation-triangle me-2'></i><strong>⚠ ADVERTENCIA:</strong> Algunos componentes pueden faltar:</div>";
        echo "<ul>";
        echo "<li>Modal: " . ($has_modal ? '✓' : '✗') . "</li>";
        echo "<li>Función JS: " . ($has_function ? '✓' : '✗') . "</li>";
        echo "<li>Formulario: " . ($has_form ? '✓' : '✗') . "</li>";
        echo "</ul>";
        $tests_failed++;
    }
} else {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ FALLÓ:</strong> El archivo tienda_detalle.php no existe.</div>";
    $tests_failed++;
}
echo "</div></div>";

// Test 5: Verificar foreign keys
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-5-circle me-2'></i>Test 5: Verificar foreign keys</h5>";
try {
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'reportes_tienda'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $foreign_keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($foreign_keys) >= 2) {
        echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> Las foreign keys están configuradas.</div>";
        echo "<ul class='small text-muted mb-0'>";
        foreach ($foreign_keys as $fk) {
            echo "<li>{$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}</li>";
        }
        echo "</ul>";
        $tests_passed++;
    } else {
        echo "<div class='alert test-warning'><i class='bi bi-exclamation-triangle me-2'></i><strong>⚠ ADVERTENCIA:</strong> Faltan foreign keys.</div>";
        $tests_failed++;
    }
} catch(PDOException $e) {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ ERROR:</strong> " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div></div>";

// Test 6: Contar reportes existentes
$tests_total++;
echo "<div class='card test-card'>";
echo "<div class='card-body'>";
echo "<h5><i class='bi bi-6-circle me-2'></i>Test 6: Verificar datos en la tabla</h5>";
try {
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'resuelto' THEN 1 ELSE 0 END) as resueltos
        FROM reportes_tienda
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='alert test-success'><i class='bi bi-check-circle me-2'></i><strong>✓ PASÓ:</strong> La tabla es accesible.</div>";
    echo "<div class='row text-center'>";
    echo "<div class='col-md-4'><h3>{$stats['total']}</h3><p class='text-muted'>Total</p></div>";
    echo "<div class='col-md-4'><h3 class='text-warning'>{$stats['pendientes']}</h3><p class='text-muted'>Pendientes</p></div>";
    echo "<div class='col-md-4'><h3 class='text-success'>{$stats['resueltos']}</h3><p class='text-muted'>Resueltos</p></div>";
    echo "</div>";
    $tests_passed++;
} catch(PDOException $e) {
    echo "<div class='alert test-error'><i class='bi bi-x-circle me-2'></i><strong>✗ ERROR:</strong> " . $e->getMessage() . "</div>";
    $tests_failed++;
}
echo "</div></div>";

// Resumen final
echo "<hr>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h3><i class='bi bi-clipboard-data me-2'></i>Resumen de Tests</h3>";

$percentage = ($tests_passed / $tests_total) * 100;
$status_class = $percentage == 100 ? 'success' : ($percentage >= 70 ? 'warning' : 'danger');

echo "<div class='row text-center mb-3'>";
echo "<div class='col-md-4'><h2 class='text-primary'>$tests_total</h2><p>Total de Tests</p></div>";
echo "<div class='col-md-4'><h2 class='text-success'>$tests_passed</h2><p>Tests Pasados</p></div>";
echo "<div class='col-md-4'><h2 class='text-danger'>$tests_failed</h2><p>Tests Fallidos</p></div>";
echo "</div>";

echo "<div class='progress mb-3' style='height: 30px;'>";
echo "<div class='progress-bar bg-$status_class' role='progressbar' style='width: $percentage%' aria-valuenow='$percentage' aria-valuemin='0' aria-valuemax='100'>";
echo "<strong>" . round($percentage, 1) . "%</strong>";
echo "</div>";
echo "</div>";

if ($percentage == 100) {
    echo "<div class='alert alert-success text-center'>";
    echo "<h4><i class='bi bi-check-circle-fill me-2'></i>¡Todos los tests pasaron!</h4>";
    echo "<p class='mb-0'>El sistema de reportes está completamente funcional y listo para usar.</p>";
    echo "</div>";
    echo "<div class='text-center'>";
    echo "<a href='admin_ver_reportes.php' class='btn btn-primary btn-lg me-2'><i class='bi bi-eye me-2'></i>Ver Panel de Reportes</a>";
    echo "<a href='directorio.php' class='btn btn-success btn-lg'><i class='bi bi-shop me-2'></i>Ir al Directorio</a>";
    echo "</div>";
} else if ($percentage >= 70) {
    echo "<div class='alert alert-warning text-center'>";
    echo "<h4><i class='bi bi-exclamation-triangle-fill me-2'></i>Sistema parcialmente funcional</h4>";
    echo "<p class='mb-0'>Algunos componentes necesitan atención. Revisa los tests fallidos arriba.</p>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger text-center'>";
    echo "<h4><i class='bi bi-x-circle-fill me-2'></i>Sistema no funcional</h4>";
    echo "<p class='mb-0'>Hay problemas críticos. Por favor ejecuta 'ejecutar_crear_reportes.php' primero.</p>";
    echo "</div>";
    echo "<div class='text-center'>";
    echo "<a href='ejecutar_crear_reportes.php' class='btn btn-danger btn-lg'><i class='bi bi-tools me-2'></i>Ejecutar Instalador</a>";
    echo "</div>";
}

echo "</div></div>";

echo "<div class='mt-4 text-center text-muted'>";
echo "<small>Test ejecutado el " . date('d/m/Y H:i:s') . "</small>";
echo "</div>";

echo "</div>
</body>
</html>";
?>
