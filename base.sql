-- 1. Usuarios: Gestión de identidad, roles y permisos
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'tecnico', 'usuario') DEFAULT 'usuario'
);

-- 2. Categorías: Clasificación de activos (ej. Sensores, Kits de Robótica)
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL
);

-- 3. Ubicaciones: Espacios físicos (ej. Lab 7, Almacén)
CREATE TABLE ubicaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL
);

-- 4. Activos: Registro maestro de dispositivos
CREATE TABLE activos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    numero_serie VARCHAR(100) UNIQUE NOT NULL,
    categoria_id INT,
    ubicacion_id INT,
    estado ENUM('DISPONIBLE', 'PRESTADO', 'MANTENIMIENTO', 'BAJA') DEFAULT 'DISPONIBLE',
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (ubicacion_id) REFERENCES ubicaciones(id)
);

-- 5. Movimientos: Trazabilidad de operaciones
CREATE TABLE movimientos_activos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    activo_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_evento ENUM('PRESTAMO', 'DEVOLUCION', 'MANTENIMIENTO', 'BAJA', 'ALTA') NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (activo_id) REFERENCES activos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- 6. Auditoría Avanzada: Registro detallado de cambios (Logs)
CREATE TABLE logs_auditoria (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_tabla VARCHAR(50),      -- Tabla afectada
    registro_id INT,               -- ID del registro afectado
    tipo_evento VARCHAR(20),       -- INSERT, UPDATE, DELETE
    valores_antiguos JSON,         -- Estado anterior
    valores_nuevos JSON,           -- Estado nuevo
    usuario_id INT,                -- Quién realizó la acción
    direccion_ip VARCHAR(45),      -- Desde dónde
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);


ALTER TABLE movimientos_activos 
ADD COLUMN fecha_vencimiento DATETIME NULL AFTER fecha_registro,
ADD COLUMN observaciones TEXT NULL AFTER fecha_vencimiento;

CREATE TRIGGER auditoria_inventario_insert
AFTER INSERT ON inventario
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (
        nombre_tabla, 
        registro_id, 
        tipo_evento, 
        valores_antiguos, 
        valores_nuevos, 
        usuario_id, 
        direccion_ip
    )
    VALUES (
        'inventario',
        NEW.id,
        'INSERT',
        NULL,
        JSON_OBJECT(
            'id', NEW.id, 
            'nombre', NEW.nombre, 
            'cantidad', NEW.cantidad, 
            'estado', NEW.estado
        ),
        @usuario_id, 
        @direccion_ip
    );
END;

DELIMITER $$

CREATE TRIGGER auditoria_activos_insert
AFTER INSERT ON activos
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (
        nombre_tabla, 
        registro_id, 
        tipo_evento, 
        valores_antiguos, 
        valores_nuevos, 
        usuario_id, 
        direccion_ip
    )
    VALUES (
        'activos',
        NEW.id,
        'INSERT',
        NULL,
        JSON_OBJECT(
            'id', NEW.id, 
            'nombre', NEW.nombre, 
            'numero_serie', NEW.numero_serie, 
            'categoria_id', NEW.categoria_id,
            'ubicacion_id', NEW.ubicacion_id,
            'estado', NEW.estado
        ),
        @usuario_id,   -- Recuerda setear esta variable en tu sesión antes de insertar
        @direccion_ip  -- Recuerda setear esta variable en tu sesión antes de insertar
    );
END$$

DELIMITER ;

-- 3. TRIGGER PARA ELIMINACIONES (AFTER DELETE)
DELIMITER $$

CREATE TRIGGER auditoria_activos_delete
AFTER DELETE ON activos
FOR EACH ROW
BEGIN
    INSERT INTO logs_auditoria (
        nombre_tabla, 
        registro_id, 
        tipo_evento, 
        valores_antiguos, 
        valores_nuevos, 
        usuario_id, 
        direccion_ip
    )
    VALUES (
        'activos',
        OLD.id,
        'DELETE',
        -- Respalda los datos que acaban de desaparecer para no perder el rastro (OLD)
        JSON_OBJECT(
            'id', OLD.id, 
            'nombre', OLD.nombre, 
            'numero_serie', OLD.numero_serie, 
            'categoria_id', OLD.categoria_id,
            'ubicacion_id', OLD.ubicacion_id,
            'estado', OLD.estado
        ),
        NULL, -- No hay valores nuevos porque el registro fue eliminado
        @usuario_id, 
        @direccion_ip
    );
END$$

DELIMITER ;