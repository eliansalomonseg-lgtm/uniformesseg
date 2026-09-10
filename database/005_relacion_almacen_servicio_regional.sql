-- =======================================================
-- 005_relacion_almacen_servicio_regional.sql
-- Vinculación oficial entre Almacenes y Servicios Regionales
-- para evitar cruces en entregas e inventarios.
-- =======================================================

-- 1. Agregar columna almacen_id en servicios_regionales si no existe
SET @col_sr = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'servicios_regionales' AND column_name = 'almacen_id');
SET @sql_sr = IF(@col_sr = 0, 'ALTER TABLE servicios_regionales ADD COLUMN almacen_id INT UNSIGNED NULL AFTER clave, ADD CONSTRAINT fk_servicio_almacen FOREIGN KEY (almacen_id) REFERENCES almacenes(id)', 'SELECT 1');
PREPARE stmt_sr FROM @sql_sr;
EXECUTE stmt_sr;
DEALLOCATE PREPARE stmt_sr;

-- 2. Agregar columna servicio_regional_id en almacenes si no existe
SET @col_alm = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'almacenes' AND column_name = 'servicio_regional_id');
SET @sql_alm = IF(@col_alm = 0, 'ALTER TABLE almacenes ADD COLUMN servicio_regional_id INT UNSIGNED NULL AFTER clave, ADD CONSTRAINT fk_almacen_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id)', 'SELECT 1');
PREPARE stmt_alm FROM @sql_alm;
EXECUTE stmt_alm;
DEALLOCATE PREPARE stmt_alm;

-- 3. Mapeo oficial de correspondencia:
-- ACAPULCO-COYUCA <--> ALMACEN REGIONAL ZONA ACAPULCO-COYUCA (id: 5)
UPDATE servicios_regionales SET almacen_id = 5 WHERE id = 1;
UPDATE almacenes SET servicio_regional_id = 1 WHERE id = 5;

-- CENTRO <--> ALMACEN CENTRAL (id: 6)
UPDATE servicios_regionales SET almacen_id = 6 WHERE id = 2;
UPDATE almacenes SET servicio_regional_id = 2 WHERE id = 6;

-- NORTE <--> ALMACEN REGIONAL ZONA NORTE (id: 1)
UPDATE servicios_regionales SET almacen_id = 1 WHERE id = 3;
UPDATE almacenes SET servicio_regional_id = 3 WHERE id = 1;

-- TIERRA CALIENTE <--> ALMACEN REGIONAL ZONA TIERRA CALIENTE (id: 4)
UPDATE servicios_regionales SET almacen_id = 4 WHERE id = 4;
UPDATE almacenes SET servicio_regional_id = 4 WHERE id = 4;

-- COSTA GRANDE <--> ALMACEN CENTRAL (id: 6)
UPDATE servicios_regionales SET almacen_id = 6 WHERE id = 5;

-- COSTA CHICA <--> ALMACEN CENTRAL (id: 6)
UPDATE servicios_regionales SET almacen_id = 6 WHERE id = 6;

-- MONTAÑA BAJA <--> ALMACEN REGIONAL ZONA MONTAÑA BAJA (id: 2)
UPDATE servicios_regionales SET almacen_id = 2 WHERE id = 7;
UPDATE almacenes SET servicio_regional_id = 7 WHERE id = 2;

-- MONTAÑA ALTA <--> ALMACEN REGIONAL ZONA MONTAÑA ALTA (id: 3)
UPDATE servicios_regionales SET almacen_id = 3 WHERE id = 8;
UPDATE almacenes SET servicio_regional_id = 8 WHERE id = 3;

-- 4. Corregir entrega UNIF-2026-00003 (id: 3) para que tenga su almacén correspondiente (ARAC, id: 5)
UPDATE entregas_servicios_regionales SET almacen_id = 5 WHERE id = 3;
UPDATE almacen_movimientos SET almacen_id = 5 WHERE referencia_tipo = 'ENTREGA_SERVICIO_REGIONAL' AND referencia_id = 3;
