<?php
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Instalación Sistema de Respuestas</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #d4edda; padding: 15px; border-radius: 8px; color: #155724; margin: 20px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 8px; color: #721c24; margin: 20px 0; }
        .btn { background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>";

echo "<h2>Instalación del Sistema de Respuestas a Reseñas</h2>";
echo "<hr>";

try {
    $sql = file_get_contents('agregar_respuestas_resenas.sql');
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo agregar_respuestas_resenas.sql");
    }
    
    $pdo->exec($sql);
    
    echo "<div class='success'>";
    echo "<strong>✓ Éxito:</strong> Las columnas 'respuesta_vendedor' y 'fecha_respuesta' han sido agregadas.<br>";
    echo "Los vendedores Premium ahora pueden responder a las reseñas.";
    echo "</div>";
    
    echo "<h3>Características:</h3>";
    echo "<ul>";
    echo "<li>✅ Vendedores Premium pueden responder reseñas</li>";
    echo "<li>✅ Respuestas visibles públicamente</li>";
    echo "<li>✅ Gestión desde el panel del vendedor</li>";
    echo "<li>✅ Mejora la reputación y engagement</li>";
    echo "</ul>";
    
    echo "<p><a href='gestionar_resenas.php' class='btn'>Ir a Gestionar Reseñas</a></p>";
    
} catch(Exception $e) {
    echo "<div class='error'>";
    echo "<strong>✗ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";
?>
