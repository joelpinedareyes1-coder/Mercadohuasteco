<?php
require_once 'config.php';

// Obtener ID de la tienda desde la URL
$tienda_id = isset($_GET['id']) ? (int)$_GET['id'] : 9;

echo "<h2>Debug de Calificaciones - Tienda ID: $tienda_id</h2>";

// 1. Verificar todas las calificaciones (sin filtros)
echo "<h3>1. Todas las calificaciones (sin filtros):</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM calificaciones WHERE tienda_id = ?");
    $stmt->execute([$tienda_id]);
    $todas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($todas);
    echo "</pre>";
    echo "<p><strong>Total: " . count($todas) . " calificaciones</strong></p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 2. Calificaciones activas
echo "<h3>2. Calificaciones activas (activo = 1):</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM calificaciones WHERE tienda_id = ? AND activo = 1");
    $stmt->execute([$tienda_id]);
    $activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($activas);
    echo "</pre>";
    echo "<p><strong>Total: " . count($activas) . " calificaciones activas</strong></p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 3. Calificaciones aprobadas y activas (como en tienda_detalle.php)
echo "<h3>3. Calificaciones aprobadas y activas (activo = 1 AND esta_aprobada = 1):</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as usuario_nombre
        FROM calificaciones c
        INNER JOIN usuarios u ON c.user_id = u.id
        WHERE c.tienda_id = ? AND c.activo = 1 AND c.esta_aprobada = 1
        ORDER BY c.fecha_calificacion DESC
    ");
    $stmt->execute([$tienda_id]);
    $aprobadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($aprobadas);
    echo "</pre>";
    echo "<p><strong>Total: " . count($aprobadas) . " calificaciones aprobadas y activas</strong></p>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// 4. Verificar estructura de la tabla
echo "<h3>4. Estructura de la tabla calificaciones:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE calificaciones");
    $estructura = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($estructura as $campo) {
        echo "<tr>";
        echo "<td>" . $campo['Field'] . "</td>";
        echo "<td>" . $campo['Type'] . "</td>";
        echo "<td>" . $campo['Null'] . "</td>";
        echo "<td>" . $campo['Key'] . "</td>";
        echo "<td>" . $campo['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='tienda_detalle.php?id=$tienda_id'>Volver a la tienda</a></p>";
?>
