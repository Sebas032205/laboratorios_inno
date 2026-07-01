<?php
// web/src/frontend/inventario.php

// 1. Validar la sesión en el servidor
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// 2. Incluir la conexión a la base de datos
require_once '../backend/conexion.php';

$mensaje_exito = "";
$mensaje_error = "";

// 3. Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $num_serie    = isset($_POST['numero_serie']) ? trim($_POST['numero_serie']) : '';
    $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;
    $ubicacion_id = isset($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 0;
    $estado       = isset($_POST['estado']) ? trim($_POST['estado']) : 'DISPONIBLE';

    // Validar campos obligatorios
    if (!empty($nombre) && !empty($num_serie) && $categoria_id > 0 && $ubicacion_id > 0) {
        try {
            // Preparar consulta con marcadores '?' nativos de MySQLi
            $stmt = $conn->prepare("INSERT INTO activos (nombre, numero_serie, categoria_id, ubicacion_id, estado) VALUES (?, ?, ?, ?, ?)");
            // s = string, i = entero -> "ssiis"
            $stmt->bind_param("ssiis", $nombre, $num_serie, $categoria_id, $ubicacion_id, $estado);
            $stmt->execute();
            
            $mensaje_exito = "Activo '" . htmlspecialchars($nombre) . "' registrado con éxito.";
        } catch (\Exception $e) {
            $mensaje_error = "Error al registrar el activo: Puede que el número de serie ya exista.";
        }
    } else {
        $mensaje_error = "Por favor, rellene todos los campos obligatorios correctamente.";
    }
}

// 4. Consultar listados auxiliares para rellenar los desplegables (<select>)
$categorias_opt  = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
$ubicaciones_opt = $conn->query("SELECT id, nombre FROM ubicaciones ORDER BY nombre ASC");

// 5. Consultar los activos haciendo un INNER JOIN para traer los nombres reales del catálogo
$sql_activos = "SELECT a.id, a.nombre, a.numero_serie, a.estado, 
                       c.nombre AS categoria, u.nombre AS ubicación 
                FROM activos a
                INNER JOIN categorias c ON a.categoria_id = c.id
                INNER JOIN ubicaciones u ON a.ubicacion_id = u.id
                ORDER BY a.id DESC";
$lista_activos = $conn->query($sql_activos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .inventario-grid { display: flex; flex-direction: column; gap: 30px; margin-top: 20px; }
        .form-tarjeta { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .tabla-tarjeta { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-row { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; }
        .form-field { flex: 1; min-width: 200px; display: flex; flex-direction: column; }
        .form-field label { font-weight: bold; margin-bottom: 5px; font-size: 14px; }
        .form-field input, .form-field select { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; background: white; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-datos th, .tabla-datos td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 14px; }
        .tabla-datos th { background-color: #f8fafc; font-weight: bold; color: #334155; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-align: center; display: inline-block; }
        .badge-disponible { background-color: #d4edda; color: #155724; }
        .badge-prestado { background-color: #fff3cd; color: #856404; }
        .badge-mantenimiento { background-color: #d1ecf1; color: #0c5460; }
        .badge-baja { background-color: #f8d7da; color: #721c24; }
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
            <a href="inventario.php" class="active">Inventario (Activos)</a>
            <a href="prestamos.php">Préstamos</a>
            <a href="auditores.php">Auditoría</a>
        </div>

        <div class="content" style="flex: 1; padding: 20px;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Inventario de Activos Tecnológicos</h1>
                <div>
                    <span style="font-weight: bold; background: #e2e8f0; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        ROl: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
                    </span>
                    <a href="../backend/logout.php" class="logout-btn" style="text-decoration: none;">Cerrar Sesión</a>
                </div>
            </div>

            <?php if (!empty($mensaje_exito)): ?>
                <div class="alert-ok"><?php echo $mensaje_exito; ?></div>
            <?php endif; ?>
            <?php if (!empty($mensaje_error)): ?>
                <div class="alert-bad"><?php echo $mensaje_error; ?></div>
            <?php endif; ?>

            <div class="inventario-grid">
                
                <div class="form-tarjeta">
                    <h3>Registrar Nuevo Activo</h3>
                    <form action="inventario.php" method="POST">
                        <div class="form-row">
                            <div class="form-field">
                                <label for="nombre">Nombre del Activo:</label>
                                <input type="text" id="nombre" name="nombre" required placeholder="Ej. Laptop Dell Latitude">
                            </div>
                            <div class="form-field">
                                <label for="numero_serie">Número de Serie:</label>
                                <input type="text" id="numero_serie" name="numero_serie" required placeholder="Ej. SN-789XYZ">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-field">
                                <label for="categoria_id">Categoría:</label>
                                <select id="categoria_id" name="categoria_id" required>
                                    <option value="">-- Seleccione una Categoría --</option>
                                    <?php while ($cat = $categorias_opt->fetch_assoc()): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['nombre']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="ubicacion_id">Ubicación Física:</label>
                                <select id="ubicacion_id" name="ubicacion_id" required>
                                    <option value="">-- Seleccione una Ubicación --</option>
                                    <?php while ($ubi = $ubicaciones_opt->fetch_assoc()): ?>
                                        <option value="<?php echo $ubi['id']; ?>"><?php echo htmlspecialchars($ubi['nombre']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-field">
                                <label for="estado">Estado Inicial:</label>
                                <select id="estado" name="estado" required>
                                    <option value="DISPONIBLE">DISPONIBLE</option>
                                    <option value="PRESTADO">PRESTADO</option>
                                    <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                                    <option value="BAJA">BAJA</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="padding: 12px 25px; margin-top: 10px;">Guardar Activo</button>
                    </form>
                </div>

                <div class="tabla-tarjeta">
                    <h3>Listado Global de Activos</h3>
                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Activo</th>
                                <th>Nº Serie</th>
                                <th>Categoría</th>
                                <th>Ubicación</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lista_activos && $lista_activos->num_rows > 0): ?>
                                <?php while ($row = $lista_activos->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($row['numero_serie']); ?></code></td>
                                        <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($row['ubicación']); ?></td>
                                        <td>
                                            <?php 
                                                $clase_badge = 'badge-' . strtolower($row['estado']);
                                                echo "<span class='badge {$clase_badge}'>" . $row['estado'] . "</span>";
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: #999; padding: 20px;">
                                        No hay activos registrados en el sistema actualmente.
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