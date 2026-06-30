<?php
// web/src/backend/verificar_sesion.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si la variable de sesión no existe en el servidor, no está logueado
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['autorizado' => false, 'error' => 'Sesión no iniciada o expirada.']);
    exit;
}

try {
    // 1. Cambiado al formato MySQLi usando el marcador '?'
    $stmt = $conn->prepare("SELECT nombre, rol FROM usuarios WHERE id = ? LIMIT 1");
    
    // 2. Vincular el parámetro de forma nativa ("i" de integer ya que el id es un número entero)
    $stmt->bind_param("i", $_SESSION['usuario_id']);
    $stmt->execute();
    
    // 3. Obtener el resultado y transformarlo en un array asociativo
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    if ($usuario) {
        http_response_code(200); // OK
        echo json_encode([
            'autorizado' => true,
            'usuario' => [
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol']
            ]
        ]);
    } else {
        // Por si el usuario fue eliminado de la BD mientras su sesión seguía abierta
        session_destroy();
        http_response_code(401);
        echo json_encode(['autorizado' => false, 'error' => 'Usuario no encontrado.']);
    }

} catch (\Exception $e) {
    // 4. Captura general de errores de MySQLi
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al verificar sesión.',
        'detalle' => $e->getMessage()
    ]);
}
?>