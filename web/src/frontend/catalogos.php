<?php
// PON ESTO HASTA ARRIBA DE TUS VISTAS (dashboard.php, prestamos.php, mis_prestamos.php)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");


// 1. Asegurar y validar la sesión en el servidor
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad dinámico y silencioso: si no hay sesión iniciada, expulsar al login raíz
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.php");
    exit;
}

// 2. Incluir la conexión a la base de datos y a Redis
require_once '../backend/conexion.php';
require_once '../backend/conexion_redis.php'; // <-- CONEXIÓN A REDIS AGREGADA

// 3. Procesar las inserciones si el usuario envía un formulario (Método POST)
$mensaje_exito = "";
$mensaje_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CASO A: Se envió una nueva Categoría
    if (isset($_POST['accion']) && $_POST['accion'] === 'nueva_categoria') {
        $nombre_cat = isset($_POST['nombre_categoria']) ? trim($_POST['nombre_categoria']) : '';
        
        if (!empty($nombre_cat)) {
            try {
                $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $stmt->bind_param("s", $nombre_cat);
                $stmt->execute();
                $mensaje_exito = "Categoría '" . htmlspecialchars($nombre_cat) . "' agregada con éxito.";
                
                // INVALIDAR CACHÉ DE CATEGORÍAS
                if (isset($redis_disponible) && $redis_disponible) {
                    $redis->del('catalogo_categorias');
                }
            } catch (\Exception $e) {
                $mensaje_error = "Error al guardar la categoría (puede que ya exista).";
            }
        }
    }
    
    // CASO B: Se envió una nueva Ubicación
    if (isset($_POST['accion']) && $_POST['accion'] === 'nueva_ubicacion') {
        $nombre_ubi = isset($_POST['nombre_ubicacion']) ? trim($_POST['nombre_ubicacion']) : '';
        
        if (!empty($nombre_ubi)) {
            try {
                $stmt = $conn->prepare("INSERT INTO ubicaciones (nombre) VALUES (?)");
                $stmt->bind_param("s", $nombre_ubi);
                $stmt->execute();
                $mensaje_exito = "Ubicación '" . htmlspecialchars($nombre_ubi) . "' agregada con éxito.";
                
                // INVALIDAR CACHÉ DE UBICACIONES
                if (isset($redis_disponible) && $redis_disponible) {
                    $redis->del('catalogo_ubicaciones');
                }
            } catch (\Exception $e) {
                $mensaje_error = "Error al guardar la ubicación (puede que ya exista).";
            }
        }
    }

    // CASO C: Editar categoría
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar_categoria') {
        $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;
        $nombre_cat = isset($_POST['nombre_categoria']) ? trim($_POST['nombre_categoria']) : '';
        if ($categoria_id > 0 && !empty($nombre_cat)) {
            try {
                $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE id = ?");
                $stmt->bind_param("si", $nombre_cat, $categoria_id);
                if ($stmt->execute()) {
                    $mensaje_exito = "Categoría actualizada correctamente.";
                    if (isset($redis_disponible) && $redis_disponible) {
                        $redis->del('catalogo_categorias');
                    }
                }
            } catch (\Exception $e) {
                $mensaje_error = "No se pudo actualizar la categoría.";
            }
        }
    }

    // CASO D: Eliminar categoría
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_categoria') {
        $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;
        if ($categoria_id > 0) {
            $res_check = $conn->prepare("SELECT COUNT(*) AS total FROM activos WHERE categoria_id = ?");
            $res_check->bind_param('i', $categoria_id);
            $res_check->execute();
            $res_check->bind_result($count_activos);
            $res_check->fetch();
            $res_check->close();
            if ($count_activos > 0) {
                $mensaje_error = "No se puede eliminar la categoría porque está asociada a activos.";
            } else {
                $stmt = $conn->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->bind_param("i", $categoria_id);
                if ($stmt->execute()) {
                    $mensaje_exito = "Categoría eliminada correctamente.";
                    if (isset($redis_disponible) && $redis_disponible) {
                        $redis->del('catalogo_categorias');
                    }
                }
            }
        }
    }

    // CASO E: Editar ubicación
    if (isset($_POST['accion']) && $_POST['accion'] === 'editar_ubicacion') {
        $ubicacion_id = isset($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 0;
        $nombre_ubi = isset($_POST['nombre_ubicacion']) ? trim($_POST['nombre_ubicacion']) : '';
        if ($ubicacion_id > 0 && !empty($nombre_ubi)) {
            try {
                $stmt = $conn->prepare("UPDATE ubicaciones SET nombre = ? WHERE id = ?");
                $stmt->bind_param("si", $nombre_ubi, $ubicacion_id);
                if ($stmt->execute()) {
                    $mensaje_exito = "Ubicación actualizada correctamente.";
                    if (isset($redis_disponible) && $redis_disponible) {
                        $redis->del('catalogo_ubicaciones');
                    }
                }
            } catch (\Exception $e) {
                $mensaje_error = "No se pudo actualizar la ubicación.";
            }
        }
    }

    // CASO F: Eliminar ubicación
    if (isset($_POST['accion']) && $_POST['accion'] === 'eliminar_ubicacion') {
        $ubicacion_id = isset($_POST['ubicacion_id']) ? intval($_POST['ubicacion_id']) : 0;
        if ($ubicacion_id > 0) {
            $res_check = $conn->prepare("SELECT COUNT(*) AS total FROM activos WHERE ubicacion_id = ?");
            $res_check->bind_param('i', $ubicacion_id);
            $res_check->execute();
            $res_check->bind_result($count_activos);
            $res_check->fetch();
            $res_check->close();
            if ($count_activos > 0) {
                $mensaje_error = "No se puede eliminar la ubicación porque está asociada a activos.";
            } else {
                $stmt = $conn->prepare("DELETE FROM ubicaciones WHERE id = ?");
                $stmt->bind_param("i", $ubicacion_id);
                if ($stmt->execute()) {
                    $mensaje_exito = "Ubicación eliminada correctamente.";
                    if (isset($redis_disponible) && $redis_disponible) {
                        $redis->del('catalogo_ubicaciones');
                    }
                }
            }
        }
    }
}

