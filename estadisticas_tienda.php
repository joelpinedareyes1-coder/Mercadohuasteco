<?php
require_once 'config.php';

// Verificar que el usuario esté logueado y sea vendedor
if (!esta_logueado() || $_SESSION['rol'] !== 'vendedor') {
    header("Location: auth.php");
    exit();
}

// Obtener información de la tienda del vendedor
try {
    $stmt = $pdo->prepare("SELECT * FROM tiendas WHERE vendedor_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $tienda_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tienda_info) {
        header("Location: panel_vendedor.php");
        exit();
    }
} catch(PDOException $e) {
    die("Error al cargar tienda");
}

// Obtener estadísticas generales
try {
    $stmt = $pdo->prepare("
        SELECT 
            t.clics as total_visitas,
            COALESCE(AVG(c.estrellas), 0) as promedio_calificacion,
            COUNT(DISTINCT c.id) as total_calificaciones,
            (SELECT COUNT(*) FROM galeria_tiendas g WHERE g.tienda_id = t.id AND g.activo = 1) as total_fotos
        FROM tiendas t 
        LEFT JOIN calificaciones c ON t.id = c.tienda_id AND c.activo = 1 AND c.esta_aprobada = 1
        WHERE t.id = ?
        GROUP BY t.id
    ");
    $stmt->execute([$tienda_info['id']]);
    $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $estadisticas = [
        'total_visitas' => 0,
        'promedio_calificacion' => 0,
        'total_calificaciones' => 0,
        'total_fotos' => 0
    ];
}

// Obtener visitas de los últimos 30 días
try {
    $stmt = $pdo->prepare("
        SELECT 
            DATE(fecha_visita) as fecha,
            COUNT(*) as visitas
        FROM visitas_tienda
        WHERE tienda_id = ? 
        AND fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha_visita)
        ORDER BY fecha ASC
    ");
    $stmt->execute([$tienda_info['id']]);
    $visitas_diarias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear array con todos los días (incluso los que tienen 0 visitas)
    $datos_grafica = [];
    for ($i = 29; $i >= 0; $i--) {
        $fecha = date('Y-m-d', strtotime("-$i days"));
        $datos_grafica[$fecha] = 0;
    }
    
    // Llenar con los datos reales
    foreach ($visitas_diarias as $visita) {
        $datos_grafica[$visita['fecha']] = (int)$visita['visitas'];
    }
    
} catch(PDOException $e) {
    $datos_grafica = [];
    error_log("Error obteniendo visitas: " . $e->getMessage());
}

// Preparar datos para Chart.js
$labels = [];
$data = [];
foreach ($datos_grafica as $fecha => $visitas) {
    $labels[] = date('d/m', strtotime($fecha));
    $data[] = $visitas;
}

// Obtener estadísticas adicionales
try {
    // Visitas de hoy
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visitas_hoy
        FROM visitas_tienda
        WHERE tienda_id = ? AND DATE(fecha_visita) = CURDATE()
    ");
    $stmt->execute([$tienda_info['id']]);
    $visitas_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['visitas_hoy'];
    
    // Visitas de esta semana
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visitas_semana
        FROM visitas_tienda
        WHERE tienda_id = ? AND fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$tienda_info['id']]);
    $visitas_semana = $stmt->fetch(PDO::FETCH_ASSOC)['visitas_semana'];
    
    // Visitas de este mes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visitas_mes
        FROM visitas_tienda
        WHERE tienda_id = ? AND fecha_visita >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$tienda_info['id']]);
    $visitas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['visitas_mes'];
    
} catch(PDOException $e) {
    $visitas_hoy = 0;
    $visitas_semana = 0;
    $visitas_mes = 0;
}

