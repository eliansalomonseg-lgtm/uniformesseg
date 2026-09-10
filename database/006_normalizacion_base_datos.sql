-- =======================================================
-- 006_normalizacion_base_datos.sql
-- Normalización integral a 3NF y BCNF para uniformes_seg
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Catálogo Normalizado de Municipios (3NF en Escuelas)
CREATE TABLE IF NOT EXISTS municipios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave VARCHAR(10) NULL,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Poblar municipios únicos existentes en la tabla escuelas
INSERT INTO municipios (nombre, activo, created_at)
SELECT DISTINCT UPPER(TRIM(municipio)) AS nombre, 1, NOW()
FROM escuelas
WHERE municipio IS NOT NULL AND TRIM(municipio) != ''
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Agregar municipio_id en escuelas si no existe
SET @col_mun = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'escuelas' AND column_name = 'municipio_id');
SET @sql_mun = IF(@col_mun = 0, 'ALTER TABLE escuelas ADD COLUMN municipio_id INT UNSIGNED NULL AFTER clave_municipio, ADD CONSTRAINT fk_escuela_municipio FOREIGN KEY (municipio_id) REFERENCES municipios(id)', 'SELECT 1');
PREPARE stmt_mun FROM @sql_mun;
EXECUTE stmt_mun;
DEALLOCATE PREPARE stmt_mun;

-- Vincular municipios existentes en escuelas
UPDATE escuelas e
JOIN municipios m ON m.nombre = UPPER(TRIM(e.municipio))
SET e.municipio_id = m.id
WHERE e.municipio_id IS NULL AND e.municipio IS NOT NULL;


-- 2. Normalización de Asignación Almacenes ↔ Servicios Regionales (Eliminación de dependencia circular)
CREATE TABLE IF NOT EXISTS almacenes_servicios_regionales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    almacen_id INT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NOT NULL,
    vigente TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_servicio_regional_almacen (servicio_regional_id),
    KEY idx_asr_almacen (almacen_id),
    CONSTRAINT fk_asr_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id) ON DELETE CASCADE,
    CONSTRAINT fk_asr_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cargar asignaciones oficiales
INSERT INTO almacenes_servicios_regionales (almacen_id, servicio_regional_id, vigente) VALUES
(5, 1, 1), -- ARAC <-> ACAPULCO-COYUCA
(6, 2, 1), -- AC <-> CENTRO
(1, 3, 1), -- ARN <-> NORTE
(4, 4, 1), -- ARTC <-> TIERRA CALIENTE
(6, 5, 1), -- AC <-> COSTA GRANDE
(6, 6, 1), -- AC <-> COSTA CHICA
(2, 7, 1), -- ARMB <-> MONTAÑA BAJA
(3, 8, 1)  -- ARMA <-> MONTAÑA ALTA
ON DUPLICATE KEY UPDATE almacen_id = VALUES(almacen_id), vigente = 1;

-- Asegurar sincronización en servicios_regionales.almacen_id
UPDATE servicios_regionales sr
JOIN almacenes_servicios_regionales asr ON asr.servicio_regional_id = sr.id
SET sr.almacen_id = asr.almacen_id;

-- Eliminar la clave foránea circular en almacenes.servicio_regional_id si existe
SET @fk_alm = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'almacenes' AND constraint_name = 'fk_almacen_servicio');
SET @sql_fk_alm = IF(@fk_alm > 0, 'ALTER TABLE almacenes DROP FOREIGN KEY fk_almacen_servicio', 'SELECT 1');
PREPARE stmt_fk_alm FROM @sql_fk_alm;
EXECUTE stmt_fk_alm;
DEALLOCATE PREPARE stmt_fk_alm;

SET @col_alm_sr = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'almacenes' AND column_name = 'servicio_regional_id');
SET @sql_col_alm_sr = IF(@col_alm_sr > 0, 'ALTER TABLE almacenes DROP COLUMN servicio_regional_id', 'SELECT 1');
PREPARE stmt_col_alm_sr FROM @sql_col_alm_sr;
EXECUTE stmt_col_alm_sr;
DEALLOCATE PREPARE stmt_col_alm_sr;


-- 3. Normalización de solicitudes_uniformes (1NF / 2NF: eliminación de dependencias parciales en cabecera)
-- Asegurar que todas las solicitudes tengan sus escuelas en solicitudes_uniformes_escuelas antes de remover columnas
INSERT IGNORE INTO solicitudes_uniformes_escuelas (solicitud_id, escuela_id, servicio_regional_id)
SELECT s.id, s.escuela_id, s.servicio_regional_id
FROM solicitudes_uniformes s
WHERE s.escuela_id IS NOT NULL;

-- Asegurar que todas las filas de detalle tengan su escuela_id
UPDATE solicitudes_uniformes_detalle d
JOIN solicitudes_uniformes s ON s.id = d.solicitud_id
SET d.escuela_id = s.escuela_id
WHERE d.escuela_id IS NULL AND s.escuela_id IS NOT NULL;

-- Eliminar claves foráneas legacy en solicitudes_uniformes
SET @fk_sol_esc = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND constraint_name = 'fk_solicitud_escuela');
SET @sql_sol_esc = IF(@fk_sol_esc > 0, 'ALTER TABLE solicitudes_uniformes DROP FOREIGN KEY fk_solicitud_escuela', 'SELECT 1');
PREPARE stmt_sol_esc FROM @sql_sol_esc;
EXECUTE stmt_sol_esc;
DEALLOCATE PREPARE stmt_sol_esc;

