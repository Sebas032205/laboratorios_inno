<?php
// PON ESTO HASTA ARRIBA DE TUS VISTAS (dashboard.php, prestamos.php, mis_prestamos.php)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SEGURIDAD AUTOMÁTICA: Si no hay variable de sesión, lo expulsa directamente al login raíz
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../backend/conexion.php';

// Consultas de métricas del inventario
$metricas = [
    'total_activos' => 0,
    'disponibles' => 0,
    'prestados' => 0,
    'mantenimiento' => 0,
    'baja' => 0,
];

$sql_metricas = "SELECT
    COUNT(*) AS total_activos,
    SUM(estado='DISPONIBLE') AS disponibles,
    SUM(estado='PRESTADO') AS prestados,
    SUM(estado='MANTENIMIENTO') AS mantenimiento,
    SUM(estado='BAJA') AS baja
FROM activos";

if ($res_metricas = $conn->query($sql_metricas)) {
    $fila_metricas = $res_metricas->fetch_assoc();
    if ($fila_metricas) {
        $metricas = array_merge($metricas, $fila_metricas);
    }
}

$total_categorias = 0;
$total_ubicaciones = 0;
if ($res = $conn->query("SELECT COUNT(*) AS total_categorias FROM categorias")) {
    $row = $res->fetch_assoc();
    $total_categorias = intval($row['total_categorias'] ?? 0);
}
if ($res = $conn->query("SELECT COUNT(*) AS total_ubicaciones FROM ubicaciones")) {
    $row = $res->fetch_assoc();
    $total_ubicaciones = intval($row['total_ubicaciones'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .dashboard-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .stat-card { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .stat-card h3 { margin: 0 0 10px; font-size: 16px; color: #334155; }
        .stat-card p { margin: 0; font-size: 34px; font-weight: bold; color: #0f172a; }
        .dashboard-chart { margin-top: 30px; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .dashboard-chart h3 { margin-bottom: 18px; }
        #chartEstados { width: 100%; max-width: 100%; height: 320px; }
        .dashboard-content { padding: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-layout" style="display: flex;">
        <div class="sidebar">
            <h2>Lab Inno</h2>
            <a href="#" class="active">Inicio (Dashboard)</a>
            <a href="catalogos.php">Categorías / Ubicaciones</a>
            <a href="inventario.php">Inventario (Activos)</a>
            <a href="prestamos.php">Préstamos</a>
           <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                <a href="auditoria.php">Auditoría</a>
            <?php endif; ?>
        </div>

        <div class="content">
            <div class="header">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h1>
                <div>
                    <span style="font-weight: bold; background: #e2e8f0; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        Nombre: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                    </span>
                    <a href="../backend/logout.php" class="logout-btn" style="text-decoration: none; display: inline-block;">Cerrar Sesión</a>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <h3>Total de Activos</h3>
                        <p><?php echo intval($metricas['total_activos']); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Activos Disponibles</h3>
                        <p><?php echo intval($metricas['disponibles']); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Activos Prestados</h3>
                        <p><?php echo intval($metricas['prestados']); ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Categorías / Ubicaciones</h3>
                        <p><?php echo intval($total_categorias); ?> cat., <?php echo intval($total_ubicaciones); ?> ubic.</p>
                    </div>
                </div>

                <div class="dashboard-chart">
                    <h3>Distribución del Inventario por Estado</h3>
                    <canvas id="chartEstados" width="800" height="320"></canvas>
                </div>
            </div>
        </div>
    </div>
</body>
</html>