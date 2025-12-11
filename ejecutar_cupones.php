<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Instalación Sistema de Cupones</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 20px 0; }
        .btn { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>";

echo "<h2>Instalación del Sistema de Cupones y Ofertas Premium</h2>";
echo "<hr>";

try {
    $sql = file_get_contents('crear_tabla_cupones.sql');
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo crear_tabla_cupones.sql");
    }
    
    $pdo->exec($sql);
    
    echo "<div class='success'>";
    echo "<strong>✓ Éxito:</strong> La tabla 'cupones_ofertas' ha sido creada correctamente.<br>";
    echo "El sistema de cupones está listo para usar.";
    echo "</div>";
    
    echo "<h3>Características del Sistema:</h3>";
    echo "<ul>";
    echo "<li>✅ Vendedores Premium pueden crear ofertas ilimitadas</li>";
    echo "<li>✅ Cupones con fecha de expiración</li>";
    echo "<li>✅ Se muestran en el perfil de la tienda</li>";
    echo "<li>✅ Página central de todas las ofertas</li>";
    echo "<li>✅ Gestión completa desde el panel</li>";
    echo "</ul>";
    
    echo "<p><a href='mis_ofertas.php' class='btn'>Ir a Mis Ofertas</a></p>";
    echo "<p><a href='ofertas.php' class='btn' style='background: #007bff;'>Ver Todas las Ofertas</a></p>";
    
} catch(Exception $e) {
    echo "<div class='error'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";
?>
