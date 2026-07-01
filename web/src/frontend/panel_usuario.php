<?php
// PON ESTO HASTA ARRIBA DE TUS VISTAS (dashboard.php, prestamos.php, mis_prestamos.php)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad: Si no ha iniciado sesión, al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// Bloqueo inverso opcional: Si un admin entra aquí por error, lo mandamos a su dashboard
if ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'tecnico') {
    header("Location: dashboard.php");
    exit;
}

require_once '../backend/conexion.php';

// Obtener el ID del alumno que está viendo la página
$id_alumno = intval($_SESSION['usuario_id']);

// CONSULTA SQL FILTRADA: Traer solo los equipos actualmente prestados a este usuario
$sql_mis_equipos = "SELECT m.id, m.activo_id, m.tipo_evento, m.fecha_registro, 
                           a.nombre AS activo_nombre, a.numero_serie, a.estado
                    FROM movimientos_activos m
                    INNER JOIN activos a ON m.activo_id = a.id
                    WHERE m.usuario_id = $id_alumno AND a.estado = 'PRESTADO'
                    ORDER BY m.id DESC";

$res_equipos = $conn->query($sql_mis_equipos);
$mis_equipos = array();

if ($res_equipos) {
    while ($r = $res_equipos->fetch_assoc()) {
        $mis_equipos[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Préstamos - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .panel-alumno { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-top: 20px; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-datos th, .tabla-datos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        .tabla-datos th { background-color: #f8fafc; font-weight: bold; color: #334155; }
        .badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-activo { background-color: #e3f2fd; color: #0d47a1; } /* Azul: En su posesión */
        .badge-devuelto { background-color: #e8f5e9; color: #1b5e20; } /* Verde: Ya lo entregó */
    </style>
</head>
<body>
    <div class="dashboard-layout" style="display: flex; min-height: 100vh;">
        
        <div class="sidebar" style="width: 250px; background: #2c3e50; color: white; padding: 20px;">
            <h2>Lab Inno</h2>
            <p style="font-size: 12px; color: #bdc3c7;">Panel del Estudiante</p>
            <hr style="border: 0; border-top: 1px solid #34495e; margin: 15px 0;">
            <a href="panel_usuario.php" class="active" style="color: white; text-decoration: none; display: block; padding: 10px 0;">🔄 Mis Equipos Activos</a>
        </div>

        <div class="content" style="flex: 1; padding: 30px; background: #f8fafc;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <div>
                    <h1>Hola, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?> 👋</h1>
                    <p style="color: #64748b;">Aquí puedes consultar el estado de los materiales que has solicitado.</p>
                </div>
                <a href="../backend/logout.php" class="logout-btn" style="background: #ef4444; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-weight: bold;">Cerrar Sesión</a>
            </div>

            <div class="panel-alumno">
                <h3>📋 Estado de tus Resguardos en el Laboratorio</h3>
                
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID Movimiento</th>
                            <th>Equipo Solicitado</th>
                            <th>Número de Serie</th>
                            <th>Tu Estatus</th>
                            <th>Fecha de Asignación</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mis_equipos)): ?>
                            <?php foreach ($mis_equipos as $row): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['activo_nombre']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($row['numero_serie']); ?></code></td>
                                    <td>
                                        <?php if ($row['tipo_evento'] === 'PRESTAMO'): ?>
                                            <span class="badge badge-activo"> Lo tienes bajo tu resguardo</span>
                                        <?php else: ?>
                                            <span class="badge badge-devuelto"> Entregado correctamente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['fecha_registro']); ?></td>
                                    <td>
                                        <form action="../backend/procesar_devolucion.php" method="POST" style="margin:0;">
                                            <input type="hidden" name="activo_id" value="<?php echo intval($row['activo_id']); ?>">
                                            <button type="submit" style="padding: 6px 12px; background:#16a34a; border:none; color:#fff; border-radius:4px; cursor:pointer;">Devolver</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">
                                    Actualmente no cuentas con ningún equipo prestado del inventario.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>