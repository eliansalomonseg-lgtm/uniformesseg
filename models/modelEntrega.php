<?php

declare(strict_types=1);

class modelEntrega
{
    public function __construct(private PDO $pdo) {}

    public function obtenerEntregas(): array
    {
        return $this->pdo->query("SELECT e.id,e.folio,e.fecha_programada,e.fecha_salida,e.estado,
            a.id almacen_id, a.nombre almacen, a.clave almacen_clave,
            sr.id servicio_regional_id, sr.nombre servicio_regional,
            COALESCE(SUM(d.cantidad_entregada),0) total
            FROM entregas_servicios_regionales e
            JOIN almacenes a ON a.id=e.almacen_id
            JOIN servicios_regionales sr ON sr.id=e.servicio_regional_id
            LEFT JOIN entregas_servicios_regionales_detalle d ON d.entrega_id=e.id
            GROUP BY e.id, e.folio, e.fecha_programada, e.fecha_salida, e.estado, a.id, a.nombre, a.clave, sr.id, sr.nombre
            ORDER BY e.id DESC")->fetchAll();
    }

    public function obtenerEntregaPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT e.*,a.nombre almacen,a.clave almacen_clave,sr.nombre servicio_regional
            FROM entregas_servicios_regionales e
            JOIN almacenes a ON a.id=e.almacen_id
            JOIN servicios_regionales sr ON sr.id=e.servicio_regional_id
            WHERE e.id=:id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerDetalle(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT t.talla,SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad_solicitada ELSE 0 END) solicitado_nino,SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad_entregada ELSE 0 END) entregado_nino,SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad_solicitada ELSE 0 END) solicitado_nina,SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad_entregada ELSE 0 END) entregado_nina
            FROM entregas_servicios_regionales_detalle d
            JOIN tallas t ON t.id=d.talla_id
            WHERE d.entrega_id=:id
            GROUP BY t.id,t.talla,t.orden
            ORDER BY t.orden");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    /**
     * Valida si un almacén y un Servicio Regional corresponden oficialmente entre sí.
     */
    public function sonCorrespondientes(int $almacenId, int $servicioRegionalId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM servicios_regionales sr
            LEFT JOIN almacenes_servicios_regionales asr ON asr.servicio_regional_id = sr.id AND asr.vigente = 1
            WHERE sr.id = :servicio_id 
              AND (asr.almacen_id = :almacen_id OR sr.almacen_id = :almacen_id2)");
        $stmt->execute(['servicio_id' => $servicioRegionalId, 'almacen_id' => $almacenId, 'almacen_id2' => $almacenId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // Fallback: Si el almacén es Central ('AC') y el servicio regional no tiene almacén regional propio
        $stmtFallback = $this->pdo->prepare("SELECT a.clave FROM almacenes a WHERE a.id = :almacen_id");
        $stmtFallback->execute(['almacen_id' => $almacenId]);
        $row = $stmtFallback->fetch();
        if ($row && $row['clave'] === 'AC') {
            return true;
        }

        return false;
    }

    /**
     * Obtiene el almacén oficial que le corresponde a un Servicio Regional.
     */
    public function obtenerAlmacenCorrespondiente(int $servicioRegionalId): array
    {
        $stmt = $this->pdo->prepare("SELECT a.*, sr.nombre servicio_nombre
            FROM servicios_regionales sr
            LEFT JOIN almacenes_servicios_regionales asr ON asr.servicio_regional_id = sr.id AND asr.vigente = 1
            JOIN almacenes a ON (a.id = asr.almacen_id OR (asr.almacen_id IS NULL AND a.id = sr.almacen_id))
            WHERE sr.id = :servicio_id AND a.activo = 1
            LIMIT 1");
        $stmt->execute(['servicio_id' => $servicioRegionalId]);
        $alm = $stmt->fetch();
        if ($alm) {
            return $alm;
        }

        // Fallback al Almacén Central
        $stmtCentral = $this->pdo->query("SELECT * FROM almacenes WHERE clave = 'AC' AND activo = 1 LIMIT 1");
        $central = $stmtCentral->fetch();
        if (!$central) {
            throw new DomainException('No se encontró un almacén activo correspondiente para el Servicio Regional.');
        }
        return $central;
    }

    public function obtenerComparativo(int $almacenId, int $servicioId): array
    {
        $stmt = $this->pdo->prepare("SELECT t.id,t.talla,
            COALESCE((
                SELECT SUM(sd.cantidad)
                FROM solicitudes_uniformes_detalle sd
                JOIN solicitudes_uniformes s ON s.id=sd.solicitud_id
                JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id=sd.solicitud_id AND se.escuela_id=sd.escuela_id
                WHERE sd.talla_id=t.id AND sd.sexo='NINO'
                  AND se.servicio_regional_id=:servicio
                  AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')
            ),0) solicitado_nino,
            COALESCE((
                SELECT ae.cantidad_fisica-ae.cantidad_apartada
                FROM almacen_existencias ae
                WHERE ae.talla_id=t.id AND ae.sexo='NINO' AND ae.almacen_id=:almacen
                LIMIT 1
            ),0) disponible_nino,
            COALESCE((
                SELECT SUM(sd.cantidad)
                FROM solicitudes_uniformes_detalle sd
                JOIN solicitudes_uniformes s ON s.id=sd.solicitud_id
                JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id=sd.solicitud_id AND se.escuela_id=sd.escuela_id
                WHERE sd.talla_id=t.id AND sd.sexo='NINA'
                  AND se.servicio_regional_id=:servicio2
                  AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')
            ),0) solicitado_nina,
            COALESCE((
                SELECT ae.cantidad_fisica-ae.cantidad_apartada
                FROM almacen_existencias ae
                WHERE ae.talla_id=t.id AND ae.sexo='NINA' AND ae.almacen_id=:almacen2
                LIMIT 1
            ),0) disponible_nina
        FROM tallas t
        WHERE t.activo=1
        ORDER BY t.orden");
        $stmt->execute(['servicio' => $servicioId, 'almacen' => $almacenId, 'servicio2' => $servicioId, 'almacen2' => $almacenId]);
        return $stmt->fetchAll();
    }

    public function registrarEnvioServicioRegional(int $almacenId, int $servicioId): int
    {
        if (!$this->sonCorrespondientes($almacenId, $servicioId)) {
            throw new DomainException('El almacén seleccionado no corresponde al Servicio Regional destinatario. Los almacenes y servicios regionales no pueden cruzarse.');
        }

        $this->pdo->beginTransaction();
        try {
            $solicitudes = $this->pdo->prepare("SELECT DISTINCT s.id
                FROM solicitudes_uniformes s
                JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id=s.id
                WHERE se.servicio_regional_id=:servicio_id
                  AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')
                ORDER BY s.id FOR UPDATE");
            $solicitudes->execute(['servicio_id' => $servicioId]);
            $idsSolicitudes = array_map('intval', array_column($solicitudes->fetchAll(), 'id'));
            if (!$idsSolicitudes) {
                throw new DomainException('No hay solicitudes pendientes para entregar a este Servicio Regional.');
            }

            $marcadores = implode(',', array_fill(0, count($idsSolicitudes), '?'));
            $params = array_merge([$servicioId], $idsSolicitudes);
            $detalleSolicitud = $this->pdo->prepare("SELECT sd.talla_id,sd.sexo,SUM(sd.cantidad) cantidad
                FROM solicitudes_uniformes_detalle sd
                JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id=sd.solicitud_id AND se.escuela_id=sd.escuela_id
                WHERE se.servicio_regional_id=? AND sd.solicitud_id IN ($marcadores)
                GROUP BY sd.talla_id,sd.sexo");
            $detalleSolicitud->execute($params);
            $detalles = $detalleSolicitud->fetchAll();
            if (!$detalles) {
                throw new DomainException('Las solicitudes pendientes no contienen cantidades para entregar a este Servicio Regional.');
            }

            $existencia = $this->pdo->prepare('SELECT id,cantidad_fisica,cantidad_apartada FROM almacen_existencias WHERE almacen_id=:almacen_id AND talla_id=:talla_id AND sexo=:sexo FOR UPDATE');
            foreach ($detalles as $detalle) {
                $existencia->execute(['almacen_id' => $almacenId, 'talla_id' => $detalle['talla_id'], 'sexo' => $detalle['sexo']]);
                $registro = $existencia->fetch();
                $disponible = $registro ? (int)$registro['cantidad_fisica'] - (int)$registro['cantidad_apartada'] : 0;
                if ($disponible < (int)$detalle['cantidad']) {
                    throw new DomainException('La existencia cambió y ya no alcanza para completar la entrega. Revise nuevamente las existencias.');
                }
            }

            $ultimoId = $this->pdo->query('SELECT id FROM entregas_servicios_regionales ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio = sprintf('UNIF-%s-%05d', date('Y'), (int)$ultimoId + 1);
            $codigoVerificacion = bin2hex(random_bytes(16));
            $entrega = $this->pdo->prepare("INSERT INTO entregas_servicios_regionales (folio,almacen_id,servicio_regional_id,fecha_salida,estado,codigo_verificacion,created_at,updated_at) VALUES (:folio,:almacen_id,:servicio_regional_id,NOW(),'ENTREGADO',:codigo,NOW(),NOW())");
            $entrega->execute(['folio' => $folio, 'almacen_id' => $almacenId, 'servicio_regional_id' => $servicioId, 'codigo' => $codigoVerificacion]);
            $entregaId = (int)$this->pdo->lastInsertId();

            $insertarDetalle = $this->pdo->prepare('INSERT INTO entregas_servicios_regionales_detalle (entrega_id,talla_id,sexo,cantidad_solicitada,cantidad_preparada,cantidad_entregada,cantidad_recibida) VALUES (:entrega_id,:talla_id,:sexo,:cantidad,:cantidad,:cantidad,0)');
            $descontar = $this->pdo->prepare('UPDATE almacen_existencias SET cantidad_fisica=cantidad_fisica-:cantidad,updated_at=NOW() WHERE almacen_id=:almacen_id AND talla_id=:talla_id AND sexo=:sexo');
            $movimiento = $this->pdo->prepare("INSERT INTO almacen_movimientos (almacen_id,talla_id,sexo,tipo,cantidad,referencia_tipo,referencia_id,observaciones,fecha_movimiento,created_at) VALUES (:almacen_id,:talla_id,:sexo,'SALIDA',:cantidad,'ENTREGA_SERVICIO_REGIONAL',:entrega_id,'Entrega directa a Servicio Regional',NOW(),NOW())");
            foreach ($detalles as $detalle) {
                $parametros = ['entrega_id' => $entregaId, 'talla_id' => $detalle['talla_id'], 'sexo' => $detalle['sexo'], 'cantidad' => $detalle['cantidad']];
                $insertarDetalle->execute($parametros);
                $descontar->execute(['almacen_id' => $almacenId, 'talla_id' => $detalle['talla_id'], 'sexo' => $detalle['sexo'], 'cantidad' => $detalle['cantidad']]);
                $movimiento->execute(['almacen_id' => $almacenId, 'talla_id' => $detalle['talla_id'], 'sexo' => $detalle['sexo'], 'cantidad' => $detalle['cantidad'], 'entrega_id' => $entregaId]);
            }

            $relacion = $this->pdo->prepare('INSERT IGNORE INTO entregas_solicitudes (entrega_id,solicitud_id) VALUES (:entrega_id,:solicitud_id)');
            $actualizarSolicitud = $this->pdo->prepare("UPDATE solicitudes_uniformes SET estado='INCLUIDA_EN_ENTREGA',updated_at=NOW() WHERE id=:id");
            foreach ($idsSolicitudes as $solicitudId) {
                $relacion->execute(['entrega_id' => $entregaId, 'solicitud_id' => $solicitudId]);
                $actualizarSolicitud->execute(['id' => $solicitudId]);
            }
            $this->pdo->commit();
            return $entregaId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Retorna el plan de entrega detallando qué almacén entregará a qué escuelas y Servicio Regional,
     * garantizando que cada Servicio Regional se vincule estrictamente con su almacén correspondiente.
     */
    public function obtenerPlanDespachoPorSolicitud(int $solicitudId): array
    {
        $stmt = $this->pdo->prepare('SELECT id, folio, estado FROM solicitudes_uniformes WHERE id = :id');
        $stmt->execute(['id' => $solicitudId]);
        $solicitud = $stmt->fetch();
        if (!$solicitud) {
            return [];
        }

        // Obtener los Servicios Regionales involucrados
        $stmtRegionales = $this->pdo->prepare("SELECT DISTINCT se.servicio_regional_id, sr.nombre servicio_nombre
            FROM solicitudes_uniformes_escuelas se
            JOIN servicios_regionales sr ON sr.id = se.servicio_regional_id
            WHERE se.solicitud_id = :id");
        $stmtRegionales->execute(['id' => $solicitudId]);
        $servicios = $stmtRegionales->fetchAll();

        $plan = [];

        foreach ($servicios as $sInfo) {
            $servicioId = (int)$sInfo['servicio_regional_id'];
            $servicioNombre = $sInfo['servicio_nombre'];
            $almacen = $this->obtenerAlmacenCorrespondiente($servicioId);

            // Escuelas de este servicio regional en la solicitud
            $stmtEscuelas = $this->pdo->prepare("SELECT e.id, e.cct, e.nombre,
                COALESCE((SELECT SUM(sd.cantidad) FROM solicitudes_uniformes_detalle sd WHERE sd.solicitud_id = :solicitud_id AND sd.escuela_id = e.id), 0) total
                FROM escuelas e
                JOIN solicitudes_uniformes_escuelas se ON se.escuela_id = e.id
                WHERE se.solicitud_id = :solicitud_id2 AND se.servicio_regional_id = :servicio_id");
            $stmtEscuelas->execute([
                'solicitud_id' => $solicitudId,
                'solicitud_id2' => $solicitudId,
                'servicio_id' => $servicioId,
            ]);
            $escuelas = $stmtEscuelas->fetchAll();

            // Total de uniformes para este Servicio Regional
            $piezasServicio = array_sum(array_column($escuelas, 'total'));

            $plan[] = [
                'servicio_id' => $servicioId,
                'servicio_nombre' => $servicioNombre,
                'almacen_id' => (int)$almacen['id'],
                'almacen_nombre' => $almacen['nombre'],
                'almacen_clave' => $almacen['clave'],
                'total_piezas' => (int)$piezasServicio,
                'escuelas' => $escuelas,
            ];
        }

        return $plan;
    }

    /**
     * Despacha la entrega de una solicitud multi-escuela o monorregional asegurando que
     * cada Servicio Regional sea entregado estrictamente desde su almacén correspondiente,
     * sin cruzar almacenes ni regiones.
     */
    public function despacharEntregaPorSolicitud(int $solicitudId): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, folio, estado FROM solicitudes_uniformes WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $solicitudId]);
            $solicitud = $stmt->fetch();
            if (!$solicitud) {
                throw new DomainException('La solicitud no existe.');
            }
            if (!in_array($solicitud['estado'], ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true)) {
                throw new DomainException('La solicitud ya fue entregada o no está pendiente.');
            }

            // 1. Obtener los Servicios Regionales involucrados en la solicitud
            $stmtRegionales = $this->pdo->prepare("SELECT DISTINCT se.servicio_regional_id, sr.nombre servicio_nombre
                FROM solicitudes_uniformes_escuelas se
                JOIN servicios_regionales sr ON sr.id = se.servicio_regional_id
                WHERE se.solicitud_id = :id");
            $stmtRegionales->execute(['id' => $solicitudId]);
            $servicios = $stmtRegionales->fetchAll();

            if (!$servicios) {
                throw new DomainException('La solicitud no tiene Servicios Regionales válidos asociados.');
            }

            $entregasCreadas = [];

            // 2. Procesar cada Servicio Regional con su respectivo almacén oficial
            foreach ($servicios as $sInfo) {
                $servicioId = (int)$sInfo['servicio_regional_id'];
                $servicioNombre = $sInfo['servicio_nombre'];

                // Buscar el almacén correspondiente al servicio regional
                $almacenCorrespondiente = $this->obtenerAlmacenCorrespondiente($servicioId);
                $almacenId = (int)$almacenCorrespondiente['id'];
                $almacenNombre = $almacenCorrespondiente['nombre'];

                // Obtener las cantidades requeridas por talla y sexo para este Servicio Regional específico
                $stmtDetalle = $this->pdo->prepare("SELECT sd.talla_id, sd.sexo, t.talla, SUM(sd.cantidad) cantidad
                    FROM solicitudes_uniformes_detalle sd
                    JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id = sd.solicitud_id AND se.escuela_id = sd.escuela_id
                    JOIN tallas t ON t.id = sd.talla_id
                    WHERE sd.solicitud_id = :solicitud_id AND se.servicio_regional_id = :servicio_id
                    GROUP BY sd.talla_id, sd.sexo, t.talla");
                $stmtDetalle->execute(['solicitud_id' => $solicitudId, 'servicio_id' => $servicioId]);
                $detalles = $stmtDetalle->fetchAll();

                if (!$detalles) {
                    $stmtDetalleFb = $this->pdo->prepare("SELECT sd.talla_id, sd.sexo, t.talla, SUM(sd.cantidad) cantidad
                        FROM solicitudes_uniformes_detalle sd
                        JOIN tallas t ON t.id = sd.talla_id
                        WHERE sd.solicitud_id = :solicitud_id
                        GROUP BY sd.talla_id, sd.sexo, t.talla");
                    $stmtDetalleFb->execute(['solicitud_id' => $solicitudId]);
                    $detalles = $stmtDetalleFb->fetchAll();
                }

                if (!$detalles) {
                    continue;
                }

                // Validar existencias físicas en el almacén correspondiente
                $existencia = $this->pdo->prepare('SELECT id, cantidad_fisica, cantidad_apartada
                    FROM almacen_existencias
                    WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo
                    FOR UPDATE');

                foreach ($detalles as $d) {
                    $existencia->execute(['almacen_id' => $almacenId, 'talla_id' => $d['talla_id'], 'sexo' => $d['sexo']]);
                    $reg = $existencia->fetch();
                    $disp = $reg ? (int)$reg['cantidad_fisica'] - (int)$reg['cantidad_apartada'] : 0;
                    if ($disp < (int)$d['cantidad']) {
                        $faltante = (int)$d['cantidad'] - $disp;
                        throw new DomainException("El {$almacenNombre} no tiene suficiente existencia en Talla {$d['talla']} ({$d['sexo']}) para entregar a {$servicioNombre}. Faltan {$faltante} uniformes.");
                    }
                }

                // Generar nuevo folio de entrega
                $ultimoId = $this->pdo->query('SELECT id FROM entregas_servicios_regionales ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
                $folioEntrega = sprintf('UNIF-%s-%05d', date('Y'), (int)$ultimoId + 1);
                $codigoVerificacion = bin2hex(random_bytes(16));

                // Insertar entrega con su almacén correspondiente y servicio regional correspondiente
                $insertEntrega = $this->pdo->prepare("INSERT INTO entregas_servicios_regionales
                    (folio, almacen_id, servicio_regional_id, fecha_salida, estado, codigo_verificacion, created_at, updated_at)
                    VALUES (:folio, :almacen_id, :servicio_regional_id, NOW(), 'ENTREGADO', :codigo, NOW(), NOW())");
                $insertEntrega->execute([
                    'folio' => $folioEntrega,
                    'almacen_id' => $almacenId,
                    'servicio_regional_id' => $servicioId,
                    'codigo' => $codigoVerificacion,
                ]);
                $entregaId = (int)$this->pdo->lastInsertId();

                $insertDetalle = $this->pdo->prepare('INSERT INTO entregas_servicios_regionales_detalle
                    (entrega_id, talla_id, sexo, cantidad_solicitada, cantidad_preparada, cantidad_entregada, cantidad_recibida)
                    VALUES (?, ?, ?, ?, ?, ?, 0)');
                $descontar = $this->pdo->prepare('UPDATE almacen_existencias
                    SET cantidad_fisica = cantidad_fisica - ?, updated_at = NOW()
                    WHERE almacen_id = ? AND talla_id = ? AND sexo = ?');
                $movimiento = $this->pdo->prepare("INSERT INTO almacen_movimientos
                    (almacen_id, talla_id, sexo, tipo, cantidad, referencia_tipo, referencia_id, observaciones, fecha_movimiento, created_at)
                    VALUES (?, ?, ?, 'SALIDA', ?, 'ENTREGA_SERVICIO_REGIONAL', ?, ?, NOW(), NOW())");

                $totalPiezas = 0;
                foreach ($detalles as $d) {
                    $cant = (int)$d['cantidad'];
                    $totalPiezas += $cant;
                    $insertDetalle->execute([
                        $entregaId,
                        $d['talla_id'],
                        $d['sexo'],
                        $cant,
                        $cant,
                        $cant,
                    ]);
                    $descontar->execute([
                        $cant,
                        $almacenId,
                        $d['talla_id'],
                        $d['sexo'],
                    ]);
                    $movimiento->execute([
                        $almacenId,
                        $d['talla_id'],
                        $d['sexo'],
                        $cant,
                        $entregaId,
                        "Entrega de solicitud {$solicitud['folio']} desde {$almacenNombre} a {$servicioNombre}",
                    ]);
                }

                $this->pdo->prepare('INSERT IGNORE INTO entregas_solicitudes (entrega_id, solicitud_id) VALUES (:entrega_id, :solicitud_id)')
                    ->execute(['entrega_id' => $entregaId, 'solicitud_id' => $solicitudId]);

                $entregasCreadas[] = [
                    'id' => $entregaId,
                    'folio' => $folioEntrega,
                    'almacen' => $almacenNombre,
                    'servicio' => $servicioNombre,
                    'total' => $totalPiezas,
                ];
            }

            if (!$entregasCreadas) {
                throw new DomainException('No se encontraron cantidades para despachar en esta solicitud.');
            }

            $this->pdo->prepare("UPDATE solicitudes_uniformes SET estado = 'INCLUIDA_EN_ENTREGA', updated_at = NOW() WHERE id = :id")
                ->execute(['id' => $solicitudId]);

            $this->pdo->commit();
            return $entregasCreadas;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    /**
     * Cancela una entrega oficial, restituyendo las existencias al almacén
     * y revirtiendo las solicitudes asociadas a 'PENDIENTE' si no tienen otras entregas activas.
     */
    public function cancelarEntrega(int $entregaId): string
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, folio, almacen_id, servicio_regional_id, estado FROM entregas_servicios_regionales WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $entregaId]);
            $entrega = $stmt->fetch();
            if (!$entrega) {
                throw new DomainException('La entrega especificada no existe.');
            }
            if ($entrega['estado'] === 'CANCELADO') {
                throw new DomainException('La entrega ya se encuentra cancelada.');
            }

            $almacenId = (int)$entrega['almacen_id'];
            $folio = (string)$entrega['folio'];

            // 1. Reintegrar existencias al inventario físico del almacén por cada talla y sexo
            $detalles = $this->pdo->prepare('SELECT talla_id, sexo, cantidad_entregada FROM entregas_servicios_regionales_detalle WHERE entrega_id = :id');
            $detalles->execute(['id' => $entregaId]);
            $filasDetalle = $detalles->fetchAll();

            $sumarStock = $this->pdo->prepare('UPDATE almacen_existencias 
                SET cantidad_fisica = cantidad_fisica + :cantidad, updated_at = NOW()
                WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo');

            $movimiento = $this->pdo->prepare("INSERT INTO almacen_movimientos 
                (almacen_id, talla_id, sexo, tipo, cantidad, referencia_tipo, referencia_id, observaciones, fecha_movimiento, created_at)
                VALUES (:almacen_id, :talla_id, :sexo, 'ENTRADA', :cantidad, 'CANCELACION_ENTREGA', :entrega_id, :obs, NOW(), NOW())");

            foreach ($filasDetalle as $row) {
                $cant = (int)$row['cantidad_entregada'];
                if ($cant > 0) {
                    $sumarStock->execute([
                        'cantidad' => $cant,
                        'almacen_id' => $almacenId,
                        'talla_id' => $row['talla_id'],
                        'sexo' => $row['sexo'],
                    ]);

                    $movimiento->execute([
                        'almacen_id' => $almacenId,
                        'talla_id' => $row['talla_id'],
                        'sexo' => $row['sexo'],
                        'cantidad' => $cant,
                        'entrega_id' => $entregaId,
                        'obs' => "Cancelación de entrega {$folio}. Reingreso de prendas a inventario.",
                    ]);
                }
            }

            // 2. Actualizar estado de la entrega a CANCELADO
            $this->pdo->prepare("UPDATE entregas_servicios_regionales SET estado = 'CANCELADO', updated_at = NOW() WHERE id = :id")
                ->execute(['id' => $entregaId]);

            // 3. Revertir solicitudes vinculadas a PENDIENTE si no tienen otras entregas vigentes
            $stmtSol = $this->pdo->prepare('SELECT solicitud_id FROM entregas_solicitudes WHERE entrega_id = :id');
            $stmtSol->execute(['id' => $entregaId]);
            $solicitudIds = $stmtSol->fetchAll(PDO::FETCH_COLUMN);

            $stmtOtras = $this->pdo->prepare("SELECT COUNT(*) FROM entregas_solicitudes es
                JOIN entregas_servicios_regionales e ON e.id = es.entrega_id
                WHERE es.solicitud_id = :solicitud_id AND e.id <> :entrega_id AND e.estado <> 'CANCELADO'");

            $stmtRevertir = $this->pdo->prepare("UPDATE solicitudes_uniformes SET estado = 'PENDIENTE', updated_at = NOW() WHERE id = :id");

            foreach ($solicitudIds as $solId) {
                $stmtOtras->execute(['solicitud_id' => $solId, 'entrega_id' => $entregaId]);
                $otrasActivas = (int)$stmtOtras->fetchColumn();
                if ($otrasActivas === 0) {
                    $stmtRevertir->execute(['id' => $solId]);
                }
            }

            $this->pdo->commit();
            return $folio;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina definitivamente una entrega del sistema. Si la entrega no estaba cancelada,
     * reintegra previamente las piezas al almacén para salvaguardar el inventario físico.
     */
    public function eliminarEntrega(int $entregaId): string
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, folio, almacen_id, servicio_regional_id, estado FROM entregas_servicios_regionales WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $entregaId]);
            $entrega = $stmt->fetch();
            if (!$entrega) {
                throw new DomainException('La entrega especificada no existe.');
            }

            $almacenId = (int)$entrega['almacen_id'];
            $folio = (string)$entrega['folio'];
            $estado = (string)$entrega['estado'];

            // 1. Si no estaba cancelada previamente, devolver el stock físico al almacén
            if ($estado !== 'CANCELADO') {
                $detalles = $this->pdo->prepare('SELECT talla_id, sexo, cantidad_entregada FROM entregas_servicios_regionales_detalle WHERE entrega_id = :id');
                $detalles->execute(['id' => $entregaId]);
                $filasDetalle = $detalles->fetchAll();

                $sumarStock = $this->pdo->prepare('UPDATE almacen_existencias 
                    SET cantidad_fisica = cantidad_fisica + :cantidad, updated_at = NOW()
                    WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo');

                foreach ($filasDetalle as $row) {
                    $cant = (int)$row['cantidad_entregada'];
                    if ($cant > 0) {
                        $sumarStock->execute([
                            'cantidad' => $cant,
                            'almacen_id' => $almacenId,
                            'talla_id' => $row['talla_id'],
                            'sexo' => $row['sexo'],
                        ]);
                    }
                }
            }

            // 2. Revertir solicitudes vinculadas a PENDIENTE si no tienen otras entregas vigentes
            $stmtSol = $this->pdo->prepare('SELECT solicitud_id FROM entregas_solicitudes WHERE entrega_id = :id');
            $stmtSol->execute(['id' => $entregaId]);
            $solicitudIds = $stmtSol->fetchAll(PDO::FETCH_COLUMN);

            $stmtOtras = $this->pdo->prepare("SELECT COUNT(*) FROM entregas_solicitudes es
                JOIN entregas_servicios_regionales e ON e.id = es.entrega_id
                WHERE es.solicitud_id = :solicitud_id AND e.id <> :entrega_id AND e.estado <> 'CANCELADO'");

            $stmtRevertir = $this->pdo->prepare("UPDATE solicitudes_uniformes SET estado = 'PENDIENTE', updated_at = NOW() WHERE id = :id");

            foreach ($solicitudIds as $solId) {
                $stmtOtras->execute(['solicitud_id' => $solId, 'entrega_id' => $entregaId]);
                $otrasActivas = (int)$stmtOtras->fetchColumn();
                if ($otrasActivas === 0) {
                    $stmtRevertir->execute(['id' => $solId]);
                }
            }

            // 3. Eliminar movimientos de almacén vinculados a esta entrega
            $this->pdo->prepare("DELETE FROM almacen_movimientos 
                WHERE referencia_tipo IN ('ENTREGA_SERVICIO_REGIONAL', 'CANCELACION_ENTREGA') 
                  AND referencia_id = :entrega_id")
                ->execute(['entrega_id' => $entregaId]);

            // 4. Eliminar el registro de entrega (sus detalles y solicitudes vinculadas se eliminan en CASCADE)
            $this->pdo->prepare('DELETE FROM entregas_servicios_regionales WHERE id = :id')
                ->execute(['id' => $entregaId]);

            $this->pdo->commit();
            return $folio;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene las solicitudes asociadas a una entrega.
     */
    public function obtenerSolicitudesPorEntrega(int $entregaId): array
    {
        $stmt = $this->pdo->prepare("SELECT s.id, s.folio, s.estado, s.fecha_solicitud,
            (SELECT COUNT(*) FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id) total_escuelas
            FROM entregas_solicitudes es
            JOIN solicitudes_uniformes s ON s.id = es.solicitud_id
            WHERE es.entrega_id = :id
            ORDER BY s.id");
        $stmt->execute(['id' => $entregaId]);
        return $stmt->fetchAll();
    }
}
