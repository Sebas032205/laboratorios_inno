<?php
// web/src/frontend/prestamos.php

// 1. Validar la sesión en el servidor
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad: solo personal autorizado puede gestionar préstamos
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.html?error=" . urlencode("Acceso denegado. Por favor, inicie sesión."));
    exit;
}

// 2. Incluir la conexión a la base de datos
require_once '../backend/conexion.php';

// 3. Consultar listado de usuarios con rol 'usuario' (alumnos/profesores) para el préstamo
$usuarios_opt = $conn->query("SELECT id, nombre FROM usuarios WHERE rol = 'usuario' ORDER BY nombre ASC");

// 4. Consultar ÚNICAMENTE los activos que estén 'DISPONIBLE'
$activos_disponibles = $conn->query("SELECT id, nombre, numero_serie FROM activos WHERE estado = 'DISPONIBLE' ORDER BY nombre ASC");

// 5. Consultar el historial de movimientos (Préstamos y Devoluciones) con INNER JOINs enriquecidos
$sql_movimientos = "SELECT m.id, m.tipo_evento, m.fecha_registro, 
                           a.nombre AS activo_nombre, a.numero_serie,
                           u.nombre AS usuario_nombre
                    FROM movimientos_activos m
                    INNER JOIN activos a ON m.activo_id = a.id
                    INNER JOIN usuarios u ON m.usuario_id = u.id
                    ORDER BY m.id DESC";
$lista_movimientos = $conn->query($sql_movimientos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préstamos - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .prestamos-grid { display: flex; flex-direction: column; gap: 30px; margin-top: 20px; }
        .form-tarjeta, .tabla-tarjeta { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-field { flex: 1; min-width: 200px; display: flex; flex-direction: column; }
        .form-field label { font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        .form-field select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: white; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-datos th, .tabla-datos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .tabla-datos th { background-color: #f8fafc; font-weight: bold; color: #334155; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-prestamo { background-color: #ffe3e3; color: #b12b2b; }
        .badge-devolucion { background-color: #d4edda; color: #155724; }
        .alert-ok { color: #155724; background-color: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
        .alert-bad { color: #721c24; background-color: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-layout" style="display: flex;">
        <div class="sidebar">
            <h2>Lab Inno</h2>
            <a href="dashboard.php">Inicio (Dashboard)</a>
            <a href="catalogos.php">Categorías / Ubicaciones</a>
            <a href="inventario.php">Inventario (Activos)</a>
            <a href="prestamos.php" class="active">Préstamos</a>
            <a href="auditoria.php">Auditoría</a>
        </div>

        <div class="content" style="flex: 1; padding: 20px;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Gestión de Préstamos y Trazabilidad</h1>
                <div>
                    <span style="font-weight: bold; background: #e2e8f0; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        PERSONAL: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                    </span>
                    <a href="../backend/logout.php" class="logout-btn" style="text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>

            <?php if (isset($_GET['exito'])): ?>
                <div class="alert-ok"><?php echo htmlspecialchars($_GET['exito']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert-bad"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>

            <div class="prestamos-grid">
                
                <?php if ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'tecnico'): ?>
                <div class="form-tarjeta">
                    <h3>Registrar Salida de Equipo</h3>
                    <form action="../backend/procesar_prestamo.php" method="POST">
                        <div class="form-row">
                            <div class="form-field">
                                <label for="usuario_id">Solicitante (Usuario):</label>
                                <select id="usuario_id" name="usuario_id" required>
                                    <option value="">-- Seleccione el Alumno/Profesor --</option>
                                    <?php while ($usr = $usuarios_opt->fetch_assoc()): ?>
                                        <option value="<?php echo $usr['id']; ?>"><?php echo htmlspecialchars($usr['nombre']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-field">
                                <label for="activo_id">Equipo Disponible:</label>
                                <select id="activo_id" name="activo_id" required>
                                    <option value="">-- Seleccione un Activo Libre --</option>
                                    <?php while ($act = $activos_disponibles->fetch_assoc()): ?>
                                        <option value="<?php echo $act['id']; ?>">
                                            <?php echo htmlspecialchars($act['nombre']); ?> [S/N: <?php echo htmlspecialchars($act['numero_serie']); ?>]
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" style="padding: 12px 25px; margin-top: 10px;">Autorizar Préstamo</button>
                    </form>
                </div>
                <?php endif; ?>

                <div class="tabla-tarjeta">
                    <h3>Historial de Movimientos de Activos</h3>
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>ID Movimiento</th>
                                <th>Equipo</th>
                                <th>Nº Serie</th>
                                <th>Asignado a</th>
                                <th>Operación</th>
                                <th>Fecha Registro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lista_movimientos && $lista_movimientos->num_rows > 0): ?>
                                <?php while ($row = $lista_movimientos->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['activo_nombre']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($row['numero_serie']); ?></code></td>
                                        <td><?php echo htmlspecialchars($row['usuario_nombre']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($row['tipo_evento']); ?>">
                                                <?php echo $row['tipo_evento']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['fecha_registro']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #999; padding: 20px;">
                                        No se registran movimientos ni préstamos en el historial del laboratorio.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>