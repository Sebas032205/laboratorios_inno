<?php
// Usamos el nombre del servicio de Docker ('db') como host
$host = 'db'; 
$user = 'usuario';
$password = '1234';
$database = 'laboratorio';

// Crear la conexión usando MySQLi
$conn = new mysqli($host, $user, $password, $database);

// Comprobar si hubo un error
if ($conn->connect_error) {
    die("Error fatal: No se pudo conectar a la base de datos. " . $conn->connect_error);
}

// Opcional: Configurar el conjunto de caracteres a UTF-8
$conn->set_charset("utf8");


// ... (aquí va tu código actual de $conn = new mysqli(...))

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_usuario_sesion = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : 'NULL';
$ip_usuario_sesion = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Desconocida';

// Inyectamos las variables globales en la conexión actual de MySQL
$conn->query("SET @usuario_id = $id_usuario_sesion");
$conn->query("SET @direccion_ip = '$ip_usuario_sesion'");
?>