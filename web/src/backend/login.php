<?php
// web/src/backend/login.php
require_once 'conexion.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validar método POST nativo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Método no permitido.");
}

$email    = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($email) || empty($password)) {
    // CORRECCIÓN: Cambiado index.php a index.html
    header("Location: ../index.html?error=" . urlencode("Por favor, rellene todos los campos."));
    exit;
}

try {
    // 1. Uso correcto del marcador '?' de MySQLi con la variable global $conn
    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? LIMIT 1");
    
    // 2. Vincular el parámetro de forma nativa ("s" de string)
    $stmt->bind_param("s", $email);
    $stmt->execute();
    
    // 3. Obtener el resultado y transformarlo en un array asociativo
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();

    // 4. Verificar contraseña con el hash seguro de la base de datos
    if ($usuario && password_verify($password, $usuario['password'])) {
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_rol']    = $usuario['rol'];

        // Redirección exitosa al dashboard dinámico protegido
        header("Location: ../frontend/dashboard.php");
        exit;
    } else {
        // CORRECCIÓN: Cambiado index.php a index.html
        header("Location: ../index.html?error=" . urlencode("El correo o la contraseña son incorrectos."));
        exit;
    }
} catch (\Exception $e) {
    // 5. Captura general de errores/excepciones de MySQLi
    // CORRECCIÓN: Cambiado index.php a index.html
    header("Location: ../index.html?error=" . urlencode("Error en la base de datos: " . $e->getMessage()));
    exit;
}
?>