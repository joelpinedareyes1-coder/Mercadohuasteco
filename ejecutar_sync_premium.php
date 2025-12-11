<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    die("❌ Solo administradores pueden ejecutar este script");
}

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Sincronizar Premium</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #17a2b8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #28a745;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .premium {
            background: #fff3cd;
            font-weight: bold;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔄 Sincronización de Estado Premium</h1>";

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Actualizar todas las tiendas según el estado Premium de sus vendedores
    $stmt_update = $pdo->prepare("
        UPDATE tiendas t
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        SET t.es_destacado = u.es_premium
        WHERE u.rol = 'vendedor'
    ");
    $stmt_update->execute();
    $filas_actualizadas = $stmt_update->rowCount();
    
    $pdo->commit();
    
    echo "<div class='success'>";
    echo "<strong>✅ Sincronización completada exitosamente!</strong><br>";
    echo "Se actualizaron <strong>$filas_actualizadas</strong> tiendas.";
    echo "</div>";
    
    // Mostrar estado actual
    echo "<div class='info'>";
    echo "<strong>📊 Estado actual de vendedores y tiendas:</strong>";
    echo "</div>";
    
    $stmt = $pdo->query("
        SELECT 
            u.id as usuario_id,
            u.nombre as vendedor,
            u.es_premium,
            t.id as tienda_id,
            t.nombre_tienda as tienda,
            t.es_destacado
        FROM usuarios u
        LEFT JOIN tiendas t ON u.id = t.vendedor_id
        WHERE u.rol = 'vendedor'
        ORDER BY u.es_premium DESC, u.nombre
    ");
    
    echo "<table>";
    echo "<tr>
            <th>Vendedor</th>
            <th>Estado Usuario</th>
            <th>Tienda</th>
            <th>Estado Tienda</th>
            <th>Sincronizado</th>
          </tr>";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $es_premium = $row['es_premium'] ? 'Premium ⭐' : 'Normal';
        $es_destacado = $row['es_destacado'] ? 'Destacada ⭐' : 'Normal';
        $sincronizado = ($row['es_premium'] == $row['es_destacado']) ? '✅ Sí' : '❌ No';
        $class = $row['es_premium'] ? 'premium' : '';
        
        echo "<tr class='$class'>";
        echo "<td>{$row['vendedor']}</td>";
        echo "<td>$es_premium</td>";
        echo "<td>" . ($row['tienda'] ?? 'Sin tienda') . "</td>";
        echo "<td>$es_destacado</td>";
        echo "<td>$sincronizado</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Estadísticas
    $stmt_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_vendedores,
            SUM(u.es_premium) as vendedores_premium,
            COUNT(t.id) as total_tiendas,
            SUM(t.es_destacado) as tiendas_destacadas
        FROM usuarios u
        LEFT JOIN tiendas t ON u.id = t.vendedor_id
        WHERE u.rol = 'vendedor'
    ");
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<strong>📈 Estadísticas:</strong><br>";
    echo "• Total de vendedores: {$stats['total_vendedores']}<br>";
    echo "• Vendedores Premium: {$stats['vendedores_premium']}<br>";
    echo "• Total de tiendas: {$stats['total_tiendas']}<br>";
    echo "• Tiendas destacadas: {$stats['tiendas_destacadas']}<br>";
    echo "</div>";
    
} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<strong>❌ Error:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<a href='gestionar_usuarios.php' class='btn'>← Volver a Gestionar Usuarios</a>";
echo "<a href='directorio.php' class='btn' style='background: #28a745; margin-left: 10px;'>Ver Directorio</a>";

echo "</div></body></html>";
?>
