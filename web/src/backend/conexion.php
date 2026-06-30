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
?>