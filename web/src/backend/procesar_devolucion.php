<?php
require_once 'conexion.php';
require_once 'conexion_redis.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Acceso denegado."));
    exit;
}

$activo_id = isset($_POST['activo_id']) ? intval($_POST['activo_id']) : 0;

if ($activo_id <= 0) {
    $destino = ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'tecnico')
        ? '../frontend/prestamos.php'
        : '../frontend/panel_usuario.php';
    header("Location: $destino?error=" . urlencode("Debe seleccionar un activo válido."));
    exit;
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("SELECT id, estado FROM activos WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $activo_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $activo = $resultado->fetch_assoc();

    if (!$activo) {
        throw new Exception('El activo no existe.');
    }

    if ($activo['estado'] !== 'PRESTADO') {
        throw new Exception('El activo no está actualmente prestado.');
    }

    $updateActivo = $conn->prepare("UPDATE activos SET estado = 'DISPONIBLE' WHERE id = ?");
    $updateActivo->bind_param('i', $activo_id);
    $updateActivo->execute();

    $tipo_evento = 'DEVOLUCION';
    $insertMovimiento = $conn->prepare("INSERT INTO movimientos_activos (activo_id, usuario_id, tipo_evento) VALUES (?, ?, ?)");
    $insertMovimiento->bind_param('iis', $activo_id, $_SESSION['usuario_id'], $tipo_evento);
    $insertMovimiento->execute();

    $conn->commit();

    if (isset($redis_disponible) && $redis_disponible) {
        $redis->del('lista_activos_disponibles');
        $redis->del('lista_activos_prestados');
        $redis->del('historial_movimientos');
        $redis->del('lista_inventario_activos');
    }

    $destino = ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'tecnico')
        ? '../frontend/prestamos.php'
        : '../frontend/panel_usuario.php';

    header("Location: $destino?exito=" . urlencode("Devolución registrada correctamente."));
    exit;
} catch (Exception $e) {
    $conn->rollback();
    $destino = ($_SESSION['usuario_rol'] === 'admin' || $_SESSION['usuario_rol'] === 'tecnico')
        ? '../frontend/prestamos.php'
        : '../frontend/panel_usuario.php';
    header("Location: $destino?error=" . urlencode("Error al registrar la devolución: " . $e->getMessage()));
    exit;
}
?>
