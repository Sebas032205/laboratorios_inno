<?php
// web/src/backend/registrar_usuario.php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido.");
}

$nombre         = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
$email          = isset($_POST['email']) ? trim($_POST['email']) : '';
$password_plana = isset($_POST['password']) ? trim($_POST['password']) : '';
$rol            = isset($_POST['rol']) ? trim($_POST['rol']) : '';

if (empty($nombre) || empty($email) || empty($password_plana) || empty($rol)) {
    header("Location: ../frontend/registro.html?error=" . urlencode("Todos los campos son obligatorios."));
    exit;
}

$roles_permitidos = ['admin', 'tecnico', 'usuario'];
if (!in_array($rol, $roles_permitidos)) {
    header("Location: ../frontend/registro.html?error=" . urlencode("El rol seleccionado no es válido."));
    exit;
}

try {
    // 1. Verificar si el correo ya existe usando la sintaxis de MySQLi (?)
    $checkStmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $resultado = $checkStmt->get_result();
    
    if ($resultado->fetch_assoc()) {
        header("Location: ../frontend/registro.html?error=" . urlencode("El correo electrónico ya se encuentra registrado."));
        exit;
    }

    // 2. Encriptar la contraseña de forma segura
    $password_hasheada = password_hash($password_plana, PASSWORD_DEFAULT);

    // 3. Insertar el nuevo usuario usando MySQLi (?)
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $email, $password_hasheada, $rol);
    $stmt->execute();

    // MEJORA: Redirigir al login principal avisando que ya puede iniciar sesión
    header("Location: ../index.html?mensaje=" . urlencode("¡Usuario registrado exitosamente! Ya puede iniciar sesión."));
    exit;

} catch (\Exception $e) {
    header("Location: ../frontend/registro.html?error=" . urlencode("Error en la base de datos: " . $e->getMessage()));
    exit;
}
?>