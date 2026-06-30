<?php
// web/src/backend/crear_admin.php
require_once 'conexion.php';

$nombre = 'Administrador Principal';
$email = 'admin@laboratorio.com';
$password_plana = 'admin123';
$rol = 'admin';

try {
    // 1. Verificar primero si el administrador ya fue creado anteriormente
    $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $resultado = $checkStmt->get_result();

    if ($resultado->fetch_assoc()) {
        echo "El usuario administrador ($email) ya existe en la base de datos. No es necesario crearlo de nuevo.";
        exit;
    }

    // 2. Encriptamos la contraseña de forma segura
    $password_hasheada = password_hash($password_plana, PASSWORD_DEFAULT);

    // 3. Preparar la inserción usando marcadores '?' nativos de MySQLi
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    
    // 4. Vincular los 4 strings ("ssss") de forma segura
    $stmt->bind_param("ssss", $nombre, $email, $password_hasheada, $rol);
    $stmt->execute();
    
    echo "¡Usuario administrador creado con éxito! Ya puedes iniciar sesión con: <b>$email</b> y la contraseña: <b>$password_plana</b>.";

} catch (\Exception $e) {
    // Captura cualquier excepción de MySQLi de manera limpia
    echo "Error interno al intentar crear el usuario: " . $e->getMessage();
}
?>