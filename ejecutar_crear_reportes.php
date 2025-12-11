<?php
require_once 'config.php';

echo "<h2>Instalación del Sistema de Reportes de Tiendas</h2>";
echo "<hr>";

try {
    // Leer el archivo SQL
    $sql = file_get_contents('crear_tabla_reportes.sql');
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo crear_tabla_reportes.sql");
    }
    
    // Ejecutar el SQL
    $pdo->exec($sql);
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0;'>";
    echo "<strong>✓ Éxito:</strong> La tabla 'reportes_tienda' ha sido creada correctamente.<br>";
    echo "El sistema de reportes está listo para usar.";
    echo "</div>";
    
    echo "<h3>Próximos pasos:</h3>";
    echo "<ol>";
    echo "<li>Los usuarios podrán reportar tiendas desde el botón de la bandera</li>";
    echo "<li>Los reportes se guardarán en la base de datos</li>";
    echo "<li>Puedes ver y gestionar los reportes en: <a href='admin_ver_reportes.php'>admin_ver_reportes.php</a></li>";
    echo "</ol>";
    
    echo "<p><a href='admin_ver_reportes.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px;'>Ir al Panel de Reportes</a></p>";
    
} catch(Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 20px 0;'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