SET @fk_sol_srv = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND constraint_name = 'fk_solicitud_servicio');
SET @sql_sol_srv = IF(@fk_sol_srv > 0, 'ALTER TABLE solicitudes_uniformes DROP FOREIGN KEY fk_solicitud_servicio', 'SELECT 1');
PREPARE stmt_sol_srv FROM @sql_sol_srv;
EXECUTE stmt_sol_srv;
DEALLOCATE PREPARE stmt_sol_srv;

-- Eliminar columnas redundantes en cabecera
SET @col_s_esc = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND column_name = 'escuela_id');
SET @sql_s_esc = IF(@col_s_esc > 0, 'ALTER TABLE solicitudes_uniformes DROP COLUMN escuela_id', 'SELECT 1');
PREPARE stmt_s_esc FROM @sql_s_esc;
EXECUTE stmt_s_esc;
DEALLOCATE PREPARE stmt_s_esc;

SET @col_s_srv = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND column_name = 'servicio_regional_id');
SET @sql_s_srv = IF(@col_s_srv > 0, 'ALTER TABLE solicitudes_uniformes DROP COLUMN servicio_regional_id', 'SELECT 1');
PREPARE stmt_s_srv FROM @sql_s_srv;
EXECUTE stmt_s_srv;
DEALLOCATE PREPARE stmt_s_srv;

SET @idx_s_srv = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND index_name = 'idx_solicitud_servicio_estado');
SET @sql_idx_s_srv = IF(@idx_s_srv > 0, 'ALTER TABLE solicitudes_uniformes DROP INDEX idx_solicitud_servicio_estado', 'SELECT 1');
PREPARE stmt_idx_s_srv FROM @sql_idx_s_srv;
EXECUTE stmt_idx_s_srv;
DEALLOCATE PREPARE stmt_idx_s_srv;



-- 4. Integridad Referencial Compuesta en solicitudes_uniformes_detalle (2NF estricta)
ALTER TABLE solicitudes_uniformes_detalle MODIFY COLUMN escuela_id BIGINT UNSIGNED NOT NULL;

-- Agregar clave foránea compuesta referenciando a solicitudes_uniformes_escuelas(solicitud_id, escuela_id) si no existe
SET @fk_det_comp = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND constraint_name = 'fk_solicitud_detalle_sol_esc');
SET @sql_det_comp = IF(@fk_det_comp = 0, 'ALTER TABLE solicitudes_uniformes_detalle ADD CONSTRAINT fk_solicitud_detalle_sol_esc FOREIGN KEY (solicitud_id, escuela_id) REFERENCES solicitudes_uniformes_escuelas(solicitud_id, escuela_id) ON DELETE CASCADE', 'SELECT 1');
PREPARE stmt_det_comp FROM @sql_det_comp;
EXECUTE stmt_det_comp;
DEALLOCATE PREPARE stmt_det_comp;


-- 5. Limpieza de ENUMs y tipos de dominio en Entregas
ALTER TABLE entregas_servicios_regionales 
MODIFY COLUMN estado ENUM('PENDIENTE','PREPARANDO','LISTO','RECIBIDO','ENTREGADO','CANCELADO') NOT NULL DEFAULT 'ENTREGADO';


-- 6. Índices para optimización de consultas
SET @idx_mov_alm = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'almacen_movimientos' AND index_name = 'idx_mov_almacen_fecha');
SET @sql_idx_mov = IF(@idx_mov_alm = 0, 'ALTER TABLE almacen_movimientos ADD INDEX idx_mov_almacen_fecha (almacen_id, fecha_movimiento)', 'SELECT 1');
PREPARE stmt_idx_mov FROM @sql_idx_mov;
EXECUTE stmt_idx_mov;
DEALLOCATE PREPARE stmt_idx_mov;

SET @idx_mov_ref = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'almacen_movimientos' AND index_name = 'idx_mov_referencia');
SET @sql_idx_ref = IF(@idx_mov_ref = 0, 'ALTER TABLE almacen_movimientos ADD INDEX idx_mov_referencia (referencia_tipo, referencia_id)', 'SELECT 1');
PREPARE stmt_idx_ref FROM @sql_idx_ref;
EXECUTE stmt_idx_ref;
DEALLOCATE PREPARE stmt_idx_ref;

SET @idx_sol_estado = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes' AND index_name = 'idx_solicitud_fecha_estado');
SET @sql_idx_sol = IF(@idx_sol_estado = 0, 'ALTER TABLE solicitudes_uniformes ADD INDEX idx_solicitud_fecha_estado (fecha_solicitud, estado)', 'SELECT 1');
PREPARE stmt_idx_sol FROM @sql_idx_sol;
EXECUTE stmt_idx_sol;
DEALLOCATE PREPARE stmt_idx_sol;

-- 7. Actualización de cascada en tabla intermedia entregas_solicitudes
ALTER TABLE entregas_solicitudes DROP FOREIGN KEY fk_entrega_solicitud_entrega;
ALTER TABLE entregas_solicitudes DROP FOREIGN KEY fk_entrega_solicitud_solicitud;
ALTER TABLE entregas_solicitudes ADD CONSTRAINT fk_entrega_solicitud_entrega FOREIGN KEY (entrega_id) REFERENCES entregas_servicios_regionales(id) ON DELETE CASCADE;
ALTER TABLE entregas_solicitudes ADD CONSTRAINT fk_entrega_solicitud_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_uniformes(id) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;
