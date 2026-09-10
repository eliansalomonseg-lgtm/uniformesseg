-- =======================================================
-- 000_schema_base.sql
-- Creación del esquema base para uniformes_seg
-- =======================================================

CREATE DATABASE IF NOT EXISTS uniformes_seg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE uniformes_seg;

-- 1. Catálogo de Almacenes
CREATE TABLE IF NOT EXISTS almacenes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    servicio_regional_id INT UNSIGNED NULL,
    ubicacion VARCHAR(255) NULL,
    responsable VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Catálogo de Tallas
CREATE TABLE IF NOT EXISTS tallas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    talla VARCHAR(20) NOT NULL UNIQUE,
    orden INT NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Catálogo de Servicios Regionales
CREATE TABLE IF NOT EXISTS servicios_regionales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(30) NOT NULL UNIQUE,
    almacen_id INT UNSIGNED NULL,
    nombre VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Padrón base de Escuelas
CREATE TABLE IF NOT EXISTS escuelas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cct VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    nivel VARCHAR(100) NULL,
    subnivel VARCHAR(100) NULL,
    turno VARCHAR(50) NULL,
    municipio VARCHAR(100) NULL,
    localidad VARCHAR(100) NULL,
    domicilio TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_escuela_cct (cct)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Relación Escuelas - Servicios Regionales
CREATE TABLE IF NOT EXISTS escuelas_servicios_regionales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    escuela_id BIGINT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NOT NULL,
    vigente TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_escuela_servicio (escuela_id, servicio_regional_id),
    CONSTRAINT fk_esr_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_esr_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Existencias en Almacén
CREATE TABLE IF NOT EXISTS almacen_existencias (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    almacen_id INT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad_fisica INT UNSIGNED NOT NULL DEFAULT 0,
    cantidad_apartada INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_almacen_talla_sexo (almacen_id, talla_id, sexo),
    CONSTRAINT fk_existencia_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id),
    CONSTRAINT fk_existencia_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Movimientos de Almacén
CREATE TABLE IF NOT EXISTS almacen_movimientos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    almacen_id INT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    referencia_tipo VARCHAR(50) NULL,
    referencia_id BIGINT UNSIGNED NULL,
    observaciones TEXT NULL,
    fecha_movimiento DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movimiento_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id),
    CONSTRAINT fk_movimiento_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Entregas a Servicios Regionales
CREATE TABLE IF NOT EXISTS entregas_servicios_regionales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    almacen_id INT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NOT NULL,
    fecha_programada DATE NULL,
    fecha_salida DATETIME NULL,
    estado ENUM('PENDIENTE','PREPARANDO','LISTO','EN_TRASLADO','RECIBIDO','ENTREGADO','CANCELADO') NOT NULL DEFAULT 'ENTREGADO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_entrega_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id),
    CONSTRAINT fk_entrega_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Detalle de Entregas a Servicios Regionales
CREATE TABLE IF NOT EXISTS entregas_servicios_regionales_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrega_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad_solicitada INT UNSIGNED NOT NULL DEFAULT 0,
    cantidad_preparada INT UNSIGNED NOT NULL DEFAULT 0,
    cantidad_entregada INT UNSIGNED NOT NULL DEFAULT 0,
    cantidad_recibida INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entrega_talla_sexo (entrega_id, talla_id, sexo),
    CONSTRAINT fk_entrega_detalle_entrega FOREIGN KEY (entrega_id) REFERENCES entregas_servicios_regionales(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrega_detalle_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- Semilla de Catálogos Iniciales
-- =======================================================

-- Tallas
INSERT INTO tallas (talla, orden, activo) VALUES
('8', 1, 1),
('10', 2, 1),
('12', 3, 1),
('14', 4, 1),
('16', 5, 1),
('18', 6, 1),
('34', 7, 1),
('40', 8, 1)
ON DUPLICATE KEY UPDATE orden = VALUES(orden), activo = 1;

-- Servicios Regionales
INSERT INTO servicios_regionales (clave, nombre, activo) VALUES
('12ADG0002S', 'SERVICIOS REGIONALES ACAPULCO-COYUCA', 1),
('12ADG0003R', 'SERVICIOS REGIONALES CENTRO', 1),
('12ADG0004Q', 'SERVICIOS REGIONALES NORTE', 1),
('12ADG0005P', 'SERVICIOS REGIONALES TIERRA CALIENTE', 1),
('12ADG0006O', 'SERVICIOS REGIONALES COSTA GRANDE', 1),
('12ADG0007N', 'SERVICIOS REGIONALES COSTA CHICA', 1),
('12ADG0008M', 'SERVICIOS REGIONALES MONTAÑA BAJA', 1),
('12ADG0023E', 'SERVICIOS REGIONALES MONTAÑA ALTA', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), activo = 1;
