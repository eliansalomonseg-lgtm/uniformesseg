-- =======================================================
-- 008_fase3_entregas_escuelas_qr_acuses.sql
-- Fase 3: Entregas directas a Planteles Escolares, Verificación QR y Acuses Digitales
-- =======================================================

USE uniformes_seg;

-- 1. Tabla de Entregas Finales a Planteles Escolares (Servicio Regional -> Escuela / Director CCT)
CREATE TABLE IF NOT EXISTS entregas_escuelas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    escuela_id BIGINT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NOT NULL,
    entrega_servicio_regional_id BIGINT UNSIGNED NULL,
    fecha_entrega DATE NOT NULL,
    recibido_por_nombre VARCHAR(255) NOT NULL,
    recibido_por_cargo VARCHAR(100) NOT NULL DEFAULT 'Director(a)',
    recibido_por_identificacion VARCHAR(100) NULL,
    recibido_por_telefono VARCHAR(50) NULL,
    entregado_por_nombre VARCHAR(255) NOT NULL,
    observaciones TEXT NULL,
    archivo_acuse VARCHAR(255) NULL,
    codigo_verificacion VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ent_esc_sol (solicitud_id),
    KEY idx_ent_esc_escuela (escuela_id),
    KEY idx_ent_esc_fecha (fecha_entrega),
    KEY idx_ent_esc_qr (codigo_verificacion),
    CONSTRAINT fk_ent_esc_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_uniformes(id) ON DELETE CASCADE,
    CONSTRAINT fk_ent_esc_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id),
    CONSTRAINT fk_ent_esc_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id),
    CONSTRAINT fk_ent_esc_entrega_sr FOREIGN KEY (entrega_servicio_regional_id) REFERENCES entregas_servicios_regionales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Detalle de Entregas Escolares por Talla y Género
CREATE TABLE IF NOT EXISTS entregas_escuelas_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrega_escuela_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad_entregada INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_eesc_talla_sexo (entrega_escuela_id, talla_id, sexo),
    CONSTRAINT fk_eesc_det_entrega FOREIGN KEY (entrega_escuela_id) REFERENCES entregas_escuelas(id) ON DELETE CASCADE,
    CONSTRAINT fk_eesc_det_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Agregar campos de QR y Acuse en entregas_servicios_regionales si no existen
SET @col_qr = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
      AND table_name = 'entregas_servicios_regionales' 
      AND column_name = 'codigo_verificacion'
);
SET @sql_qr = IF(@col_qr = 0, 'ALTER TABLE entregas_servicios_regionales ADD COLUMN codigo_verificacion VARCHAR(64) NULL UNIQUE AFTER estado', 'SELECT 1');
PREPARE stmt_qr FROM @sql_qr;
EXECUTE stmt_qr;
DEALLOCATE PREPARE stmt_qr;

SET @col_ac = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
      AND table_name = 'entregas_servicios_regionales' 
      AND column_name = 'archivo_acuse'
);
SET @sql_ac = IF(@col_ac = 0, 'ALTER TABLE entregas_servicios_regionales ADD COLUMN archivo_acuse VARCHAR(255) NULL AFTER codigo_verificacion', 'SELECT 1');
PREPARE stmt_ac FROM @sql_ac;
EXECUTE stmt_ac;
DEALLOCATE PREPARE stmt_ac;

-- 4. Generar código de verificación para entregas existentes que aún no tengan uno
UPDATE entregas_servicios_regionales
SET codigo_verificacion = SHA2(CONCAT(id, folio, RAND(), NOW()), 256)
WHERE codigo_verificacion IS NULL OR codigo_verificacion = '';
