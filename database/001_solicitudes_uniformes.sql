CREATE TABLE IF NOT EXISTS solicitudes_uniformes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(50) NOT NULL UNIQUE,
    escuela_id BIGINT UNSIGNED NOT NULL,
    servicio_regional_id INT UNSIGNED NOT NULL,
    fecha_solicitud DATE NOT NULL,
    ciclo_periodo VARCHAR(50) NULL,
    estado ENUM('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION','INCLUIDA_EN_ENTREGA','ATENDIDA_POR_ALMACEN','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
    observaciones TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_solicitud_escuela (escuela_id),
    INDEX idx_solicitud_servicio_estado (servicio_regional_id, estado),
    CONSTRAINT fk_solicitud_escuela FOREIGN KEY (escuela_id) REFERENCES escuelas(id),
    CONSTRAINT fk_solicitud_servicio FOREIGN KEY (servicio_regional_id) REFERENCES servicios_regionales(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS solicitudes_uniformes_detalle (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    talla_id INT UNSIGNED NOT NULL,
    sexo ENUM('NINO','NINA') NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_solicitud_talla_sexo (solicitud_id, talla_id, sexo),
    CONSTRAINT fk_solicitud_detalle_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_uniformes(id),
    CONSTRAINT fk_solicitud_detalle_talla FOREIGN KEY (talla_id) REFERENCES tallas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS entregas_solicitudes (
    entrega_id BIGINT UNSIGNED NOT NULL,
    solicitud_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (entrega_id, solicitud_id),
    CONSTRAINT fk_entrega_solicitud_entrega FOREIGN KEY (entrega_id) REFERENCES entregas_servicios_regionales(id),
    CONSTRAINT fk_entrega_solicitud_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitudes_uniformes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
