<?php
require_once 'config.php';

echo "<h2>🎥 Instalación de Video Premium</h2>";

// 1. Verificar si ya existe la columna
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM tiendas LIKE 'link_video'");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "<p>✅ La columna 'link_video' ya existe. No es necesario ejecutar el script.</p>";
    } else {
        echo "<p>⚙️ Agregando columna 'link_video' a la tabla tiendas...</p>";
        
        // Ejecutar el ALTER TABLE
        $pdo->exec("ALTER TABLE tiendas ADD COLUMN link_video VARCHAR(500) DEFAULT NULL AFTER logo");
        echo "<p>✅ Columna 'link_video' agregada exitosamente</p>";
        
        // Crear índice
        $pdo->exec("CREATE INDEX idx_link_video ON tiendas(link_video)");
        echo "<p>✅ Índice creado exitosamente</p>";
    }
} catch(PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ Instalación Completa</h3>";
echo "<p>El sistema de Video Premium está listo para usar.</p>";
echo "<p><strong>Próximos pasos:</strong></p>";
echo "<ol>";
echo "<li>Los vendedores Premium pueden agregar su video de YouTube/Vimeo desde el Panel del Vendedor</li>";
echo "<li>El video aparecerá automáticamente en su página de tienda</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php'>← Volver al inicio</a></p>";
?>
