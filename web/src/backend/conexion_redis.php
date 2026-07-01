<?php
// backend/conexion_redis.php

$redis_disponible = false;

// Verificamos que la extensión de Redis esté instalada y activa en PHP
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        
        // Conectamos al contenedor de Redis (usando el nombre del servicio, asegúrate que sea 'redis_cache')
        if ($redis->connect('redis_cache', 6379)) {
            $redis_disponible = true;
        } else {
            $redis_disponible = false;
        }
        
    } catch (Exception $e) {
        // Si el contenedor de Redis está apagado o falla, lo atrapamos aquí para que la app no colapse
        $redis_disponible = false;
    }
} else {
    // Si la extensión no existe, desactivamos el uso de caché silenciosamente
    $redis_disponible = false;
}
?>