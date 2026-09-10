-- =======================================================
-- 004_solicitudes_multi_escuela.sql
-- Habilita solicitudes con múltiples escuelas y diversos
-- Servicios Regionales simultáneamente
-- =======================================================

USE uniformes_seg;

-- 1. Tabla relacional de escuelas vinculadas a la solicitud
CREATE TABLE IF NOT EXISTS solicitudes_uniformes_escuelas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    escuela_id BIGINT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_solicitud_escuela (solicitud_id, escuela_id),
    INDEX idx_sol_esc_escuela (escuela_id),
    INDEX idx_sol_esc_servicio (servicio_regional_id),
    CONSTRAINT fk_sol_esc_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_uniformes(id) ON DELETE CASCADE,
    CONSTRAINT fk_sol_esc_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id),
    CONSTRAINT fk_sol_esc_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Flexibilizar columnas en solicitudes_uniformes para solicitudes multi-escuela y multi-regionales
ALTER TABLE solicitudes_uniformes
    MODIFY COLUMN escuela_id BIGINT UNSIGNED NULL,
    MODIFY COLUMN servicio_regional_id INT UNSIGNED NULL;

-- 3. Agregar escuela_id a solicitudes_uniformes_detalle para rastrear las cantidades por escuela
SET @col_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND column_name = 'escuela_id');
SET @query_add_col = IF(@col_exists = 0, 'ALTER TABLE solicitudes_uniformes_detalle ADD COLUMN escuela_id BIGINT UNSIGNED NULL AFTER solicitud_id', 'SELECT 1');
PREPARE stmt_add_col FROM @query_add_col;
EXECUTE stmt_add_col;
DEALLOCATE PREPARE stmt_add_col;

-- 4. Migrar datos existentes a la nueva estructura relacional
INSERT IGNORE INTO solicitudes_uniformes_escuelas (solicitud_id, escuela_id, servicio_regional_id)
SELECT id, escuela_id, servicio_regional_id
FROM solicitudes_uniformes
WHERE escuela_id IS NOT NULL;

UPDATE solicitudes_uniformes_detalle d
JOIN solicitudes_uniformes s ON s.id = d.solicitud_id
SET d.escuela_id = s.escuela_id
WHERE d.escuela_id IS NULL AND s.escuela_id IS NOT NULL;

-- 5. Agregar índice explícito para la clave foránea de solicitud_id antes de eliminar el índice antiguo
SET @idx_sol_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND index_name = 'idx_solicitud_detalle_solicitud');
SET @query_add_sol_idx = IF(@idx_sol_exists = 0, 'ALTER TABLE solicitudes_uniformes_detalle ADD INDEX idx_solicitud_detalle_solicitud (solicitud_id)', 'SELECT 1');
PREPARE stmt_add_sol_idx FROM @query_add_sol_idx;
EXECUTE stmt_add_sol_idx;
DEALLOCATE PREPARE stmt_add_sol_idx;

-- Ahora es seguro eliminar uq_solicitud_talla_sexo
SET @idx_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND index_name = 'uq_solicitud_talla_sexo');
SET @query_drop_idx = IF(@idx_exists > 0, 'ALTER TABLE solicitudes_uniformes_detalle DROP INDEX uq_solicitud_talla_sexo', 'SELECT 1');
PREPARE stmt_drop_idx FROM @query_drop_idx;
EXECUTE stmt_drop_idx;
DEALLOCATE PREPARE stmt_drop_idx;

-- Crear el nuevo índice único que incluye escuela_id
SET @uq_esc_exists = (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND index_name = 'uq_solicitud_escuela_talla_sexo');
SET @query_add_uq = IF(@uq_esc_exists = 0, 'ALTER TABLE solicitudes_uniformes_detalle ADD UNIQUE KEY uq_solicitud_escuela_talla_sexo (solicitud_id, escuela_id, talla_id, sexo)', 'SELECT 1');
PREPARE stmt_add_uq FROM @query_add_uq;
EXECUTE stmt_add_uq;
DEALLOCATE PREPARE stmt_add_uq;

-- Agregar foreign key para escuela_id en detalle
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = 'solicitudes_uniformes_detalle' AND constraint_name = 'fk_solicitud_detalle_escuela');
SET @query_add_fk = IF(@fk_exists = 0, 'ALTER TABLE solicitudes_uniformes_detalle ADD CONSTRAINT fk_solicitud_detalle_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id)', 'SELECT 1');
PREPARE stmt_add_fk FROM @query_add_fk;
EXECUTE stmt_add_fk;
DEALLOCATE PREPARE stmt_add_fk;
