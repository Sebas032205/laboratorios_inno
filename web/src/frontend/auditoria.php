<?php
// web/src/frontend/auditoria.php

// 1. Validar la sesión en el servidor
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad: La auditoría suele ser exclusiva para el rol 'admin'
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    header("Location: dashboard.php?error=" . urlencode("Acceso denegado. Solo los administradores pueden ver los logs de auditoría."));
    exit;
}

// 2. Incluir la conexión a la base de datos
require_once '../backend/conexion.php';

// 3. Consultar los logs de auditoría con un INNER JOIN para traer el nombre del usuario responsable
$sql_logs = "SELECT l.id, l.nombre_tabla, l.registro_id, l.tipo_evento, 
                    l.valores_antiguos, l.valores_nuevos, l.direccion_ip, l.fecha_registro,
                    u.nombre AS usuario_nombre
             FROM logs_auditoria l
             LEFT JOIN usuarios u ON l.usuario_id = u.id
             ORDER BY l.id DESC 
             LIMIT 100"; // Limitado a los últimos 100 eventos por rendimiento

$lista_logs = $conn->query($sql_logs);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoría Avanzada - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .auditoria-container { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-top: 20px; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-datos th, .tabla-datos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; vertical-align: top; }
        .tabla-datos th { background-color: #f8fafc; font-weight: bold; color: #334155; }
        
        /* Badges para los tipos de eventos SQL */
        .badge-evento { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        .badge-insert { background-color: #d4edda; color: #155724; }
        .badge-update { background-color: #fff3cd; color: #856404; }
        .badge-delete { background-color: #f8d7da; color: #721c24; }
        
        /* Contenedor de datos JSON preformateados */
        .json-block { background-color: #f1f5f9; padding: 8px; border-radius: 4px; font-family: monospace; font-size: 11px; white-space: pre-wrap; max-width: 250px; color: #475569; margin: 0; }
        .text-muted { color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <div class="dashboard-layout" style="display: flex;">
        <div class="sidebar">
            <h2>Lab Inno</h2>
            <a href="dashboard.php">Inicio (Dashboard)</a>
            <a href="catalogos.php">Categorías / Ubicaciones</a>
            <a href="inventario.php">Inventario (Activos)</a>
            <a href="prestamos.php">Préstamos</a>
            <a href="auditoria.php" class="active">Auditoría</a>
        </div>

        <div class="content" style="flex: 1; padding: 20px;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Pistas de Auditoría del Sistema</h1>
                <div>
                    <span style="font-weight: bold; background: #dc2626; color: white; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        ADMIN: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                    </span>
                    <a href="../backend/logout.php" class="logout-btn" style="text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>

            <div class="auditoria-container">
                <h3>Historial Reciente de Cambios (Logs)</h3>
                <p style="font-size: 14px; color: #64748b;">Monitoreo en tiempo real de inserciones, modificaciones y eliminaciones de datos.</p>
                
                <table class="tabla-datos">
                    <thead>
                        <tr>
                            <th>ID Log</th>
                            <th>Fecha y Hora</th>
                            <th>Usuario</th>
                            <th>Tabla / Registro ID</th>
                            <th>Operación</th>
                            <th>Valores Antiguos</th>
                            <th>Valores Nuevos</th>
                            <th>Dirección IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($lista_logs && $lista_logs->num_rows > 0): ?>
                            <?php while ($row = $lista_logs->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><strong><?php echo $row['fecha_registro']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['usuario_nombre'] ?? 'Sistema / Automatización'); ?></td>
                                    <td>
                                        <code><?php echo htmlspecialchars($row['nombre_tabla']); ?></code> 
                                        <br><span style="color: #64748b;">ID del Registro: #<?php echo $row['registro_id']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                            $evento = strtoupper($row['tipo_evento']);
                                            $clase = 'badge-insert';
                                            if ($evento === 'UPDATE') $clase = 'badge-update';
                                            if ($evento === 'DELETE') $clase = 'badge-delete';
                                            echo "<span class='badge-evento {$clase}'>{$evento}</span>";
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['valores_antiguos']) && $row['valores_antiguos'] !== 'null'): ?>
                                            <pre class="json-block"><?php 
                                                // Decodificar y volver a formatear de forma bonita el JSON
                                                $json_antiguo = json_decode($row['valores_antiguos'], true);
                                                echo htmlspecialchars(json_encode($json_antiguo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 
                                            ?></pre>
                                        <?php else: ?>
                                            <span class="text-muted">N/A (Ninguno)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['valores_nuevos']) && $row['valores_nuevos'] !== 'null'): ?>
                                            <pre class="json-block"><?php 
                                                $json_nuevo = json_decode($row['valores_nuevos'], true);
                                                echo htmlspecialchars(json_encode($json_nuevo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 
                                            ?></pre>
                                        <?php else: ?>
                                            <span class="text-muted">N/A (Ninguno)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($row['direccion_ip'] ?? 'Desconocida'); ?></code></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; color: #999; padding: 25px;">
                                    No hay registros de auditoría almacenados en este momento.
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