START TRANSACTION;

INSERT INTO almacenes (clave,nombre,activo,created_at,updated_at) VALUES
('ARN','ALMACEN REGIONAL ZONA NORTE',1,NOW(),NOW()),
('ARMB','ALMACEN REGIONAL ZONA MONTAÑA BAJA',1,NOW(),NOW()),
('ARMA','ALMACEN REGIONAL ZONA MONTAÑA ALTA',1,NOW(),NOW()),
('ARTC','ALMACEN REGIONAL ZONA TIERRA CALIENTE',1,NOW(),NOW()),
('ARAC','ALMACEN REGIONAL ZONA ACAPULCO-COYUCA',1,NOW(),NOW()),
('AC','ALMACEN CENTRAL',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),activo=1,updated_at=NOW();

CREATE TEMPORARY TABLE carga_existencias (
    almacen_clave VARCHAR(30) NOT NULL,
    talla VARCHAR(20) NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL
);

INSERT INTO carga_existencias VALUES
('ARN','8','NINA',57),('ARN','10','NINA',84),('ARN','12','NINA',40),('ARN','14','NINA',66),('ARN','16','NINA',7),('ARN','18','NINA',805),('ARN','34','NINA',4146),('ARN','40','NINA',46),
('ARN','8','NINO',292),('ARN','10','NINO',8),('ARN','12','NINO',83),('ARN','14','NINO',101),('ARN','16','NINO',27),('ARN','18','NINO',8),('ARN','34','NINO',2100),('ARN','40','NINO',178),
('ARMB','8','NINA',3196),('ARMB','10','NINA',0),('ARMB','12','NINA',0),('ARMB','14','NINA',0),('ARMB','16','NINA',0),('ARMB','18','NINA',0),('ARMB','34','NINA',0),('ARMB','40','NINA',0),
('ARMB','8','NINO',0),('ARMB','10','NINO',10),('ARMB','12','NINO',0),('ARMB','14','NINO',0),('ARMB','16','NINO',0),('ARMB','18','NINO',20),('ARMB','34','NINO',0),('ARMB','40','NINO',0),
('ARMA','8','NINA',2240),('ARMA','10','NINA',0),('ARMA','12','NINA',0),('ARMA','14','NINA',0),('ARMA','16','NINA',0),('ARMA','18','NINA',0),('ARMA','34','NINA',200),('ARMA','40','NINA',0),
('ARMA','8','NINO',0),('ARMA','10','NINO',0),('ARMA','12','NINO',0),('ARMA','14','NINO',300),('ARMA','16','NINO',0),('ARMA','18','NINO',0),('ARMA','34','NINO',1200),('ARMA','40','NINO',0),
('ARTC','8','NINA',710),('ARTC','10','NINA',0),('ARTC','12','NINA',0),('ARTC','14','NINA',0),('ARTC','16','NINA',0),('ARTC','18','NINA',0),('ARTC','34','NINA',160),('ARTC','40','NINA',0),
('ARTC','8','NINO',0),('ARTC','10','NINO',0),('ARTC','12','NINO',95),('ARTC','14','NINO',0),('ARTC','16','NINO',0),('ARTC','18','NINO',0),('ARTC','34','NINO',650),('ARTC','40','NINO',70),
('ARAC','8','NINA',11600),('ARAC','10','NINA',2400),('ARAC','12','NINA',9790),('ARAC','14','NINA',12100),('ARAC','16','NINA',4480),('ARAC','18','NINA',6760),('ARAC','34','NINA',1690),('ARAC','40','NINA',130),
('ARAC','8','NINO',9390),('ARAC','10','NINO',0),('ARAC','12','NINO',6600),('ARAC','14','NINO',3800),('ARAC','16','NINO',810),('ARAC','18','NINO',1080),('ARAC','34','NINO',4160),('ARAC','40','NINO',70),
('AC','8','NINA',0),('AC','10','NINA',509),('AC','12','NINA',800),('AC','14','NINA',989),('AC','16','NINA',173),('AC','18','NINA',257),('AC','34','NINA',168),('AC','40','NINA',4),
('AC','8','NINO',499),('AC','10','NINO',1000),('AC','12','NINO',1562),('AC','14','NINO',922),('AC','16','NINO',161),('AC','18','NINO',173),('AC','34','NINO',179),('AC','40','NINO',0);

INSERT INTO almacen_existencias (almacen_id,talla_id,sexo,cantidad_fisica,cantidad_apartada,created_at,updated_at)
SELECT a.id,t.id,c.sexo,c.cantidad,0,NOW(),NOW()
FROM carga_existencias c
JOIN almacenes a ON a.clave=c.almacen_clave
JOIN tallas t ON t.talla=c.talla
ON DUPLICATE KEY UPDATE cantidad_fisica=VALUES(cantidad_fisica),cantidad_apartada=0,updated_at=NOW();

INSERT INTO almacen_movimientos (almacen_id,talla_id,sexo,tipo,cantidad,referencia_tipo,referencia_id,observaciones,fecha_movimiento,created_at)
SELECT a.id,t.id,c.sexo,'AJUSTE_ENTRADA',c.cantidad,'CARGA_INICIAL_2026',NULL,'Carga inicial de existencias proporcionada',NOW(),NOW()
FROM carga_existencias c
JOIN almacenes a ON a.clave=c.almacen_clave
JOIN tallas t ON t.talla=c.talla
WHERE c.cantidad>0
AND NOT EXISTS (
    SELECT 1 FROM almacen_movimientos m
    WHERE m.almacen_id=a.id AND m.talla_id=t.id AND m.sexo=c.sexo AND m.referencia_tipo='CARGA_INICIAL_2026'
);

DROP TEMPORARY TABLE carga_existencias;

COMMIT;
