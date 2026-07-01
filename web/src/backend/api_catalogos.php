<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'conexion.php';
require_once 'conexion_redis.php';
session_start();

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado. Inicie sesión.']);
    exit;
}

$tabla = isset($_GET['tabla']) ? trim($_GET['tabla']) : '';
$tablas_permitidas = ['categorias', 'ubicaciones'];

if (!in_array($tabla, $tablas_permitidas, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Catálogo no válido. Use categorias o ubicaciones.']);
    exit;
}

$clave_cache = 'catalogo_' . $tabla;
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $datos_cacheados = false;
        if (!empty($redis_disponible) && $redis_disponible) {
            $datos_cacheados = $redis->get($clave_cache);
        }

        if ($datos_cacheados !== false && $datos_cacheados !== '') {
            http_response_code(200);
            echo $datos_cacheados;
            exit;
        }

        try {
            $stmt = $conn->prepare("SELECT id, nombre FROM " . $tabla . " ORDER BY nombre ASC");
            $stmt->execute();
            $resultados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $json_respuesta = json_encode($resultados);

            if (!empty($redis_disponible) && $redis_disponible) {
                $redis->setex($clave_cache, 3600, $json_respuesta);
            }

            http_response_code(200);
            echo $json_respuesta;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al consultar los datos.']);
        }
        break;

    case 'POST':
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['nombre']) || trim((string) $data['nombre']) === '') {
            http_response_code(400);
            echo json_encode(['error' => 'El nombre es obligatorio.']);
            exit;
        }

        $nombre = trim((string) $data['nombre']);

        try {
            $stmt = $conn->prepare("INSERT INTO " . $tabla . " (nombre) VALUES (?)");
            $stmt->bind_param('s', $nombre);
            $stmt->execute();

            if (!empty($redis_disponible) && $redis_disponible) {
                $redis->del($clave_cache);
            }

            http_response_code(201);
            echo json_encode(['mensaje' => 'Registro agregado con éxito a ' . $tabla]);
        } catch (Exception $e) {
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