<?php

declare(strict_types=1);

class modelSolicitud
{
    public function __construct(private PDO $pdo) {}

    public function buscarEscuelasParaSolicitud(string $busqueda): array
    {
        if ($busqueda === '') {
            return [];
        }

        $termino = '%' . $busqueda . '%';
        $stmt = $this->pdo->prepare("SELECT e.id, e.cct, e.nombre, e.nivel, e.municipio, e.localidad, sr.id servicio_regional_id, sr.nombre servicio_regional, e.cct_servicio_regional
            FROM escuelas e
            LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id = e.id AND esr.vigente = 1
            LEFT JOIN servicios_regionales sr ON sr.id = esr.servicio_regional_id
            WHERE e.cct LIKE :termino OR e.nombre LIKE :termino2
            ORDER BY CASE WHEN e.cct = :exacta THEN 0 ELSE 1 END, e.nombre
            LIMIT 25");
        $stmt->execute(['termino' => $termino, 'termino2' => $termino, 'exacta' => $busqueda]);
        return $stmt->fetchAll();
    }

    public function obtenerEscuelaParaSolicitud(int $escuelaId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT e.id, e.cct, e.nombre, e.nivel, e.municipio, e.localidad, sr.id servicio_regional_id, sr.nombre servicio_regional, e.cct_servicio_regional
            FROM escuelas e
            LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id = e.id AND esr.vigente = 1
            LEFT JOIN servicios_regionales sr ON sr.id = esr.servicio_regional_id
            WHERE e.id = :id");
        $stmt->execute(['id' => $escuelaId]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerEscuelasPorIds(array $escuelaIds): array
    {
        $ids = array_filter(array_map('intval', $escuelaIds));
        if (!$ids) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("SELECT e.id, e.cct, e.nombre, e.nivel, e.municipio, e.localidad, sr.id servicio_regional_id, sr.nombre servicio_regional, e.cct_servicio_regional
            FROM escuelas e
            LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id = e.id AND esr.vigente = 1
            LEFT JOIN servicios_regionales sr ON sr.id = esr.servicio_regional_id
            WHERE e.id IN ($marcadores)
            ORDER BY e.nombre");
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public function obtenerTallasActivas(): array
    {
        return $this->pdo->query('SELECT id, talla FROM tallas WHERE activo = 1 ORDER BY orden')->fetchAll();
    }

    public function obtenerDisponibilidadGeneral(): array
    {
        return $this->pdo->query("SELECT talla_id, sexo, COALESCE(SUM(cantidad_fisica - cantidad_apartada), 0) disponible
            FROM almacen_existencias
            GROUP BY talla_id, sexo")->fetchAll();
    }

    public function registrarSolicitud(array $escuelaIds, string $fechaSolicitud, ?string $cicloPeriodo, ?string $observaciones, array $cantidadesPorEscuela): int
    {
        $escuelaIdsUnicas = array_values(array_unique(array_filter(array_map('intval', $escuelaIds))));
        if (!$escuelaIdsUnicas) {
            throw new DomainException('Debe seleccionar al menos una escuela para registrar la solicitud.');
        }

        $escuelasValidadas = [];
        $serviciosRegionales = [];
        foreach ($escuelaIdsUnicas as $escuelaId) {
            $escuela = $this->obtenerEscuelaParaSolicitud($escuelaId);
            if (!$escuela) {
                throw new DomainException("La escuela con ID {$escuelaId} no existe.");
            }
            if (!$escuela['servicio_regional_id']) {
                throw new DomainException("La escuela {$escuela['nombre']} ({$escuela['cct']}) no tiene un Servicio Regional oficial vigente.");
            }
            $escuelasValidadas[$escuelaId] = $escuela;
            $serviciosRegionales[] = (int) $escuela['servicio_regional_id'];
        }

        $tallasActivas = $this->obtenerTallasActivas();
        $tallasIds = array_column($tallasActivas, 'id');
        $detalles = [];

        foreach ($escuelaIdsUnicas as $escuelaId) {
            $cantidadesEscuela = $cantidadesPorEscuela[$escuelaId] ?? [];
            foreach ($tallasIds as $tallaId) {
                foreach (['NINO', 'NINA'] as $sexo) {
                    $cantidad = $cantidadesEscuela[$sexo][$tallaId] ?? 0;
                    $cantidadInt = filter_var($cantidad, FILTER_VALIDATE_INT);
                    if ($cantidadInt === false || $cantidadInt < 0) {
                        throw new DomainException('Las cantidades de uniformes deben ser números enteros iguales o mayores a cero.');
                    }
                    if ($cantidadInt > 0) {
                        $detalles[] = [
                            'escuela_id' => $escuelaId,
                            'talla_id' => (int) $tallaId,
                            'sexo' => $sexo,
                            'cantidad' => $cantidadInt,
                        ];
                    }
                }
            }
        }

        if (!$detalles) {
            throw new DomainException('Capture al menos una cantidad mayor a cero en los uniformes solicitados.');
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaSolicitud);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaSolicitud) {
            throw new DomainException('La fecha de solicitud no es válida.');
        }

        $this->pdo->beginTransaction();
        try {
            $ultimoId = $this->pdo->query('SELECT id FROM solicitudes_uniformes ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $siguiente = (int) $ultimoId + 1;
            $folio = sprintf('SOL-%s-%05d', $fecha->format('Y'), $siguiente);

            $stmt = $this->pdo->prepare("INSERT INTO solicitudes_uniformes (folio, fecha_solicitud, ciclo_periodo, estado, observaciones)
                VALUES (:folio, :fecha_solicitud, :ciclo_periodo, 'PENDIENTE', :observaciones)");
            $stmt->execute([
                'folio' => $folio,
                'fecha_solicitud' => $fechaSolicitud,
                'ciclo_periodo' => $cicloPeriodo ?: null,
                'observaciones' => $observaciones ?: null,
            ]);
            $solicitudId = (int) $this->pdo->lastInsertId();

            $insertEscuela = $this->pdo->prepare('INSERT INTO solicitudes_uniformes_escuelas (solicitud_id, escuela_id, servicio_regional_id)
                VALUES (:solicitud_id, :escuela_id, :servicio_regional_id)');
            foreach ($escuelasValidadas as $escuelaId => $infoEscuela) {
                $insertEscuela->execute([
                    'solicitud_id' => $solicitudId,
                    'escuela_id' => $escuelaId,
                    'servicio_regional_id' => $infoEscuela['servicio_regional_id'],
                ]);
            }

            $insertDetalle = $this->pdo->prepare('INSERT INTO solicitudes_uniformes_detalle (solicitud_id, escuela_id, talla_id, sexo, cantidad)
                VALUES (:solicitud_id, :escuela_id, :talla_id, :sexo, :cantidad)');
            foreach ($detalles as $registro) {
                $insertDetalle->execute([
                    'solicitud_id' => $solicitudId,
                    'escuela_id' => $registro['escuela_id'],
                    'talla_id' => $registro['talla_id'],
                    'sexo' => $registro['sexo'],
                    'cantidad' => $registro['cantidad'],
                ]);
            }

            $this->pdo->commit();
            return $solicitudId;
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function obtenerSolicitudes(int $servicioId = 0): array
    {
        $sql = "SELECT s.id, s.folio, s.fecha_solicitud, s.estado,
            (SELECT COUNT(*) FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id) total_escuelas,
            (SELECT GROUP_CONCAT(CONCAT(e.cct, '||', e.nombre, '||', COALESCE(e.municipio,''), '||', COALESCE(e.localidad,'')) SEPARATOR '###')
             FROM solicitudes_uniformes_escuelas se JOIN escuelas e ON e.id = se.escuela_id WHERE se.solicitud_id = s.id) escuelas_resumen,
            (SELECT GROUP_CONCAT(DISTINCT sr.nombre ORDER BY sr.nombre SEPARATOR ', ')
             FROM solicitudes_uniformes_escuelas se JOIN servicios_regionales sr ON sr.id = se.servicio_regional_id WHERE se.solicitud_id = s.id) servicios_resumen,
            (SELECT COUNT(DISTINCT se.servicio_regional_id)
             FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id) total_servicios,
            (SELECT COALESCE(SUM(d.cantidad), 0) FROM solicitudes_uniformes_detalle d WHERE d.solicitud_id = s.id) total
        FROM solicitudes_uniformes s
        WHERE (:servicio = 0
            OR EXISTS (SELECT 1 FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id AND se.servicio_regional_id = :servicio2))
        ORDER BY s.fecha_solicitud DESC, s.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['servicio' => $servicioId, 'servicio2' => $servicioId]);
        $solicitudes = $stmt->fetchAll();

        foreach ($solicitudes as &$sol) {
            $escuelasList = [];
            if (!empty($sol['escuelas_resumen'])) {
                foreach (explode('###', $sol['escuelas_resumen']) as $escuelaStr) {
                    $partes = explode('||', $escuelaStr);
                    $escuelasList[] = [
                        'cct' => $partes[0] ?? '',
                        'nombre' => $partes[1] ?? '',
                        'municipio' => $partes[2] ?? '',
                        'localidad' => $partes[3] ?? '',
                    ];
                }
            }
            $sol['escuelas'] = $escuelasList;
            if (count($escuelasList) === 1) {
                $sol['cct'] = $escuelasList[0]['cct'];
                $sol['escuela'] = $escuelasList[0]['nombre'];
                $sol['municipio'] = $escuelasList[0]['municipio'];
                $sol['localidad'] = $escuelasList[0]['localidad'];
            } else {
                $sol['cct'] = count($escuelasList) . ' escuelas';
                $sol['escuela'] = count($escuelasList) . ' escuelas vinculadas';
                $sol['municipio'] = '';
                $sol['localidad'] = '';
            }
            $sol['servicio_regional'] = $sol['servicios_resumen'] ?: 'Varios Servicios Regionales';
        }
        unset($sol);

        return $solicitudes;
    }

    public function obtenerSolicitudPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT s.* FROM solicitudes_uniformes s WHERE s.id = :id');
        $stmt->execute(['id' => $id]);
        $solicitud = $stmt->fetch();
        if (!$solicitud) {
            return null;
        }

        $escuelas = $this->obtenerEscuelasPorSolicitud($id);
        $solicitud['escuelas'] = $escuelas;
        $solicitud['total_escuelas'] = count($escuelas);

        $servicios = array_values(array_unique(array_filter(array_column($escuelas, 'servicio_regional'))));
        $solicitud['servicios_regionales'] = $servicios;
        $solicitud['total_servicios'] = count($servicios);

        if (count($escuelas) === 1) {
            $solicitud['cct'] = $escuelas[0]['cct'];
            $solicitud['escuela'] = $escuelas[0]['escuela'];
            $solicitud['nivel'] = $escuelas[0]['nivel'];
            $solicitud['municipio'] = $escuelas[0]['municipio'];
            $solicitud['localidad'] = $escuelas[0]['localidad'];
            $solicitud['cct_servicio_regional'] = $escuelas[0]['cct_servicio_regional'];
            $solicitud['servicio_regional'] = $escuelas[0]['servicio_regional'];
        } else {
            $solicitud['cct'] = count($escuelas) . ' escuelas';
            $solicitud['escuela'] = count($escuelas) . ' escuelas vinculadas';
            $solicitud['nivel'] = 'Múltiples niveles';
            $solicitud['municipio'] = 'Varios municipios';
            $solicitud['localidad'] = '';
            $solicitud['cct_servicio_regional'] = '';
            $solicitud['servicio_regional'] = implode(', ', $servicios);
        }

        return $solicitud;
    }

    public function obtenerEscuelasPorSolicitud(int $solicitudId): array
    {
        $stmt = $this->pdo->prepare("SELECT se.escuela_id, e.cct, e.nombre escuela, e.nivel, e.municipio, e.localidad,
            e.cct_servicio_regional, sr.id servicio_regional_id, sr.nombre servicio_regional,
            COALESCE((SELECT SUM(d.cantidad) FROM solicitudes_uniformes_detalle d WHERE d.solicitud_id = se.solicitud_id AND d.escuela_id = se.escuela_id), 0) total_escuela
        FROM solicitudes_uniformes_escuelas se
        JOIN escuelas e ON e.id = se.escuela_id
        LEFT JOIN servicios_regionales sr ON sr.id = se.servicio_regional_id
        WHERE se.solicitud_id = :id
        ORDER BY e.nombre");
        $stmt->execute(['id' => $solicitudId]);
        return $stmt->fetchAll();
    }

    public function obtenerDetallePorTalla(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT t.talla,
            COALESCE(SUM(CASE WHEN d.sexo = 'NINO' THEN d.cantidad ELSE 0 END), 0) nino,
            COALESCE(SUM(CASE WHEN d.sexo = 'NINA' THEN d.cantidad ELSE 0 END), 0) nina,
            COALESCE(SUM(d.cantidad), 0) total
        FROM tallas t
        LEFT JOIN solicitudes_uniformes_detalle d ON d.talla_id = t.id AND d.solicitud_id = :id
        WHERE t.activo = 1
        GROUP BY t.id, t.talla, t.orden
        HAVING total > 0
        ORDER BY t.orden");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function obtenerDetallePorEscuelaYTalla(int $id): array
    {
        $escuelas = $this->obtenerEscuelasPorSolicitud($id);
        $tallas = $this->obtenerTallasActivas();

        $stmt = $this->pdo->prepare("SELECT d.escuela_id, d.talla_id, d.sexo, d.cantidad
            FROM solicitudes_uniformes_detalle d
            WHERE d.solicitud_id = :id");
        $stmt->execute(['id' => $id]);
        $detalles = $stmt->fetchAll();

        $matriz = [];
        foreach ($detalles as $d) {
            $matriz[(int)$d['escuela_id']][$d['sexo']][(int)$d['talla_id']] = (int)$d['cantidad'];
        }

        $resultado = [];
        foreach ($escuelas as $esc) {
            $escId = (int)$esc['escuela_id'];
            $tallasEscuela = [];
            $totalEscuela = 0;
            foreach ($tallas as $t) {
                $tId = (int)$t['id'];
                $nino = $matriz[$escId]['NINO'][$tId] ?? 0;
                $nina = $matriz[$escId]['NINA'][$tId] ?? 0;
                $totalTalla = $nino + $nina;
                $totalEscuela += $totalTalla;
                $tallasEscuela[] = [
                    'id' => $tId,
                    'talla' => $t['talla'],
                    'nino' => $nino,
                    'nina' => $nina,
                    'total' => $totalTalla,
                ];
            }
            $resultado[] = [
                'escuela' => $esc,
                'tallas' => $tallasEscuela,
                'total' => $totalEscuela,
            ];
        }

        return $resultado;
    }

    public function solicitudEsEditable(string $estado): bool
    {
        return in_array($estado, ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true);
    }

    public function actualizarSolicitud(int $solicitudId, array $escuelaIds, string $fechaSolicitud, ?string $cicloPeriodo, ?string $observaciones, array $cantidadesPorEscuela): void
    {
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaSolicitud);
        if (!$fecha || $fecha->format('Y-m-d') !== $fechaSolicitud) {
            throw new DomainException('La fecha de solicitud no es válida.');
        }

        $escuelaIdsUnicas = array_values(array_unique(array_filter(array_map('intval', $escuelaIds))));
        if (!$escuelaIdsUnicas) {
            throw new DomainException('Debe seleccionar al menos una escuela para la solicitud.');
        }

        $escuelasValidadas = [];
        $serviciosRegionales = [];
        foreach ($escuelaIdsUnicas as $escuelaId) {
            $escuela = $this->obtenerEscuelaParaSolicitud($escuelaId);
            if (!$escuela) {
                throw new DomainException("La escuela con ID {$escuelaId} no existe.");
            }
            if (!$escuela['servicio_regional_id']) {
                throw new DomainException("La escuela {$escuela['nombre']} ({$escuela['cct']}) no tiene un Servicio Regional oficial vigente.");
            }
            $escuelasValidadas[$escuelaId] = $escuela;
            $serviciosRegionales[] = (int) $escuela['servicio_regional_id'];
        }

        $tallasActivas = $this->obtenerTallasActivas();
        $tallasIds = array_column($tallasActivas, 'id');
        $detalles = [];

        foreach ($escuelaIdsUnicas as $escuelaId) {
            $cantidadesEscuela = $cantidadesPorEscuela[$escuelaId] ?? [];
            foreach ($tallasIds as $tallaId) {
                foreach (['NINO', 'NINA'] as $sexo) {
                    $cantidad = $cantidadesEscuela[$sexo][$tallaId] ?? 0;
                    $cantidadInt = filter_var($cantidad, FILTER_VALIDATE_INT);
                    if ($cantidadInt === false || $cantidadInt < 0) {
                        throw new DomainException('Las cantidades deben ser números enteros iguales o mayores a cero.');
                    }
                    if ($cantidadInt > 0) {
                        $detalles[] = [
                            'escuela_id' => $escuelaId,
                            'talla_id' => (int) $tallaId,
                            'sexo' => $sexo,
                            'cantidad' => $cantidadInt,
                        ];
                    }
                }
            }
        }

        if (!$detalles) {
            throw new DomainException('Capture al menos una cantidad mayor a cero en los uniformes solicitados.');
        }

        $this->pdo->beginTransaction();
        try {
            $bloqueo = $this->pdo->prepare('SELECT estado FROM solicitudes_uniformes WHERE id = :id FOR UPDATE');
            $bloqueo->execute(['id' => $solicitudId]);
            $estado = $bloqueo->fetchColumn();
            if (!$estado) {
                throw new DomainException('La solicitud no existe.');
            }
            if (!$this->solicitudEsEditable($estado)) {
                throw new DomainException('La solicitud ya forma parte de un envío y no puede modificarse.');
            }

            $actualizar = $this->pdo->prepare('UPDATE solicitudes_uniformes
                SET fecha_solicitud = :fecha_solicitud, ciclo_periodo = :ciclo_periodo, observaciones = :observaciones, updated_at = NOW()
                WHERE id = :id');
            $actualizar->execute([
                'fecha_solicitud' => $fechaSolicitud,
                'ciclo_periodo' => $cicloPeriodo ?: null,
                'observaciones' => $observaciones ?: null,
                'id' => $solicitudId,
            ]);

            $this->pdo->prepare('DELETE FROM solicitudes_uniformes_detalle WHERE solicitud_id = :id')->execute(['id' => $solicitudId]);
            $this->pdo->prepare('DELETE FROM solicitudes_uniformes_escuelas WHERE solicitud_id = :id')->execute(['id' => $solicitudId]);
            $insertEscuela = $this->pdo->prepare('INSERT INTO solicitudes_uniformes_escuelas (solicitud_id, escuela_id, servicio_regional_id)
                VALUES (:solicitud_id, :escuela_id, :servicio_regional_id)');
            foreach ($escuelasValidadas as $escuelaId => $infoEscuela) {
                $insertEscuela->execute([
                    'solicitud_id' => $solicitudId,
                    'escuela_id' => $escuelaId,
                    'servicio_regional_id' => $infoEscuela['servicio_regional_id'],
                ]);
            }

            $insertDetalle = $this->pdo->prepare('INSERT INTO solicitudes_uniformes_detalle (solicitud_id, escuela_id, talla_id, sexo, cantidad)
                VALUES (:solicitud_id, :escuela_id, :talla_id, :sexo, :cantidad)');
            foreach ($detalles as $registro) {
                $insertDetalle->execute([
                    'solicitud_id' => $solicitudId,
                    'escuela_id' => $registro['escuela_id'],
                    'talla_id' => $registro['talla_id'],
                    'sexo' => $registro['sexo'],
                    'cantidad' => $registro['cantidad'],
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function obtenerPendientesPorServicioRegional(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT s.id, s.folio,
            (SELECT COUNT(*) FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id) total_escuelas,
            (SELECT GROUP_CONCAT(CONCAT(e.cct, ' - ', e.nombre) SEPARATOR '||')
             FROM solicitudes_uniformes_escuelas se JOIN escuelas e ON e.id = se.escuela_id WHERE se.solicitud_id = s.id AND se.servicio_regional_id = :id) escuelas_resumen,
            (SELECT COALESCE(SUM(d.cantidad), 0)
             FROM solicitudes_uniformes_detalle d
             JOIN solicitudes_uniformes_escuelas se ON se.solicitud_id = d.solicitud_id AND se.escuela_id = d.escuela_id
             WHERE d.solicitud_id = s.id AND se.servicio_regional_id = :id2) total
        FROM solicitudes_uniformes s
        WHERE EXISTS (SELECT 1 FROM solicitudes_uniformes_escuelas se WHERE se.solicitud_id = s.id AND se.servicio_regional_id = :id3)
          AND s.estado IN ('PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION')
        ORDER BY s.fecha_solicitud");
        $stmt->execute(['id' => $id, 'id2' => $id, 'id3' => $id]);
        $resultados = $stmt->fetchAll();

        foreach ($resultados as &$res) {
            $partes = !empty($res['escuelas_resumen']) ? explode('||', $res['escuelas_resumen']) : [];
            $res['escuela'] = count($partes) === 1 ? $partes[0] : count($partes) . ' escuelas (' . implode(', ', array_slice($partes, 0, 2)) . (count($partes) > 2 ? '...' : '') . ')';
            $res['cct'] = '';
        }
        unset($res);

        return $resultados;
    }
}
