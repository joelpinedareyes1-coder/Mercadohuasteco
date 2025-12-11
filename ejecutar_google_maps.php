<?php
require_once 'config.php';

echo "<h2>Instalación de Google Maps para Tiendas Premium</h2>";
echo "<hr>";

try {
    // Leer el archivo SQL
    $sql = file_get_contents('agregar_google_maps.sql');
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo agregar_google_maps.sql");
    }
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
    echo "<strong>✓ Éxito:</strong> La columna 'google_maps_src' ha sido agregada a la tabla 'tiendas'.<br>";
    echo "Las tiendas Premium ahora pueden agregar mapas de Google Maps a sus perfiles.";
    echo "</div>";
    
    echo "<h3>Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Los vendedores Premium podrán agregar su mapa desde el panel</li>";
    echo "<li>El mapa aparecerá automáticamente en su perfil de tienda</li>";
    echo "<li>Solo visible para tiendas Premium</li>";
    echo "</ol>";
    
    echo "<p><a href='panel_vendedor.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px;'>Ir al Panel de Vendedor</a></p>";
    
} catch(Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 20px 0;'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
