<?php
require_once 'config.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: directorio.php");
    exit();
}

// Obtener datos del formulario
$id_tienda = isset($_POST['id_tienda_reportada']) ? (int)$_POST['id_tienda_reportada'] : 0;
$motivo = isset($_POST['motivo_reporte']) ? trim($_POST['motivo_reporte']) : '';

// Validaciones
if ($id_tienda <= 0) {
    header("Location: directorio.php?error=tienda_invalida");
    exit();
}

if (empty($motivo)) {
    header("Location: tienda_detalle.php?id=" . $id_tienda . "&error=motivo_vacio");
    exit();
}

if (strlen($motivo) < 10) {
    header("Location: tienda_detalle.php?id=" . $id_tienda . "&error=motivo_corto");
    exit();
}

if (strlen($motivo) > 1000) {
    header("Location: tienda_detalle.php?id=" . $id_tienda . "&error=motivo_largo");
    exit();
}

// Verificar que la tienda existe
try {
    $stmt = $pdo->prepare("SELECT id FROM tiendas WHERE id = ? AND activo = 1");
    $stmt->execute([$id_tienda]);
    if (!$stmt->fetch()) {
        header("Location: directorio.php?error=tienda_no_existe");
        exit();
    }
} catch(PDOException $e) {
    header("Location: directorio.php?error=db");
    exit();
}

// Obtener ID del usuario (puede ser NULL si no está logueado)
$id_usuario = esta_logueado() ? $_SESSION['user_id'] : null;

// Verificar si el usuario ya reportó esta tienda recientemente (últimas 24 horas)
if ($id_usuario) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM reportes_tienda 
            WHERE id_tienda = ? 
            AND id_usuario_reporta = ? 
            AND fecha_reporte > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$id_tienda, $id_usuario]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultado['total'] > 0) {
            header("Location: tienda_detalle.php?id=" . $id_tienda . "&error=ya_reportado");
            exit();
        }
    } catch(PDOException $e) {
        // Continuar si hay error en la verificación
    }
}

// Insertar el reporte en la base de datos
try {
    $stmt = $pdo->prepare("
        INSERT INTO reportes_tienda (id_tienda, id_usuario_reporta, motivo, estado, fecha_reporte) 
        VALUES (?, ?, ?, 'pendiente', NOW())
    ");
    $stmt->execute([$id_tienda, $id_usuario, $motivo]);
    
    // Log del reporte
    error_log("Nuevo reporte de tienda - ID Tienda: $id_tienda, Usuario: " . ($id_usuario ?? 'Anónimo'));
    
    // Redirigir con mensaje de éxito
    header("Location: tienda_detalle.php?id=" . $id_tienda . "&reporte_enviado=1");
    exit();
    
} catch(PDOException $e) {
    error_log("Error al guardar reporte: " . $e->getMessage());
    header("Location: tienda_detalle.php?id=" . $id_tienda . "&error=db_insert");
    exit();
}
