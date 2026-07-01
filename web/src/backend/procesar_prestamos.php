<?php
require_once 'conexion.php';
require_once 'conexion_redis.php'; // <-- 1. INCLUIR LA CONEXIÓN A REDIS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Acceso denegado."));
    exit;
}

if (!in_array($_SESSION['usuario_rol'] ?? '', ['admin', 'tecnico'], true)) {
    header("Location: ../frontend/prestamos.php?error=" . urlencode("No tiene permisos para procesar préstamos."));
    exit;
}

$usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
$activo_id  = isset($_POST['activo_id']) ? intval($_POST['activo_id']) : 0;

if ($usuario_id <= 0 || $activo_id <= 0) {
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Todos los campos son requeridos."));
    exit;
}

$conn->begin_transaction();

try {
    $updateStmt = $conn->prepare("UPDATE activos SET estado = 'PRESTADO' WHERE id = ? AND estado = 'DISPONIBLE'");
    $updateStmt->bind_param('i', $activo_id);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception('El equipo seleccionado ya no está disponible para préstamo.');
    }

    $tipo_evento = 'PRESTAMO';
    $insertStmt = $conn->prepare("INSERT INTO movimientos_activos (activo_id, usuario_id, tipo_evento) VALUES (?, ?, ?)");
    $insertStmt->bind_param('iis', $activo_id, $usuario_id, $tipo_evento);
    $insertStmt->execute();

    // Confirmamos los cambios en MySQL
    $conn->commit();

    // 2. LÓGICA REDIS: Invalidar las cachés afectadas
    // Solo borramos las llaves si Redis está conectado y funcionando.
    if (isset($redis_disponible) && $redis_disponible) {
        $redis->del('lista_activos_disponibles'); // Para que el equipo prestado desaparezca del <select>
        $redis->del('historial_movimientos');     // Para que la tabla muestre el nuevo préstamo
        $redis->del('lista_inventario_activos');  // Para que el inventario global refleje el nuevo estado "PRESTADO"
    }

    header("Location: ../frontend/prestamos.php?exito=" . urlencode("¡Préstamo procesado y registrado con éxito!"));
    exit;
} catch (Exception $e) {
    // Si algo falla, revertimos en MySQL
    $conn->rollback();
    // No borramos la caché aquí porque en la base de datos nada cambió realmente
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Error al procesar: " . $e->getMessage()));
    exit;
}
?>