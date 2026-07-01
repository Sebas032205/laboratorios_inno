<?php
// web/src/frontend/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SEGURIDAD AUTOMÁTICA: Si no hay variable de sesión, lo expulsa directamente al login raíz
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <div class="dashboard-layout" style="display: flex;">
        <div class="sidebar">
            <h2>Lab Inno</h2>
            <a href="#" class="active">Inicio (Dashboard)</a>
            <a href="catalogos.php">Categorías / Ubicaciones</a>
            <a href="inventario.php">Inventario (Activos)</a>
            <a href="prestamos.php">Préstamos</a>
            <a href="auditoria.php">Auditoría</a>
        </div>

        <div class="content">
            <div class="header">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></h1>
                <div>
                    <span style="font-weight: bold; background: #e2e8f0; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        ROL: <?php echo strtoupper($_SESSION['usuario_rol']); ?>
                    </span>
                    <a href="../backend/logout.php" class="logout-btn" style="text-decoration: none; display: inline-block;">Cerrar Sesión</a>
                </div>
            </div>

            <div class="dashboard-cards" style="display: flex; gap: 20px; margin-top: 20px;">
                <div class="card" style="background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <h3>Módulo de Inventario</h3>
                    <p>Gestiona todos los dispositivos tecnológicos y sensores del laboratorio.</p>
                </div>
                <div class="card" style="background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <h3>Préstamos Activos</h3>
                    <p>Visualiza qué alumnos tienen equipos bajo su responsabilidad.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>