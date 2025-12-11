<?php
require_once 'config.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    header("Location: auth.php");
    exit();
}

// Verificar que se recibió el ID de la tienda
if (!isset($_GET['tienda_id'])) {
    header("Location: directorio.php");
    exit();
}

$tienda_id = (int)$_GET['tienda_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar que la reseña existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT id 
        FROM calificaciones 
        WHERE tienda_id = ? AND user_id = ? AND activo = 1
    ");
    $stmt->execute([$tienda_id, $user_id]);
    $reseña = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reseña) {
        $_SESSION['error'] = "No se encontró tu reseña o ya fue eliminada.";
        header("Location: tienda_detalle.php?id=" . $tienda_id);
        exit();
    }
    
    // Eliminar la reseña (soft delete)
    $stmt = $pdo->prepare("
        UPDATE calificaciones 
        SET activo = 0 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$reseña['id'], $user_id]);
    
    // Redirigir con mensaje de éxito
    header("Location: tienda_detalle.php?id=" . $tienda_id . "&deleted=1");
    exit();
    
} catch(PDOException $e) {
    // Redirigir con mensaje de error
    header("Location: tienda_detalle.php?id=" . $tienda_id . "&error=delete");
    exit();
}
