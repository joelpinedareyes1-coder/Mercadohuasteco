<?php
require_once 'config.php';

echo "<h2>📊 Instalación de Sistema de Estadísticas</h2>";

// 1. Verificar si ya existe la tabla
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'visitas_tienda'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "<p>✅ La tabla 'visitas_tienda' ya existe.</p>";
    } else {
        echo "<p>⚙️ Creando tabla 'visitas_tienda'...</p>";
        
        // Crear tabla
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS visitas_tienda (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tienda_id INT NOT NULL,
                fecha_visita DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_visitante VARCHAR(45) NULL,
                user_agent TEXT NULL,
                INDEX idx_tienda_fecha (tienda_id, fecha_visita),
                INDEX idx_fecha (fecha_visita),
                FOREIGN KEY (tienda_id) REFERENCES tiendas(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p>✅ Tabla 'visitas_tienda' creada exitosamente</p>";
        
        // Contar tiendas con visitas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM tiendas WHERE clics > 0");
        $result = $stmt->fetch();
        $tiendas_con_visitas = $result['total'];
        
        if ($tiendas_con_visitas > 0) {
            echo "<p>⚙️ Migrando datos históricos aproximados...</p>";
            echo "<p>ℹ️ Se crearán registros de visitas distribuidos en los últimos 30 días</p>";
            
            // Migrar datos existentes (crear visitas distribuidas en los últimos 30 días)
            $stmt = $pdo->query("SELECT id, clics FROM tiendas WHERE clics > 0");
            $tiendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total_insertados = 0;
            foreach ($tiendas as $tienda) {
                // Crear visitas distribuidas en los últimos 30 días
                $visitas_a_crear = min($tienda['clics'], 100); // Máximo 100 registros por tienda
                
                for ($i = 0; $i < $visitas_a_crear; $i++) {
                    $dias_atras = rand(0, 29);
                    $stmt_insert = $pdo->prepare("
                        INSERT INTO visitas_tienda (tienda_id, fecha_visita) 
                        VALUES (?, DATE_SUB(NOW(), INTERVAL ? DAY))
                    ");
                    $stmt_insert->execute([$tienda['id'], $dias_atras]);
                    $total_insertados++;
                }
            }
            
            echo "<p>✅ $total_insertados registros de visitas creados</p>";
        }
    }
} catch(PDOException $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>✅ Instalación Completa</h3>";
echo "<p>El sistema de estadísticas está listo para usar.</p>";
echo "<p><strong>Características:</strong></p>";
echo "<ul>";
echo "<li>✅ Registro detallado de cada visita</li>";
echo "<li>✅ Gráfica de visitas de los últimos 30 días</li>";
echo "<li>✅ Datos históricos migrados (si existían)</li>";
echo "<li>✅ Optimizado con índices para consultas rápidas</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>← Volver al inicio</a></p>";
?>
