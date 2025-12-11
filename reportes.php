<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea admin
if (!esta_logueado() || $_SESSION['rol'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

try {
    // 1. TOP 5 - TIENDAS MÁS VISITADAS
    $stmt_visitadas = $pdo->prepare("
        SELECT t.nombre_tienda, t.clics, u.nombre as vendedor_nombre, t.categoria
        FROM tiendas t
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        WHERE t.activo = 1
        ORDER BY t.clics DESC
        LIMIT 5
    ");
    $stmt_visitadas->execute();
    $tiendas_visitadas = $stmt_visitadas->fetchAll(PDO::FETCH_ASSOC);

    // 2. TOP 5 - TIENDAS MEJOR CALIFICADAS
    $stmt_mejor_calificadas = $pdo->prepare("
        SELECT t.nombre_tienda, u.nombre as vendedor_nombre, t.categoria,
               AVG(c.estrellas) as promedio_estrellas,
               COUNT(c.id) as total_reseñas
        FROM tiendas t
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        INNER JOIN calificaciones c ON t.id = c.tienda_id
        WHERE t.activo = 1 AND c.activo = 1 AND c.esta_aprobada = 1
        GROUP BY t.id, t.nombre_tienda, u.nombre, t.categoria
        HAVING COUNT(c.id) >= 2
        ORDER BY AVG(c.estrellas) DESC, COUNT(c.id) DESC
        LIMIT 5
    ");
    $stmt_mejor_calificadas->execute();
    $tiendas_mejor_calificadas = $stmt_mejor_calificadas->fetchAll(PDO::FETCH_ASSOC);

    // 3. TOP 5 - TIENDAS PEOR CALIFICADAS
    $stmt_peor_calificadas = $pdo->prepare("
        SELECT t.nombre_tienda, u.nombre as vendedor_nombre, t.categoria,
               AVG(c.estrellas) as promedio_estrellas,
               COUNT(c.id) as total_reseñas
        FROM tiendas t
        INNER JOIN usuarios u ON t.vendedor_id = u.id
        INNER JOIN calificaciones c ON t.id = c.tienda_id
        WHERE t.activo = 1 AND c.activo = 1 AND c.esta_aprobada = 1
        GROUP BY t.id, t.nombre_tienda, u.nombre, t.categoria
        HAVING COUNT(c.id) >= 2 AND AVG(c.estrellas) < 3
        ORDER BY AVG(c.estrellas) ASC, COUNT(c.id) DESC
        LIMIT 5
    ");
    $stmt_peor_calificadas->execute();
    $tiendas_peor_calificadas = $stmt_peor_calificadas->fetchAll(PDO::FETCH_ASSOC);

    // 4. TOP 5 - USUARIOS CON MÁS RESEÑAS
    $stmt_usuarios_reseñas = $pdo->prepare("
        SELECT u.nombre, u.email, u.rol,
               COUNT(c.id) as total_reseñas,
               AVG(c.estrellas) as promedio_dado
        FROM usuarios u
        INNER JOIN calificaciones c ON u.id = c.user_id
        WHERE u.activo = 1 AND c.activo = 1 AND c.esta_aprobada = 1
        GROUP BY u.id, u.nombre, u.email, u.rol
        ORDER BY COUNT(c.id) DESC
        LIMIT 5
    ");
    $stmt_usuarios_reseñas->execute();
    $usuarios_reseñas = $stmt_usuarios_reseñas->fetchAll(PDO::FETCH_ASSOC);

    // 5. GRÁFICO - NUEVOS USUARIOS POR DÍA (últimos 30 días)
    $stmt_usuarios_dia = $pdo->prepare("
        SELECT DATE(fecha_registro) as fecha, COUNT(*) as nuevos_usuarios
        FROM usuarios
        WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_registro)
        ORDER BY DATE(fecha_registro) ASC
    ");
    $stmt_usuarios_dia->execute();
    $usuarios_por_dia = $stmt_usuarios_dia->fetchAll(PDO::FETCH_ASSOC);

    // 6. ESTADÍSTICAS GENERALES
    $stmt_stats = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM usuarios WHERE activo = 1) as total_usuarios,
            (SELECT COUNT(*) FROM tiendas WHERE activo = 1) as total_tiendas,
            (SELECT COUNT(*) FROM calificaciones WHERE activo = 1 AND esta_aprobada = 1) as total_reseñas,
            (SELECT COUNT(*) FROM galeria_tiendas WHERE activo = 1) as total_fotos,
            (SELECT SUM(clics) FROM tiendas WHERE activo = 1) as total_visitas
    ");
    $stmt_stats->execute();
    $estadisticas_generales = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $error = "Error al cargar los reportes: " . $e->getMessage();
    $tiendas_visitadas = $tiendas_mejor_calificadas = $tiendas_peor_calificadas = $usuarios_reseñas = $usuarios_por_dia = [];
    $estadisticas_generales = ['total_usuarios' => 0, 'total_tiendas' => 0, 'total_reseñas' => 0, 'total_fotos' => 0, 'total_visitas' => 0];
}

// Función para mostrar estrellas
function mostrar_estrellas_reporte($promedio) {
    $html = '';
    $promedio_redondeado = round($promedio, 1);
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $promedio_redondeado) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        } elseif ($i - 0.5 <= $promedio_redondeado) {
            $html .= '<i class="bi bi-star-half text-warning"></i>';
        } else {
            $html .= '<i class="bi bi-star text-muted"></i>';
        }
    }
    
    return $html . ' (' . $promedio_redondeado . ')';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        
        .header {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #007bff, #6f42c1);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .top-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid #007bff;
        }
        
        .top-item.first {
            border-left-color: #ffc107;
            background: linear-gradient(90deg, #fff3cd, #ffffff);
        }
        
        .top-item.second {
            border-left-color: #6c757d;
            background: linear-gradient(90deg, #e2e3e5, #ffffff);
        }
        
        .top-item.third {
            border-left-color: #fd7e14;
            background: linear-gradient(90deg, #fde2d1, #ffffff);
        }
        
        .chart-container {
            position: relative;
            height: 400px;
            margin: 2rem 0;
        }
        
        .section-title {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .badge-rol {
            font-size: 0.75rem;
        }
        
        .no-data {
            text-align: center;
            color: #6c757d;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="bi bi-bar-chart"></i> Reportes y Estadísticas
                    </h1>
                    <p class="mb-0 opacity-75">Análisis completo de la plataforma</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard_admin.php" class="btn btn-light">
                        <i class="bi bi-arrow-left"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas Generales -->
        <div class="admin-card">
            <h3 class="section-title">
                <i class="bi bi-speedometer2"></i> Resumen General
            </h3>
            <div class="row">
                <div class="col-md-2">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($estadisticas_generales['total_usuarios']); ?></div>
                        <div>Usuarios</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <div class="stat-number"><?php echo number_format($estadisticas_generales['total_tiendas']); ?></div>
                        <div>Tiendas</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                        <div class="stat-number"><?php echo number_format($estadisticas_generales['total_reseñas']); ?></div>
                        <div>Reseñas</div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #6f42c1);">
                        <div class="stat-number"><?php echo number_format($estadisticas_generales['total_fotos']); ?></div>
                        <div>Fotos</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #fd7e14);">
                        <div class="stat-number"><?php echo number_format($estadisticas_generales['total_visitas']); ?></div>
                        <div>Visitas Totales Generadas</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Nuevos Usuarios -->
        <div class="admin-card">
            <h3 class="section-title">
                <i class="bi bi-graph-up"></i> Nuevos Usuarios por Día (Últimos 30 días)
            </h3>
            <div class="chart-container">
                <canvas id="usuariosChart"></canvas>
            </div>
        </div>

        <!-- Reportes de Tiendas -->
        <div class="row">
            <!-- Top 5 Más Visitadas -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <h4 class="section-title">
                        <i class="bi bi-eye"></i> Top 5 - Más Visitadas
                    </h4>
                    
                    <?php if (empty($tiendas_visitadas)): ?>
                        <div class="no-data">
                            <i class="bi bi-inbox"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tiendas_visitadas as $index => $tienda): ?>
                            <div class="top-item <?php echo $index === 0 ? 'first' : ($index === 1 ? 'second' : ($index === 2 ? 'third' : '')); ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?> - 
                                            <?php echo htmlspecialchars($tienda['categoria']); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary fs-6">
                                            <?php echo number_format($tienda['clics']); ?> visitas
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 5 Mejor Calificadas -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <h4 class="section-title">
                        <i class="bi bi-star"></i> Top 5 - Mejor Calificadas
                    </h4>
                    
                    <?php if (empty($tiendas_mejor_calificadas)): ?>
                        <div class="no-data">
                            <i class="bi bi-inbox"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tiendas_mejor_calificadas as $index => $tienda): ?>
                            <div class="top-item <?php echo $index === 0 ? 'first' : ($index === 1 ? 'second' : ($index === 2 ? 'third' : '')); ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?> - 
                                        <?php echo htmlspecialchars($tienda['categoria']); ?>
                                    </small>
                                    <br>
                                    <div class="mt-1">
                                        <?php echo mostrar_estrellas_reporte($tienda['promedio_estrellas']); ?>
                                        <small class="text-muted ms-2"><?php echo $tienda['total_reseñas']; ?> reseñas</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top 5 Peor Calificadas -->
            <div class="col-lg-4">
                <div class="admin-card">
                    <h4 class="section-title">
                        <i class="bi bi-star"></i> Top 5 - Peor Calificadas
                    </h4>
                    
                    <?php if (empty($tiendas_peor_calificadas)): ?>
                        <div class="no-data">
                            <i class="bi bi-inbox"></i>
                            <p>No hay datos disponibles</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($tiendas_peor_calificadas as $index => $tienda): ?>
                            <div class="top-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($tienda['nombre_tienda']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($tienda['vendedor_nombre']); ?> - 
                                        <?php echo htmlspecialchars($tienda['categoria']); ?>
                                    </small>
                                    <br>
                                    <div class="mt-1">
                                        <?php echo mostrar_estrellas_reporte($tienda['promedio_estrellas']); ?>
                                        <small class="text-muted ms-2"><?php echo $tienda['total_reseñas']; ?> reseñas</small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reporte de Usuarios -->
        <div class="admin-card">
            <h3 class="section-title">
                <i class="bi bi-people"></i> Top 5 - Usuarios con Más Reseñas
            </h3>
            
            <?php if (empty($usuarios_reseñas)): ?>
                <div class="no-data">
                    <i class="bi bi-inbox"></i>
                    <p>No hay datos disponibles</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($usuarios_reseñas as $index => $usuario): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="top-item <?php echo $index === 0 ? 'first' : ($index === 1 ? 'second' : ($index === 2 ? 'third' : '')); ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>
                                    <span class="badge badge-rol bg-<?php echo $usuario['rol'] === 'admin' ? 'danger' : ($usuario['rol'] === 'vendedor' ? 'success' : 'primary'); ?> ms-2">
                                        <?php echo ucfirst($usuario['rol']); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                    <br>
                                    <div class="mt-2">
                                        <span class="badge bg-info">
                                            <?php echo $usuario['total_reseñas']; ?> reseñas
                                        </span>
                                        <span class="badge bg-warning text-dark ms-1">
                                            Promedio: <?php echo round($usuario['promedio_dado'], 1); ?> ⭐
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Datos para el gráfico de usuarios por día
        const usuariosPorDia = <?php echo json_encode($usuarios_por_dia); ?>;
        
        // Preparar datos para Chart.js
        const fechas = [];
        const cantidades = [];
        
        // Llenar arrays con los datos
        usuariosPorDia.forEach(function(item) {
            // Formatear fecha para mostrar
            const fecha = new Date(item.fecha);
            const fechaFormateada = fecha.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit'
            });
            
            fechas.push(fechaFormateada);
            cantidades.push(parseInt(item.nuevos_usuarios));
        });
        
        // Configuración del gráfico
        const ctx = document.getElementById('usuariosChart').getContext('2d');
        const usuariosChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: fechas,
                datasets: [{
                    label: 'Nuevos Usuarios',
                    data: cantidades,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(75, 192, 192)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Registros de Usuarios - Últimos 30 Días',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    },
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Math.floor(value) === value ? value : '';
                            }
                        },
                        title: {
                            display: true,
                            text: 'Cantidad de Usuarios'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        hoverBackgroundColor: 'rgb(255, 99, 132)'
                    }
                }
            }
        });
        
        // Mostrar información adicional si no hay datos
        if (usuariosPorDia.length === 0) {
            document.querySelector('.chart-container').innerHTML = `
                <div class="no-data">
                    <i class="bi bi-graph-up" style="font-size: 3rem; color: #dee2e6;"></i>
                    <h4 class="mt-3 text-muted">No hay registros recientes</h4>
                    <p class="text-muted">No se han registrado usuarios en los últimos 30 días</p>
                </div>
            `;
        }
    </script>
</body>
</html>