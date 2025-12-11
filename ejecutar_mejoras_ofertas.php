<?php
/**
 * INSTALADOR DE MEJORAS PARA OFERTAS PREMIUM
 * Ejecuta este archivo UNA VEZ para agregar los nuevos campos
 */

require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Instalador - Mejoras Ofertas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container py-5'>
        <div class='card shadow'>
            <div class='card-header bg-warning text-dark'>
                <h3 class='mb-0'><i class='bi bi-rocket-takeoff me-2'></i>Instalador de Mejoras - Sistema de Ofertas</h3>
            </div>
            <div class='card-body'>";

try {
    // Leer el archivo SQL
    $sql = file_get_contents('agregar_campos_ofertas_mejoradas.sql');
    
    if ($sql === false) {
        throw new Exception("No se pudo leer el archivo SQL");
    }
    
    // Ejecutar las consultas
    $pdo->exec($sql);
    
    echo "<div class='alert alert-success'>
            <h4><i class='bi bi-check-circle me-2'></i>¡Instalación Exitosa!</h4>
            <p class='mb-0'>Los nuevos campos se han agregado correctamente a la tabla cupones_ofertas.</p>
          </div>";
    
    echo "<h5 class='mt-4'>Nuevas características agregadas:</h5>
          <ul class='list-group mb-4'>
            <li class='list-group-item'><i class='bi bi-percent text-success me-2'></i><strong>Porcentaje de descuento</strong> - Muestra el % de descuento de forma destacada</li>
            <li class='list-group-item'><i class='bi bi-link-45deg text-primary me-2'></i><strong>Link del producto</strong> - Enlace directo al producto en oferta</li>
            <li class='list-group-item'><i class='bi bi-image text-info me-2'></i><strong>Imagen de oferta</strong> - Imagen promocional atractiva</li>
            <li class='list-group-item'><i class='bi bi-tag text-warning me-2'></i><strong>Categorías</strong> - Descuento, 2x1, 3x2, Envío gratis, etc.</li>
            <li class='list-group-item'><i class='bi bi-eye text-secondary me-2'></i><strong>Estadísticas</strong> - Contador de vistas y clics</li>
          </ul>";
    
    echo "<div class='alert alert-info'>
            <h5><i class='bi bi-info-circle me-2'></i>Próximos pasos:</h5>
            <ol class='mb-0'>
                <li>Ve a <strong>Mis Ofertas</strong> para crear ofertas con las nuevas opciones</li>
                <li>Visita <strong>Ofertas</strong> para ver el nuevo diseño mejorado</li>
                <li>Las ofertas existentes seguirán funcionando normalmente</li>
            </ol>
          </div>";
    
    echo "<a href='mis_ofertas.php' class='btn btn-warning btn-lg'>
            <i class='bi bi-ticket-perforated me-2'></i>Ir a Mis Ofertas
          </a>
          <a href='ofertas.php' class='btn btn-outline-secondary btn-lg ms-2'>
            <i class='bi bi-eye me-2'></i>Ver Ofertas Públicas
          </a>";
    
} catch(PDOException $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-exclamation-triangle me-2'></i>Error de Base de Datos</h4>
            <p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
            <p class='mb-0'><small>Si el error dice que las columnas ya existen, significa que ya se instaló previamente.</small></p>
          </div>";
} catch(Exception $e) {
    echo "<div class='alert alert-danger'>
            <h4><i class='bi bi-exclamation-triangle me-2'></i>Error</h4>
            <p class='mb-0'>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}

echo "      </div>
        </div>
    </div>
</body>
</html>";
?>