// 4. Consultar los datos con lógica de CACHÉ (Redis First)

// ---- OBTENER CATEGORÍAS ----
$categorias = array();
$datos_cat_redis = (isset($redis_disponible) && $redis_disponible) ? $redis->get('catalogo_categorias') : false;

if ($datos_cat_redis) {
    // CACHE HIT: Convertir el JSON de Redis a Arreglo de PHP
    $categorias = json_decode($datos_cat_redis, true);
} else {
    // CACHE MISS: Consultar MySQL
    $res_cat = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    if ($res_cat) {
        while ($row = $res_cat->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
    // Guardar en Redis para la próxima (1 hora)
    if (isset($redis_disponible) && $redis_disponible) {
        $redis->setex('catalogo_categorias', 3600, json_encode($categorias));
    }
}

// ---- OBTENER UBICACIONES ----
$ubicaciones = array();
$datos_ubi_redis = (isset($redis_disponible) && $redis_disponible) ? $redis->get('catalogo_ubicaciones') : false;

if ($datos_ubi_redis) {
    // CACHE HIT
    $ubicaciones = json_decode($datos_ubi_redis, true);
} else {
    // CACHE MISS: Consultar MySQL
    $res_ubi = $conn->query("SELECT id, nombre FROM ubicaciones ORDER BY nombre ASC");
    if ($res_ubi) {
        while ($row = $res_ubi->fetch_assoc()) {
            $ubicaciones[] = $row;
        }
    }
    // Guardar en Redis para la próxima (1 hora)
    if (isset($redis_disponible) && $redis_disponible) {
        $redis->setex('catalogo_ubicaciones', 3600, json_encode($ubicaciones));
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogos - Lab Inno</title>
    <link rel="stylesheet" href="css/estilo.css">
    <style>
        .catalogos-container { display: flex; gap: 30px; margin-top: 20px; }
        .catalogo-seccion { background: white; padding: 20px; border-radius: 8px; flex: 1; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .form-inline { display: flex; gap: 10px; margin-bottom: 20px; }
        .form-inline input { flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
        .tabla-datos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-datos th, .tabla-datos td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .tabla-datos th { background-color: #f8fafc; font-weight: bold; }
        .alert-ok { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .alert-bad { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="dashboard-layout" style="display: flex;">
        <div class="sidebar">
            <h2>Lab Inno</h2>
            <a href="dashboard.php">Inicio (Dashboard)</a>
            <a href="catalogos.php" class="active">Categorías / Ubicaciones</a>
            <a href="inventario.php">Inventario (Activos)</a>
            <a href="prestamos.php">Préstamos</a>
            <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                <a href="auditoria.php">Auditoría</a>
            <?php endif; ?>
        </div>

        <div class="content" style="flex: 1; padding: 20px;">
            <div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Gestión de Catálogos Maestros</h1>
                <div>
                    <?php 
                    // Obtener el rol actual de la sesión
                    $rol_actual = $_SESSION['usuario_rol'] ?? 'usuario';
                    
                    // Definir dinámicamente el color de fondo y el texto según el rol del usuario logueado
                    switch ($rol_actual) {
                        case 'admin':
                            $color_fondo = '#dc2626'; // Rojo vibrante
                            $texto_badge = 'ADMIN';
                            break;
                        case 'tecnico':
                            $color_fondo = '#0284c7'; // Azul / Celeste profesional
                            $texto_badge = 'TÉCNICO';
                            break;
                        default:
                            $color_fondo = '#64748b'; // Gris elegante
                            $texto_badge = 'USUARIO';
                            break;
                    }
                    ?>

                    <span style="font-weight: bold; background: #e2e8f0; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        Nombre: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
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

            <div class="catalogos-container">
                
                <div class="catalogo-seccion">
                    <h3>Categorías de Activos</h3>
                    <p style="font-size:13px; color:#666; margin-bottom: 10px;">Ej. Laptops, Sensores, Herramientas, Pantallas.</p>
                    
                    <form action="catalogos.php" method="POST" class="form-inline">
                        <input type="hidden" name="accion" value="nueva_categoria">
                        <input type="text" name="nombre_categoria" placeholder="Nombre de la categoría..." required>
                        <button type="submit" class="btn-primary" style="padding: 8px 15px;">Agregar</button>
                    </form>

                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th style="width: 12%;">ID</th>
                                <th>Nombre de Categoría</th>
                                <th style="width: 28%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach($categorias as $row): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <form action="catalogos.php" method="POST" style="display:flex; gap: 8px; align-items:center; margin:0;">
                                                <input type="hidden" name="accion" value="editar_categoria">
                                                <input type="hidden" name="categoria_id" value="<?php echo $row['id']; ?>">
                                                <input type="text" name="nombre_categoria" value="<?php echo htmlspecialchars($row['nombre']); ?>" style="flex:1; padding:6px 8px; border:1px solid #ccc; border-radius:4px;" required>
                                                <button type="submit" style="padding:6px 12px; background:#2563eb; border:none; color:#fff; border-radius:4px; cursor:pointer;">Guardar</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="catalogos.php" method="POST" style="display:inline-block; margin:0;">
                                                <input type="hidden" name="accion" value="eliminar_categoria">
                                                <input type="hidden" name="categoria_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" style="padding:6px 12px; background:#dc2626; border:none; color:#fff; border-radius:4px; cursor:pointer;" onclick="return confirm('¿Eliminar esta categoría?');">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="color: #999; text-align: center; padding: 15px;">No hay categorías registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="catalogo-seccion">
                    <h3>Ubicaciones Físicas</h3>
                    <p style="font-size:13px; color:#666; margin-bottom: 10px;">Ej. Almacén A, Laboratorio de Cómputo, Estante 3.</p>
                    
                    <form action="catalogos.php" method="POST" class="form-inline">
                        <input type="hidden" name="accion" value="nueva_ubicacion">
                        <input type="text" name="nombre_ubicacion" placeholder="Nombre de la ubicación..." required>
                        <button type="submit" class="btn-primary" style="padding: 8px 15px;">Agregar</button>
                    </form>

                    <table class="tabla-datos">
                        <thead>
                            <tr>
                                <th style="width: 12%;">ID</th>
                                <th>Nombre de Ubicación</th>
                                <th style="width: 28%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($ubicaciones)): ?>
                                <?php foreach($ubicaciones as $row): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <form action="catalogos.php" method="POST" style="display:flex; gap: 8px; align-items:center; margin:0;">
                                                <input type="hidden" name="accion" value="editar_ubicacion">
                                                <input type="hidden" name="ubicacion_id" value="<?php echo $row['id']; ?>">
                                                <input type="text" name="nombre_ubicacion" value="<?php echo htmlspecialchars($row['nombre']); ?>" style="flex:1; padding:6px 8px; border:1px solid #ccc; border-radius:4px;" required>
                                                <button type="submit" style="padding:6px 12px; background:#2563eb; border:none; color:#fff; border-radius:4px; cursor:pointer;">Guardar</button>
                                            </form>
                                        </td>
                                        <td>
                                            <form action="catalogos.php" method="POST" style="display:inline-block; margin:0;">
                                                <input type="hidden" name="accion" value="eliminar_ubicacion">
                                                <input type="hidden" name="ubicacion_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" style="padding:6px 12px; background:#dc2626; border:none; color:#fff; border-radius:4px; cursor:pointer;" onclick="return confirm('¿Eliminar esta ubicación?');">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="color: #999; text-align: center; padding: 15px;">No hay ubicaciones registradas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>