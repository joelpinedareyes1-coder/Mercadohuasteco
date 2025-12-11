<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    die("Solo administradores pueden ejecutar este script");
}

echo "<h2>Sincronizando estado Premium con tiendas...</h2>";

try {
    // Obtener todos los vendedores
    $stmt = $pdo->query("SELECT id, nombre, es_premium FROM usuarios WHERE rol = 'vendedor'");
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach ($vendedores as $vendedor) {
        // Actualizar su tienda
        $stmt_update = $pdo->prepare("UPDATE tiendas SET es_destacado = ? WHERE vendedor_id = ?");
        $stmt_update->execute([$vendedor['es_premium'], $vendedor['id']]);
        
        $filas = $stmt_update->rowCount();
        $estado = $vendedor['es_premium'] ? 'Premium (Destacado)' : 'Normal';
        
        echo "<li>Vendedor: {$vendedor['nombre']} - Estado: $estado - Tiendas actualizadas: $filas</li>";
    }
    echo "</ul>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ Sincronización completada!</p>";
    echo "<p><a href='gestionar_usuarios.php'>Volver a Gestionar Usuarios</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