// Obtener reseñas de la tienda
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.nombre as usuario_nombre
        FROM calificaciones c
        INNER JOIN usuarios u ON c.user_id = u.id
        WHERE c.tienda_id = ? AND c.activo = 1
        ORDER BY c.fecha_calificacion DESC
    ");
    $stmt->execute([$tienda_info['id']]);
    $reseñas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $reseñas = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas - <?php echo htmlspecialchars($tienda_info['nombre_tienda']); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        :root {
            --primary-color: #006666;
            --secondary-color: #CC5500;
            --accent-color: #FF6B6B;
            --success-color: #28a745;
            --font-family: 'Montserrat', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f6f5f7 0%, #e2e8f0 100%);
            font-family: var(--font-family);
        }
        
        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
        
        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }
        
        .stats-icon.primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .stats-icon.success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .stats-icon.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }
        
        .stats-icon.info {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
        }
        
        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-top: 2rem;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: white;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-2">
                        <i class="fas fa-chart-line me-2"></i>Estadísticas de tu Tienda
                    </h1>
                    <p class="mb-0 opacity-75"><?php echo htmlspecialchars($tienda_info['nombre_tienda']); ?></p>
                </div>
                <a href="panel_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Tarjetas de estadísticas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon primary mx-auto">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($estadisticas['total_visitas']); ?></h3>
                    <p class="text-muted mb-0">Visitas Totales</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon success mx-auto">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($visitas_hoy); ?></h3>
                    <p class="text-muted mb-0">Visitas Hoy</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon warning mx-auto">
                        <i class="fas fa-calendar-week"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($visitas_semana); ?></h3>
                    <p class="text-muted mb-0">Últimos 7 Días</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-icon info mx-auto">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($visitas_mes); ?></h3>
                    <p class="text-muted mb-0">Últimos 30 Días</p>
                </div>
            </div>
        </div>

        <!-- Gráfica de visitas -->
        <div class="chart-container">
            <h4 class="mb-4">
                <i class="fas fa-chart-area me-2" style="color: var(--primary-color);"></i>
                Visitas de los Últimos 30 Días
            </h4>
            <canvas id="visitasChart" height="80"></canvas>
        </div>

        <!-- Estadísticas adicionales -->
        <div class="row g-4 mt-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon success me-3">
                            <i class="fas fa-star"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo number_format($estadisticas['promedio_calificacion'], 1); ?></h4>
                            <p class="text-muted mb-0">Calificación Promedio</p>
                            <small class="text-muted"><?php echo $estadisticas['total_calificaciones']; ?> reseñas</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon warning me-3">
                            <i class="fas fa-images"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?php echo $estadisticas['total_fotos']; ?></h4>
                            <p class="text-muted mb-0">Fotos en Galería</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon info me-3">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div>
                            <?php 
                            $promedio_diario = $visitas_mes > 0 ? round($visitas_mes / 30, 1) : 0;
                            ?>
                            <h4 class="mb-0"><?php echo $promedio_diario; ?></h4>
                            <p class="text-muted mb-0">Visitas por Día</p>
                            <small class="text-muted">Promedio mensual</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestión de Reseñas -->
        <div class="chart-container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">
                    <i class="fas fa-comments me-2" style="color: var(--primary-color);"></i>
                    Reseñas de Clientes
                </h4>
                <span class="badge bg-primary fs-6">
                    <?php echo count($reseñas); ?> reseñas totales
                </span>
            </div>

            <?php if (empty($reseñas)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-comment-slash" style="font-size: 4rem; color: #ddd;"></i>
                    <p class="text-muted mt-3 mb-0">Aún no tienes reseñas</p>
                    <small class="text-muted">Las reseñas de tus clientes aparecerán aquí</small>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($reseñas as $reseña): ?>
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <i class="fas fa-user-circle me-2" style="color: var(--primary-color);"></i>
                                                <?php echo htmlspecialchars($reseña['usuario_nombre']); ?>
                                            </h6>
                                            <div class="mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $reseña['estrellas']): ?>
                                                        <i class="fas fa-star text-warning"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star text-muted"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                                <span class="ms-2 text-muted"><?php echo $reseña['estrellas']; ?>/5</span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                <?php echo date('d/m/Y', strtotime($reseña['fecha_calificacion'])); ?>
                                            </small>
                                            <br>
                                            <?php if ($reseña['esta_aprobada']): ?>
                                                <span class="badge bg-success mt-1">
                                                    <i class="fas fa-check-circle me-1"></i>Aprobada
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning mt-1">
                                                    <i class="fas fa-clock me-1"></i>Pendiente
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-dark">
                                        <?php echo nl2br(htmlspecialchars($reseña['comentario'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Resumen de reseñas -->
                <div class="row g-3 mt-3">
                    <?php
                    // Calcular distribución de estrellas
                    $distribucion = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                    foreach ($reseñas as $r) {
                        $distribucion[$r['estrellas']]++;
                    }
                    $total_reseñas = count($reseñas);
                    ?>
                    
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    <i class="fas fa-chart-bar me-2"></i>Distribución de Calificaciones
                                </h6>
                                <?php foreach ([5, 4, 3, 2, 1] as $estrella): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="me-2" style="width: 60px;">
                                            <?php echo $estrella; ?> <i class="fas fa-star text-warning"></i>
                                        </span>
                                        <div class="progress flex-grow-1" style="height: 20px;">
                                            <?php 
                                            $porcentaje = $total_reseñas > 0 ? ($distribucion[$estrella] / $total_reseñas) * 100 : 0;
                                            ?>
                                            <div class="progress-bar bg-warning" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $porcentaje; ?>%"
                                                 aria-valuenow="<?php echo $porcentaje; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $distribucion[$estrella]; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    <i class="fas fa-lightbulb me-2"></i>Consejos para Mejorar
                                </h6>
                                <?php 
                                $promedio = $estadisticas['promedio_calificacion'];
                                ?>
                                <?php if ($promedio >= 4.5): ?>
                                    <div class="alert alert-success mb-2">
                                        <i class="fas fa-trophy me-2"></i>
                                        <strong>¡Excelente!</strong> Mantén este nivel de servicio.
                                    </div>
                                <?php elseif ($promedio >= 3.5): ?>
                                    <div class="alert alert-info mb-2">
                                        <i class="fas fa-thumbs-up me-2"></i>
                                        <strong>Buen trabajo.</strong> Busca áreas de mejora.
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning mb-2">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Atención.</strong> Revisa los comentarios negativos.
                                    </div>
                                <?php endif; ?>
                                
                                <ul class="mb-0 small">
                                    <li>Responde a todas las reseñas (próximamente)</li>
                                    <li>Agradece los comentarios positivos</li>
                                    <li>Aprende de las críticas constructivas</li>
                                    <li>Mejora continuamente tu servicio</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Configuración de la gráfica
        const ctx = document.getElementById('visitasChart').getContext('2d');
        const visitasChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Visitas',
                    data: <?php echo json_encode($data); ?>,
                    borderColor: 'rgb(0, 102, 102)',
                    backgroundColor: 'rgba(0, 102, 102, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(0, 102, 102)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return 'Visitas: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 11
                            },
                            maxRotation: 45,
                            minRotation: 45
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
