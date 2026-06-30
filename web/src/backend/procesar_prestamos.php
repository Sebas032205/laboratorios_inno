<?php
// web/src/backend/procesar_prestamo.php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Seguridad básica: Verificar origen POST y rol administrativo
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['usuario_id'])) {
    die("Acceso denegado.");
}

$usuario_id = isset($_POST['usuario_id']) ? intval($_POST['usuario_id']) : 0;
$activo_id  = isset($_POST['activo_id']) ? intval($_POST['activo_id']) : 0;

if ($usuario_id <= 0 || $activo_id <= 0) {
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Todos los campos son requeridos."));
    exit;
}

// INICIAR TRANSACCIÓN ATÓMICA DE MYSQLI
$conn->begin_transaction();

try {
    // 1. Cambiar el estado del activo de DISPONIBLE a PRESTADO
    $updateStmt = $conn->prepare("UPDATE activos SET estado = 'PRESTADO' WHERE id = ? AND estado = 'DISPONIBLE'");
    $updateStmt->bind_param("i", $activo_id);
    $updateStmt->execute();

    // Validar si la fila realmente cambió (si el equipo ya no estaba disponible, afectará 0 filas)
    if ($updateStmt->affected_rows === 0) {
        throw new \Exception("El equipo seleccionado ya no está disponible para préstamo.");
    }

    // 2. Insertar la traza del evento en movimientos_activos
    $tipo_evento = 'PRESTAMO';
    $insertStmt = $conn->prepare("INSERT INTO movimientos_activos (activo_id, usuario_id, tipo_evento) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iis", $activo_id, $usuario_id, $tipo_evento);
    $insertStmt->execute();

    // Si todo salió bien, guardamos cambios de forma permanente
    $conn->commit();
    
    header("Location: ../frontend/prestamos.php?exito=" . urlencode("¡Préstamo procesado y registrado con éxito!"));
    exit;

} catch (\Exception $e) {
    // Si algo falla, deshacemos todo lo ejecutado en la transacción
    $conn->rollback();
    header("Location: ../frontend/prestamos.php?error=" . urlencode("Error al procesar: " . $e->getMessage()));
    exit;
}
?>