-- =======================================================
-- 009_modulo_entregas_directas_simplificado.sql
-- Módulo ligero y directo de control de entregas de uniformes
-- =======================================================

USE uniformes_seg;

CREATE TABLE IF NOT EXISTS entregas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    tipo_destino ENUM('ESCUELA','SERVICIO_REGIONAL') NOT NULL DEFAULT 'ESCUELA',
    escuela_id BIGINT UNSIGNED NULL,
    servicio_regional_id INT UNSIGNED NULL,
    fecha_entrega DATE NOT NULL,
    recibido_por_nombre VARCHAR(255) NOT NULL,
    recibido_por_cargo VARCHAR(100) NULL,
    recibido_por_telefono VARCHAR(50) NULL,
    entregado_por_nombre VARCHAR(255) NOT NULL,
    observaciones TEXT NULL,
    total_piezas INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ent_tipo (tipo_destino),
    KEY idx_ent_escuela (escuela_id),
    KEY idx_ent_servicio (servicio_regional_id),
    KEY idx_ent_fecha (fecha_entrega),
    CONSTRAINT fk_ent_simpl_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id) ON DELETE SET NULL,
    CONSTRAINT fk_ent_simpl_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entregas_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entrega_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ent_det_talla_sexo (entrega_id, talla_id, sexo),
    CONSTRAINT fk_ent_simpl_det_entrega FOREIGN KEY (entrega_id) REFERENCES entregas(id) ON DELETE CASCADE,
    CONSTRAINT fk_ent_simpl_det_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
