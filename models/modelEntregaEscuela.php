<?php

declare(strict_types=1);

class modelEntregaEscuela
{
    public function __construct(private PDO $pdo) {}

    /**
     * Registra el acta formal de entrega física de uniformes a un plantel escolar.
     */
    public function registrarEntregaEscuela(
        int $solicitudId,
        int $escuelaId,
        int $servicioRegionalId,
        ?int $entregaRegionalId,
        string $fechaEntrega,
        string $recibidoNombre,
        string $recibidoCargo,
        string $recibidoIden,
        string $recibidoTel,
        string $entregadoNombre,
        string $observaciones,
        array $cantidadesPorTalla
    ): int {
        if ($solicitudId <= 0 || $escuelaId <= 0 || $servicioRegionalId <= 0) {
            throw new DomainException('Identificadores de solicitud, escuela o Servicio Regional no válidos.');
        }

        $recibidoNombre = trim($recibidoNombre);
        if ($recibidoNombre === '') {
            throw new DomainException('El nombre de la persona que recibe en la escuela es obligatorio.');
        }

        $entregadoNombre = trim($entregadoNombre);
        if ($entregadoNombre === '') {
            throw new DomainException('El nombre del funcionario o enlace que entrega es obligatorio.');
        }

        if ($fechaEntrega === '') {
            $fechaEntrega = date('Y-m-d');
        }

        $items = $this->normalizarCantidades($cantidadesPorTalla);
        if (!$items) {
            throw new DomainException('Debe especificar al menos una prenda entregada a la escuela.');
        }

        $this->pdo->beginTransaction();
        try {
            // Verificar si la escuela ya cuenta con un acta previa para esta solicitud
            $stmtExiste = $this->pdo->prepare('SELECT id, folio FROM entregas_escuelas WHERE solicitud_id = :sol_id AND escuela_id = :esc_id');
            $stmtExiste->execute(['sol_id' => $solicitudId, 'esc_id' => $escuelaId]);
            $existente = $stmtExiste->fetch();
            if ($existente) {
                throw new DomainException("Esta escuela ya cuenta con un acta de entrega registrada (Folio: {$existente['folio']}).");
            }

            // Generar folio consecutivo ACTA-ESC-YYYY-XXXXX
            $ultimoId = (int)$this->pdo->query('SELECT id FROM entregas_escuelas ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio = sprintf('ACTA-ESC-%s-%05d', date('Y'), $ultimoId + 1);

            // Generar token único para verificación por Código QR
            $codigoVerificacion = hash('sha256', $folio . microtime() . random_bytes(16));

            $stmt = $this->pdo->prepare(
                'INSERT INTO entregas_escuelas (
                    folio, solicitud_id, escuela_id, servicio_regional_id, entrega_servicio_regional_id,
                    fecha_entrega, recibido_por_nombre, recibido_por_cargo, recibido_por_identificacion,
                    recibido_por_telefono, entregado_por_nombre, observaciones, codigo_verificacion,
                    created_at, updated_at
                ) VALUES (
                    :folio, :solicitud_id, :escuela_id, :servicio_regional_id, :entrega_sr_id,
                    :fecha_entrega, :recibido_nombre, :recibido_cargo, :recibido_iden,
                    :recibido_tel, :entregado_nombre, :observaciones, :codigo_verificacion,
                    NOW(), NOW()
                )'
            );
            $stmt->execute([
                'folio' => $folio,
                'solicitud_id' => $solicitudId,
                'escuela_id' => $escuelaId,
                'servicio_regional_id' => $servicioRegionalId,
                'entrega_sr_id' => $entregaRegionalId ?: null,
                'fecha_entrega' => $fechaEntrega,
                'recibido_nombre' => $recibidoNombre,
                'recibido_cargo' => trim($recibidoCargo) ?: 'Director(a)',
                'recibido_iden' => trim($recibidoIden) ?: null,
                'recibido_tel' => trim($recibidoTel) ?: null,
                'entregado_nombre' => $entregadoNombre,
                'observaciones' => trim($observaciones) ?: null,
                'codigo_verificacion' => $codigoVerificacion,
            ]);
            $entregaEscuelaId = (int)$this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO entregas_escuelas_detalle (entrega_escuela_id, talla_id, sexo, cantidad_entregada, created_at)
                 VALUES (:entrega_escuela_id, :talla_id, :sexo, :cantidad, NOW())'
            );

            foreach ($items as $item) {
                $stmtDetalle->execute([
                    'entrega_escuela_id' => $entregaEscuelaId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);
            }

            // Comprobar si todas las escuelas de esta solicitud ya recibieron sus uniformes
            $stmtTotalEscuelas = $this->pdo->prepare('SELECT COUNT(*) FROM solicitudes_uniformes_escuelas WHERE solicitud_id = :id');
            $stmtTotalEscuelas->execute(['id' => $solicitudId]);
            $totalEscuelas = (int)$stmtTotalEscuelas->fetchColumn();

            $stmtEntregadas = $this->pdo->prepare('SELECT COUNT(*) FROM entregas_escuelas WHERE solicitud_id = :id');
            $stmtEntregadas->execute(['id' => $solicitudId]);
            $entregadas = (int)$stmtEntregadas->fetchColumn();

            if ($totalEscuelas > 0 && $entregadas >= $totalEscuelas) {
                $stmtUpd = $this->pdo->prepare("UPDATE solicitudes_uniformes SET estado = 'ATENDIDA_POR_ALMACEN', updated_at = NOW() WHERE id = :id");
                $stmtUpd->execute(['id' => $solicitudId]);
            }

            $this->pdo->commit();
            return $entregaEscuelaId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el acta de entrega escolar completa por su ID.
     */
    public function obtenerEntregaEscuelaPorId(int $id): ?array
    {
        $sql = "SELECT ee.*, 
                       e.cct, e.nombre AS escuela_nombre, e.nivel AS escuela_nivel, 
                       COALESCE(m.nombre, e.municipio) AS escuela_municipio, e.localidad AS escuela_localidad,
                       sr.nombre AS servicio_regional_nombre, sr.clave AS servicio_regional_clave,
                       s.folio AS solicitud_folio, s.fecha_solicitud,
                       esr.folio AS entrega_regional_folio,
                       COALESCE(SUM(d.cantidad_entregada), 0) AS total_piezas,
                       COALESCE(SUM(CASE WHEN d.sexo = 'NINA' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nina,
                       COALESCE(SUM(CASE WHEN d.sexo = 'NINO' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nino
                FROM entregas_escuelas ee
                JOIN escuelas e ON e.id = ee.escuela_id
                LEFT JOIN municipios m ON m.id = e.municipio_id
                JOIN servicios_regionales sr ON sr.id = ee.servicio_regional_id
                JOIN solicitudes_uniformes s ON s.id = ee.solicitud_id
                LEFT JOIN entregas_servicios_regionales esr ON esr.id = ee.entrega_servicio_regional_id
                LEFT JOIN entregas_escuelas_detalle d ON d.entrega_escuela_id = ee.id
                WHERE ee.id = :id
                GROUP BY ee.id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $entrega = $stmt->fetch();
        if (!$entrega) {
            return null;
        }

        $stmtDetalle = $this->pdo->prepare(
            'SELECT d.*, t.talla, t.orden
             FROM entregas_escuelas_detalle d
             JOIN tallas t ON t.id = d.talla_id
             WHERE d.entrega_escuela_id = :id
             ORDER BY t.orden, d.sexo'
        );
        $stmtDetalle->execute(['id' => $id]);
        $entrega['detalle'] = $stmtDetalle->fetchAll();

        return $entrega;
    }

    /**
     * Consulta pública para el validador de Códigos QR (soporta tanto entregas escolares como regionales).
     */
    public function obtenerPorCodigoVerificacion(string $codigo): ?array
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return null;
        }

        // 1. Buscar en entregas_escuelas
        $stmtEsc = $this->pdo->prepare(
            "SELECT ee.id, ee.folio, ee.fecha_entrega AS fecha_oficial, 'ENTREGA_ESCUELA' AS tipo_documento,
                    ee.recibido_por_nombre, ee.recibido_por_cargo, ee.entregado_por_nombre,
                    e.cct, e.nombre AS escuela_nombre, e.nivel AS escuela_nivel,
                    COALESCE(m.nombre, e.municipio) AS municipio, e.localidad,
                    sr.nombre AS servicio_regional,
                    COALESCE(SUM(d.cantidad_entregada), 0) AS total_piezas,
                    COALESCE(SUM(CASE WHEN d.sexo = 'NINA' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nina,
                    COALESCE(SUM(CASE WHEN d.sexo = 'NINO' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nino,
                    ee.archivo_acuse, ee.created_at
             FROM entregas_escuelas ee
             JOIN escuelas e ON e.id = ee.escuela_id
             LEFT JOIN municipios m ON m.id = e.municipio_id
             JOIN servicios_regionales sr ON sr.id = ee.servicio_regional_id
             LEFT JOIN entregas_escuelas_detalle d ON d.entrega_escuela_id = ee.id
             WHERE ee.codigo_verificacion = :cod
             GROUP BY ee.id"
        );
        $stmtEsc->execute(['cod' => $codigo]);
        $resultado = $stmtEsc->fetch();
        if ($resultado) {
            $stmtDet = $this->pdo->prepare(
                'SELECT d.*, t.talla FROM entregas_escuelas_detalle d JOIN tallas t ON t.id = d.talla_id WHERE d.entrega_escuela_id = :id ORDER BY t.orden'
            );
            $stmtDet->execute(['id' => $resultado['id']]);
            $resultado['detalle'] = $stmtDet->fetchAll();
            return $resultado;
        }

        // 2. Buscar en entregas_servicios_regionales
        $stmtReg = $this->pdo->prepare(
            "SELECT esr.id, esr.folio, esr.fecha_salida AS fecha_oficial, 'ENTREGA_REGIONAL' AS tipo_documento,
                    sr.nombre AS servicio_regional, a.nombre AS almacen_origen,
                    esr.estado, esr.archivo_acuse, esr.created_at,
                    COALESCE(SUM(d.cantidad_entregada), 0) AS total_piezas,
                    COALESCE(SUM(CASE WHEN d.sexo = 'NINA' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nina,
                    COALESCE(SUM(CASE WHEN d.sexo = 'NINO' THEN d.cantidad_entregada ELSE 0 END), 0) AS total_nino
             FROM entregas_servicios_regionales esr
             JOIN almacenes a ON a.id = esr.almacen_id
             JOIN servicios_regionales sr ON sr.id = esr.servicio_regional_id
             LEFT JOIN entregas_servicios_regionales_detalle d ON d.entrega_id = esr.id
             WHERE esr.codigo_verificacion = :cod
             GROUP BY esr.id"
        );
        $stmtReg->execute(['cod' => $codigo]);
        $resReg = $stmtReg->fetch();
        if ($resReg) {
            $stmtDet = $this->pdo->prepare(
                'SELECT d.*, t.talla FROM entregas_servicios_regionales_detalle d JOIN tallas t ON t.id = d.talla_id WHERE d.entrega_id = :id ORDER BY t.orden'
            );
            $stmtDet->execute(['id' => $resReg['id']]);
            $resReg['detalle'] = $stmtDet->fetchAll();
            return $resReg;
        }

        return null;
    }

    /**
     * Guarda la ruta del archivo de acuse firmado físicamente.
     */
    public function guardarArchivoAcuse(int $id, string $rutaArchivo): bool
    {
        $stmt = $this->pdo->prepare('UPDATE entregas_escuelas SET archivo_acuse = :ruta, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id, 'ruta' => $rutaArchivo]);
    }

    /**
     * Guarda la ruta del archivo de acuse para una entrega regional.
     */
    public function guardarAcuseRegional(int $entregaId, string $rutaArchivo): bool
    {
        $stmt = $this->pdo->prepare('UPDATE entregas_servicios_regionales SET archivo_acuse = :ruta, updated_at = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $entregaId, 'ruta' => $rutaArchivo]);
    }

    /**
     * Obtiene las actas emitidas para una solicitud específica.
     */
    public function obtenerEntregasPorSolicitud(int $solicitudId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT ee.*, e.cct, e.nombre AS escuela,
                    COALESCE(SUM(d.cantidad_entregada), 0) AS total_piezas
             FROM entregas_escuelas ee
             JOIN escuelas e ON e.id = ee.escuela_id
             LEFT JOIN entregas_escuelas_detalle d ON d.entrega_escuela_id = ee.id
             WHERE ee.solicitud_id = :sol_id
             GROUP BY ee.id
             ORDER BY ee.fecha_entrega DESC, ee.id DESC"
        );
        $stmt->execute(['sol_id' => $solicitudId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene el acta de una escuela dentro de una solicitud (si ya existe).
     */
    public function obtenerEntregaPorEscuelaYSolicitud(int $solicitudId, int $escuelaId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ee.* FROM entregas_escuelas ee WHERE ee.solicitud_id = :sol_id AND ee.escuela_id = :esc_id LIMIT 1'
        );
        $stmt->execute(['sol_id' => $solicitudId, 'esc_id' => $escuelaId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Normaliza cantidades [sexo => [talla_id => cantidad]].
     */
    private function normalizarCantidades(array $postCantidades): array
    {
        $resultado = [];
        foreach (['NINO', 'NINA'] as $sexo) {
            $tallas = $postCantidades[$sexo] ?? [];
            foreach ((array)$tallas as $tallaId => $cant) {
                $idT = filter_var($tallaId, FILTER_VALIDATE_INT);
                $cantidad = filter_var($cant, FILTER_VALIDATE_INT);
                if ($idT && $cantidad && $cantidad > 0) {
                    $resultado[] = [
                        'talla_id' => $idT,
                        'sexo' => $sexo,
                        'cantidad' => $cantidad,
                    ];
                }
            }
        }
        return $resultado;
    }
}
