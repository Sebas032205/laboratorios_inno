<?php
// web/src/backend/api_catalogos.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'conexion.php';
session_start();

// SEGURIDAD: Solo usuarios logueados pueden interactuar con los catálogos
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Inicie sesión.']);
    exit;
}

// Validar qué catálogo se está solicitando
$tabla = isset($_GET['tabla']) ? $_GET['tabla'] : '';
if ($tabla !== 'categorias' && $tabla !== 'ubicaciones') {
    http_response_code(400);
    echo json_encode(['error' => 'Catálogo no válido. Use categorias o ubicaciones.']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        try {
            // Consultar todos los registros de la tabla solicitada
            $stmt = $pdo->query("SELECT id, nombre FROM $tabla ORDER BY nombre ASC");
            $resultados = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($resultados);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al consultar los datos.']);
        }
        break;

    case 'POST':
        // Leer el JSON del frontend
        $json = file_get_contents("php://input");
        $data = json_decode($json);

        if (!isset($data->nombre) || empty(trim($data->nombre))) {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio.']);
            exit;
        }

        $nombre = trim($data->nombre);

        try {
            // Insertar de forma segura usando sentencias preparadas
            $stmt = $pdo->prepare("INSERT INTO $tabla (nombre) VALUES (:nombre)");
            $stmt->execute([':nombre' => $nombre]);

            http_response_code(201);
            echo json_encode(['mensaje' => 'Registro agregado con éxito a ' . $tabla]);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al insertar en la base de datos.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido.']);
        break;
}
?>