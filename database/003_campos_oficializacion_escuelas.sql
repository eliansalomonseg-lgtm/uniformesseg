ALTER TABLE escuelas
    ADD COLUMN clave_alterna VARCHAR(50) NULL AFTER cct,
    ADD COLUMN homo VARCHAR(30) NULL AFTER clave_alterna,
    ADD COLUMN clave_turno VARCHAR(20) NULL AFTER turno,
    ADD COLUMN region VARCHAR(100) NULL AFTER subnivel,
    ADD COLUMN clave_municipio VARCHAR(20) NULL AFTER region,
    ADD COLUMN clave_localidad VARCHAR(20) NULL AFTER municipio,
    ADD COLUMN control VARCHAR(100) NULL AFTER domicilio,
    ADD COLUMN sostenimiento VARCHAR(150) NULL AFTER control,
    ADD COLUMN tipo VARCHAR(150) NULL AFTER sostenimiento,
    ADD COLUMN caracterizacion_1 VARCHAR(150) NULL AFTER tipo,
    ADD COLUMN caracterizacion_2 VARCHAR(150) NULL AFTER caracterizacion_1,
    ADD COLUMN zona VARCHAR(50) NULL AFTER caracterizacion_2,
    ADD COLUMN jefatura VARCHAR(100) NULL AFTER zona,
    ADD COLUMN cct_servicio_regional VARCHAR(20) NULL AFTER jefatura,
    ADD COLUMN ambito VARCHAR(50) NULL AFTER cct_servicio_regional;

CREATE INDEX idx_escuela_cct_servicio_regional ON escuelas (cct_servicio_regional);
