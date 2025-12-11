<?php
require_once 'config.php';

echo "<h2>Estado de Usuarios Premium</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>es_premium</th></tr>";

$stmt = $pdo->query("SELECT id, nombre, email, rol, es_premium FROM usuarios ORDER BY id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nombre']}</td>";
    echo "<td>{$row['email']}</td>";
    echo "<td>{$row['rol']}</td>";
    echo "<td style='background: " . ($row['es_premium'] ? 'lightgreen' : 'lightcoral') . "'>{$row['es_premium']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h2>Estado de Tiendas</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Vendedor ID</th><th>es_destacado</th><th>Vendedor Premium</th></tr>";

$stmt = $pdo->query("
    SELECT t.id, t.nombre, t.vendedor_id, t.es_destacado, u.es_premium 
    FROM tiendas t 
    INNER JOIN usuarios u ON t.vendedor_id = u.id
    ORDER BY t.id
");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['nombre']}</td>";
    echo "<td>{$row['vendedor_id']}</td>";
    echo "<td style='background: " . ($row['es_destacado'] ? 'lightgreen' : 'lightcoral') . "'>{$row['es_destacado']}</td>";
    echo "<td style='background: " . ($row['es_premium'] ? 'lightgreen' : 'lightcoral') . "'>{$row['es_premium']}</td>";
    echo "</tr>";
}
echo "</table>";
?>
