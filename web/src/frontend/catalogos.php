<?php
// web/src/frontend/catalogos.php

// 1. Asegurar y validar la sesión en el servidor
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bloqueo de seguridad dinámico y silencioso: si no hay sesión iniciada, expulsar al login raíz
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../index.html");
    exit;
}

// 2. Incluir la conexión a la base de datos (subiendo un nivel para buscar la carpeta backend)
require_once '../backend/conexion.php';

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
            } catch (\Exception $e) {
                $mensaje_error = "Error al guardar la ubicación (puede que ya exista).";
            }
        }
    }
}

// 4. Consultar los datos frescos para pintar las tablas de forma dinámica (Orden Alfabético)
$lista_categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
$lista_ubicaciones = $conn->query("SELECT id, nombre FROM ubicaciones ORDER BY nombre ASC");
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

                    <span style="font-weight: bold; background: <?php echo $color_fondo; ?>; color: white; padding: 5px 10px; border-radius: 4px; margin-right: 10px;">
                        <?php echo $texto_badge; ?>: <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>
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
                                <th style="width: 20%;">ID</th>
                                <th>Nombre de Categoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lista_categories = $lista_categorias): ?>
                                <?php if ($lista_categories->num_rows > 0): ?>
                                    <?php while($row = $lista_categories->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $row['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="color: #999; text-align: center; padding: 15px;">No hay categorías registradas.</td></tr>
                                <?php endif; ?>
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
                                <th style="width: 20%;">ID</th>
                                <th>Nombre de Ubicación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($lista_places = $lista_ubicaciones): ?>
                                <?php if ($lista_places->num_rows > 0): ?>
                                    <?php while($row = $lista_places->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $row['id']; ?></td>
                                            <td><strong><?php echo htmlspecialchars($row['nombre']); ?></strong></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="color: #999; text-align: center; padding: 15px;">No hay ubicaciones registradas.</td></tr>
                                <?php endif; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</body>
</html>