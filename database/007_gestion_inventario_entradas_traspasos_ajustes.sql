-- =======================================================
-- 007_gestion_inventario_entradas_traspasos_ajustes.sql
-- Módulo completo de inventarios: Entradas, Traspasos, Ajustes/Mermas y Stock Mínimo
-- =======================================================

USE uniformes_seg;

-- 1. Tabla de Entradas de Almacén (Recepciones de Proveedor / Maquila / Compra)
CREATE TABLE IF NOT EXISTS almacen_entradas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    almacen_id INT UNSIGNED NOT NULL,
    proveedor_origen VARCHAR(255) NOT NULL,
    num_remision_factura VARCHAR(100) NULL,
    fecha_entrada DATE NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_entrada_almacen (almacen_id),
    KEY idx_entrada_fecha (fecha_entrada),
    CONSTRAINT fk_entrada_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS almacen_entradas_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrada_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entrada_talla_sexo (entrada_id, talla_id, sexo),
    CONSTRAINT fk_entrada_det_entrada FOREIGN KEY (entrada_id) REFERENCES almacen_entradas(id) ON DELETE CASCADE,
    CONSTRAINT fk_entrada_det_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. Tabla de Traspasos Directos entre Almacenes
CREATE TABLE IF NOT EXISTS almacen_traspasos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    almacen_origen_id INT UNSIGNED NOT NULL,
    almacen_destino_id INT UNSIGNED NOT NULL,
    fecha_traspaso DATE NOT NULL,
    transportista VARCHAR(255) NULL,
    observaciones TEXT NULL,
    estado ENUM('COMPLETADO','CANCELADO') NOT NULL DEFAULT 'COMPLETADO',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_traspaso_origen (almacen_origen_id),
    KEY idx_traspaso_destino (almacen_destino_id),
    KEY idx_traspaso_fecha (fecha_traspaso),
    CONSTRAINT fk_traspaso_alm_origen FOREIGN KEY (almacen_origen_id) REFERENCES almacenes(id),
    CONSTRAINT fk_traspaso_alm_destino FOREIGN KEY (almacen_destino_id) REFERENCES almacenes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS almacen_traspasos_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    traspaso_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_traspaso_talla_sexo (traspaso_id, talla_id, sexo),
    CONSTRAINT fk_traspaso_det_traspaso FOREIGN KEY (traspaso_id) REFERENCES almacen_traspasos(id) ON DELETE CASCADE,
    CONSTRAINT fk_traspaso_det_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 3. Tabla de Ajustes de Inventario y Mermas (Defectos, Daños o Regularización Física)
CREATE TABLE IF NOT EXISTS almacen_ajustes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    almacen_id INT UNSIGNED NOT NULL,
    tipo_ajuste ENUM('MERMA','AJUSTE_POSITIVO','AJUSTE_NEGATIVO') NOT NULL,
    motivo VARCHAR(255) NOT NULL,
    num_acta VARCHAR(100) NULL,
    fecha_ajuste DATE NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ajuste_almacen (almacen_id),
    KEY idx_ajuste_fecha (fecha_ajuste),
    CONSTRAINT fk_ajuste_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS almacen_ajustes_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ajuste_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ajuste_talla_sexo (ajuste_id, talla_id, sexo),
    CONSTRAINT fk_ajuste_det_ajuste FOREIGN KEY (ajuste_id) REFERENCES almacen_ajustes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ajuste_det_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 4. Campo de Stock Mínimo para el Semáforo de Reorden
SET @col_stock_min = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
      AND table_name = 'almacen_existencias' 
      AND column_name = 'stock_minimo'
);

SET @sql_stock_min = IF(
    @col_stock_min = 0, 
    'ALTER TABLE almacen_existencias ADD COLUMN stock_minimo INT UNSIGNED NOT NULL DEFAULT 50 AFTER cantidad_apartada', 
    'SELECT 1'
);
PREPARE stmt_stock_min FROM @sql_stock_min;
EXECUTE stmt_stock_min;
DEALLOCATE PREPARE stmt_stock_min;
