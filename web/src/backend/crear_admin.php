<?php
require_once 'conexion.php';

$nombre = 'Administrador Principal';
$email = getenv('ADMIN_EMAIL') ?: 'admin@laboratorio.com';
$password_plana = getenv('ADMIN_PASSWORD');
$rol = 'admin';

if (empty($password_plana)) {
    echo 'No se puede crear el administrador porque la variable de entorno ADMIN_PASSWORD no está configurada.';
    exit;
}

try {
    $checkStmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    $resultado = $checkStmt->get_result();

    if ($resultado->fetch_assoc()) {
        echo "El usuario administrador ($email) ya existe en la base de datos. No es necesario crearlo de nuevo.";
        exit;
    }

    $password_hasheada = password_hash($password_plana, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssss', $nombre, $email, $password_hasheada, $rol);
    $stmt->execute();

    echo "¡Usuario administrador creado con éxito! Ya puedes iniciar sesión con: <b>$email</b>.";
} catch (Exception $e) {
    echo 'Error interno al intentar crear el usuario: ' . $e->getMessage();
}
?>