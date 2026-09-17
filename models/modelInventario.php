<?php

declare(strict_types=1);

class modelInventario
{
    public function __construct(private PDO $pdo) {}

    /**
     * Registra una entrada oficial de uniformes al almacén (proveedor, maquila o compra).
     */
    public function registrarEntrada(
        int $almacenId,
        string $proveedor,
        string $remision,
        string $fecha,
        string $observaciones,
        array $cantidadesPorTalla
    ): int {
        if ($almacenId <= 0) {
            throw new DomainException('Seleccione un almacén de destino válido.');
        }
        $proveedor = trim($proveedor);
        if ($proveedor === '') {
            throw new DomainException('El nombre o razón social del proveedor/origen es obligatorio.');
        }
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        $items = $this->normalizarCantidades($cantidadesPorTalla);
        if (!$items) {
            throw new DomainException('Debe ingresar al menos una prenda para registrar la entrada.');
        }

        $this->pdo->beginTransaction();
        try {
            // Generar folio consecutivo ENT-YYYY-XXXXX
            $ultimoId = (int)$this->pdo->query('SELECT id FROM almacen_entradas ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio = sprintf('ENT-%s-%05d', date('Y'), $ultimoId + 1);

            $stmt = $this->pdo->prepare(
                'INSERT INTO almacen_entradas (folio, almacen_id, proveedor_origen, num_remision_factura, fecha_entrada, observaciones, created_at, updated_at)
                 VALUES (:folio, :almacen_id, :proveedor, :remision, :fecha, :observaciones, NOW(), NOW())'
            );
            $stmt->execute([
                'folio' => $folio,
                'almacen_id' => $almacenId,
                'proveedor' => $proveedor,
                'remision' => trim($remision) ?: null,
                'fecha' => $fecha,
                'observaciones' => trim($observaciones) ?: null,
            ]);
            $entradaId = (int)$this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO almacen_entradas_detalle (entrada_id, talla_id, sexo, cantidad, created_at)
                 VALUES (:entrada_id, :talla_id, :sexo, :cantidad, NOW())'
            );

            $stmtExistencia = $this->pdo->prepare(
                'INSERT INTO almacen_existencias (almacen_id, talla_id, sexo, cantidad_fisica, cantidad_apartada, stock_minimo, created_at, updated_at)
                 VALUES (:almacen_id, :talla_id, :sexo, :cantidad, 0, 50, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE cantidad_fisica = cantidad_fisica + VALUES(cantidad_fisica), updated_at = NOW()'
            );

            $stmtMovimiento = $this->pdo->prepare(
                "INSERT INTO almacen_movimientos (almacen_id, talla_id, sexo, tipo, cantidad, referencia_tipo, referencia_id, observaciones, fecha_movimiento, created_at)
                 VALUES (:almacen_id, :talla_id, :sexo, 'ENTRADA', :cantidad, 'ENTRADA_PROVEEDOR', :ref_id, :obs, :fecha_mov, NOW())"
            );

            foreach ($items as $item) {
                $stmtDetalle->execute([
                    'entrada_id' => $entradaId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                $stmtExistencia->execute([
                    'almacen_id' => $almacenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                $obsMov = "Entrada Folio {$folio} | Prov: {$proveedor}" . ($remision ? " (Rem: {$remision})" : '');
                $stmtMovimiento->execute([
                    'almacen_id' => $almacenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                    'ref_id' => $entradaId,
                    'obs' => $obsMov,
                    'fecha_mov' => $fecha . ' ' . date('H:i:s'),
                ]);
            }

            $this->pdo->commit();
            return $entradaId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra un traspaso directo de uniformes entre dos almacenes.
     */
    public function registrarTraspaso(
        int $origenId,
        int $destinoId,
        string $fecha,
        string $transportista,
        string $observaciones,
        array $cantidadesPorTalla
    ): int {
        if ($origenId <= 0 || $destinoId <= 0) {
            throw new DomainException('Seleccione almacenes de origen y destino válidos.');
        }
        if ($origenId === $destinoId) {
            throw new DomainException('El almacén origen y el almacén destino no pueden ser el mismo.');
        }
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        $items = $this->normalizarCantidades($cantidadesPorTalla);
        if (!$items) {
            throw new DomainException('Debe ingresar al menos una prenda para realizar el traspaso.');
        }

        $this->pdo->beginTransaction();
        try {
            // Validar stock disponible en almacén origen con bloqueo
            $stmtCheck = $this->pdo->prepare(
                'SELECT cantidad_fisica, cantidad_apartada 
                 FROM almacen_existencias 
                 WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo 
                 FOR UPDATE'
            );

            $stmtTallaNombre = $this->pdo->prepare('SELECT talla FROM tallas WHERE id = :id');

            foreach ($items as $item) {
                $stmtCheck->execute([
                    'almacen_id' => $origenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                ]);
                $reg = $stmtCheck->fetch();
                $disponible = $reg ? (int)$reg['cantidad_fisica'] - (int)$reg['cantidad_apartada'] : 0;
                if ($disponible < $item['cantidad']) {
                    $stmtTallaNombre->execute(['id' => $item['talla_id']]);
                    $tallaNom = (string)$stmtTallaNombre->fetchColumn() ?: (string)$item['talla_id'];
                    $generoNom = $item['sexo'] === 'NINO' ? 'Niño' : 'Niña';
                    throw new DomainException(
                        "Stock insuficiente en origen para Talla {$tallaNom} ({$generoNom}). Disponible: {$disponible}, Solicitado: {$item['cantidad']}."
                    );
                }
            }

            // Generar folio consecutivo TRAS-YYYY-XXXXX
            $ultimoId = (int)$this->pdo->query('SELECT id FROM almacen_traspasos ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio = sprintf('TRAS-%s-%05d', date('Y'), $ultimoId + 1);

            $stmtTraspaso = $this->pdo->prepare(
                'INSERT INTO almacen_traspasos (folio, almacen_origen_id, almacen_destino_id, fecha_traspaso, transportista, observaciones, estado, created_at, updated_at)
                 VALUES (:folio, :origen_id, :destino_id, :fecha, :transportista, :observaciones, \'COMPLETADO\', NOW(), NOW())'
            );
            $stmtTraspaso->execute([
                'folio' => $folio,
                'origen_id' => $origenId,
                'destino_id' => $destinoId,
                'fecha' => $fecha,
                'transportista' => trim($transportista) ?: null,
                'observaciones' => trim($observaciones) ?: null,
            ]);
            $traspasoId = (int)$this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO almacen_traspasos_detalle (traspaso_id, talla_id, sexo, cantidad, created_at)
                 VALUES (:traspaso_id, :talla_id, :sexo, :cantidad, NOW())'
            );

            $stmtDescontarOrigen = $this->pdo->prepare(
                'UPDATE almacen_existencias 
                 SET cantidad_fisica = cantidad_fisica - :cantidad, updated_at = NOW() 
                 WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo'
            );

            $stmtSumarDestino = $this->pdo->prepare(
                'INSERT INTO almacen_existencias (almacen_id, talla_id, sexo, cantidad_fisica, cantidad_apartada, stock_minimo, created_at, updated_at)
                 VALUES (:almacen_id, :talla_id, :sexo, :cantidad, 0, 50, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE cantidad_fisica = cantidad_fisica + VALUES(cantidad_fisica), updated_at = NOW()'
            );

            $stmtMovimiento = $this->pdo->prepare(
                "INSERT INTO almacen_movimientos (almacen_id, talla_id, sexo, tipo, cantidad, referencia_tipo, referencia_id, observaciones, fecha_movimiento, created_at)
                 VALUES (:almacen_id, :talla_id, :sexo, :tipo, :cantidad, :ref_tipo, :ref_id, :obs, :fecha_mov, NOW())"
            );

            // Obtener nombres de almacenes para las observaciones de los movimientos
            $alms = $this->pdo->query("SELECT id, nombre FROM almacenes WHERE id IN ($origenId, $destinoId)")->fetchAll(PDO::FETCH_KEY_PAIR);
            $nomOrigen = $alms[$origenId] ?? 'Almacén Origen';
            $nomDestino = $alms[$destinoId] ?? 'Almacén Destino';

            foreach ($items as $item) {
                $stmtDetalle->execute([
                    'traspaso_id' => $traspasoId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                // Descontar en origen
                $stmtDescontarOrigen->execute([
                    'almacen_id' => $origenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                // Sumar en destino
                $stmtSumarDestino->execute([
                    'almacen_id' => $destinoId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                // Movimiento Salida Origen
                $stmtMovimiento->execute([
                    'almacen_id' => $origenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'tipo' => 'SALIDA',
                    'cantidad' => $item['cantidad'],
                    'ref_tipo' => 'TRASPASO_SALIDA',
                    'ref_id' => $traspasoId,
                    'obs' => "Traspaso Folio {$folio} enviado hacia {$nomDestino}",
                    'fecha_mov' => $fecha . ' ' . date('H:i:s'),
                ]);

                // Movimiento Entrada Destino
                $stmtMovimiento->execute([
                    'almacen_id' => $destinoId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'tipo' => 'ENTRADA',
                    'cantidad' => $item['cantidad'],
                    'ref_tipo' => 'TRASPASO_ENTRADA',
                    'ref_id' => $traspasoId,
                    'obs' => "Traspaso Folio {$folio} recibido desde {$nomOrigen}",
                    'fecha_mov' => $fecha . ' ' . date('H:i:s'),
                ]);
            }

            $this->pdo->commit();
            return $traspasoId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra un ajuste de inventario o merma (baja por daño o regularización).
     */
    public function registrarAjuste(
        int $almacenId,
        string $tipoAjuste,
        string $motivo,
        string $acta,
        string $fecha,
        string $observaciones,
        array $cantidadesPorTalla
    ): int {
        if ($almacenId <= 0) {
            throw new DomainException('Seleccione un almacén válido.');
        }
        if (!in_array($tipoAjuste, ['MERMA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO'], true)) {
            throw new DomainException('Tipo de ajuste no válido.');
        }
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new DomainException('El motivo o justificación del ajuste es obligatorio.');
        }
        if ($fecha === '') {
            $fecha = date('Y-m-d');
        }

        $items = $this->normalizarCantidades($cantidadesPorTalla);
        if (!$items) {
            throw new DomainException('Debe ingresar al menos una prenda para registrar el ajuste.');
        }

        $this->pdo->beginTransaction();
        try {
            // Si es ajuste negativo o merma, validar que no exceda el stock no apartado
            if (in_array($tipoAjuste, ['MERMA', 'AJUSTE_NEGATIVO'], true)) {
                $stmtCheck = $this->pdo->prepare(
                    'SELECT cantidad_fisica, cantidad_apartada 
                     FROM almacen_existencias 
                     WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo 
                     FOR UPDATE'
                );
                $stmtTallaNombre = $this->pdo->prepare('SELECT talla FROM tallas WHERE id = :id');

                foreach ($items as $item) {
                    $stmtCheck->execute([
                        'almacen_id' => $almacenId,
                        'talla_id' => $item['talla_id'],
                        'sexo' => $item['sexo'],
                    ]);
                    $reg = $stmtCheck->fetch();
                    $disponible = $reg ? (int)$reg['cantidad_fisica'] - (int)$reg['cantidad_apartada'] : 0;
                    if ($disponible < $item['cantidad']) {
                        $stmtTallaNombre->execute(['id' => $item['talla_id']]);
                        $tallaNom = (string)$stmtTallaNombre->fetchColumn() ?: (string)$item['talla_id'];
                        $generoNom = $item['sexo'] === 'NINO' ? 'Niño' : 'Niña';
                        throw new DomainException(
                            "No se puede ajustar a la baja la Talla {$tallaNom} ({$generoNom}). Disponible sin comprometer: {$disponible}, Ajuste solicitado: {$item['cantidad']}."
                        );
                    }
                }
            }

            // Generar folio consecutivo AJUS-YYYY-XXXXX
            $ultimoId = (int)$this->pdo->query('SELECT id FROM almacen_ajustes ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio = sprintf('AJUS-%s-%05d', date('Y'), $ultimoId + 1);

            $stmtAjuste = $this->pdo->prepare(
                'INSERT INTO almacen_ajustes (folio, almacen_id, tipo_ajuste, motivo, num_acta, fecha_ajuste, observaciones, created_at, updated_at)
                 VALUES (:folio, :almacen_id, :tipo_ajuste, :motivo, :num_acta, :fecha, :observaciones, NOW(), NOW())'
            );
            $stmtAjuste->execute([
                'folio' => $folio,
                'almacen_id' => $almacenId,
                'tipo_ajuste' => $tipoAjuste,
                'motivo' => $motivo,
                'num_acta' => trim($acta) ?: null,
                'fecha' => $fecha,
                'observaciones' => trim($observaciones) ?: null,
            ]);
            $ajusteId = (int)$this->pdo->lastInsertId();

            $stmtDetalle = $this->pdo->prepare(
                'INSERT INTO almacen_ajustes_detalle (ajuste_id, talla_id, sexo, cantidad, created_at)
                 VALUES (:ajuste_id, :talla_id, :sexo, :cantidad, NOW())'
            );

            $stmtUpdateNegativo = $this->pdo->prepare(
                'UPDATE almacen_existencias 
                 SET cantidad_fisica = cantidad_fisica - :cantidad, updated_at = NOW() 
                 WHERE almacen_id = :almacen_id AND talla_id = :talla_id AND sexo = :sexo'
            );

            $stmtUpdatePositivo = $this->pdo->prepare(
                'INSERT INTO almacen_existencias (almacen_id, talla_id, sexo, cantidad_fisica, cantidad_apartada, stock_minimo, created_at, updated_at)
                 VALUES (:almacen_id, :talla_id, :sexo, :cantidad, 0, 50, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE cantidad_fisica = cantidad_fisica + VALUES(cantidad_fisica), updated_at = NOW()'
            );

            $stmtMovimiento = $this->pdo->prepare(
                "INSERT INTO almacen_movimientos (almacen_id, talla_id, sexo, tipo, cantidad, referencia_tipo, referencia_id, observaciones, fecha_movimiento, created_at)
                 VALUES (:almacen_id, :talla_id, :sexo, :tipo, :cantidad, 'AJUSTE_INVENTARIO', :ref_id, :obs, :fecha_mov, NOW())"
            );

            $tipoMov = ($tipoAjuste === 'MERMA') ? 'MERMA' : (($tipoAjuste === 'AJUSTE_POSITIVO') ? 'ENTRADA' : 'SALIDA');
            $actaMsg = $acta ? " (Acta: {$acta})" : '';

            foreach ($items as $item) {
                $stmtDetalle->execute([
                    'ajuste_id' => $ajusteId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'cantidad' => $item['cantidad'],
                ]);

                if (in_array($tipoAjuste, ['MERMA', 'AJUSTE_NEGATIVO'], true)) {
                    $stmtUpdateNegativo->execute([
                        'almacen_id' => $almacenId,
                        'talla_id' => $item['talla_id'],
                        'sexo' => $item['sexo'],
                        'cantidad' => $item['cantidad'],
                    ]);
                } else {
                    $stmtUpdatePositivo->execute([
                        'almacen_id' => $almacenId,
                        'talla_id' => $item['talla_id'],
                        'sexo' => $item['sexo'],
                        'cantidad' => $item['cantidad'],
                    ]);
                }

                $obsMov = "Ajuste Folio {$folio} [{$tipoAjuste}]: {$motivo}{$actaMsg}";
                $stmtMovimiento->execute([
                    'almacen_id' => $almacenId,
                    'talla_id' => $item['talla_id'],
                    'sexo' => $item['sexo'],
                    'tipo' => $tipoMov,
                    'cantidad' => $item['cantidad'],
                    'ref_id' => $ajusteId,
                    'obs' => $obsMov,
                    'fecha_mov' => $fecha . ' ' . date('H:i:s'),
                ]);
            }

            $this->pdo->commit();
            return $ajusteId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Obtiene el comprobante completo de una entrada por su ID.
     */
    public function obtenerEntradaPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT e.*, a.nombre AS almacen_nombre, a.clave AS almacen_clave, a.ubicacion AS almacen_ubicacion, a.responsable AS almacen_responsable,
                    COALESCE(SUM(d.cantidad), 0) AS total_piezas,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINA\' THEN d.cantidad ELSE 0 END), 0) AS total_nina,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINO\' THEN d.cantidad ELSE 0 END), 0) AS total_nino
             FROM almacen_entradas e
             JOIN almacenes a ON a.id = e.almacen_id
             LEFT JOIN almacen_entradas_detalle d ON d.entrada_id = e.id
             WHERE e.id = :id
             GROUP BY e.id'
        );
        $stmt->execute(['id' => $id]);
        $entrada = $stmt->fetch();
        if (!$entrada) {
            return null;
        }

        $stmtDetalle = $this->pdo->prepare(
            'SELECT d.*, t.talla, t.orden
             FROM almacen_entradas_detalle d
             JOIN tallas t ON t.id = d.talla_id
             WHERE d.entrada_id = :id
             ORDER BY t.orden, d.sexo'
        );
        $stmtDetalle->execute(['id' => $id]);
        $entrada['detalle'] = $stmtDetalle->fetchAll();

        return $entrada;
    }

    /**
     * Obtiene el comprobante completo de un traspaso por su ID.
     */
    public function obtenerTraspasoPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, 
                    ao.nombre AS origen_nombre, ao.clave AS origen_clave, ao.responsable AS origen_responsable,
                    ad.nombre AS destino_nombre, ad.clave AS destino_clave, ad.responsable AS destino_responsable,
                    COALESCE(SUM(d.cantidad), 0) AS total_piezas,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINA\' THEN d.cantidad ELSE 0 END), 0) AS total_nina,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINO\' THEN d.cantidad ELSE 0 END), 0) AS total_nino
             FROM almacen_traspasos t
             JOIN almacenes ao ON ao.id = t.almacen_origen_id
             JOIN almacenes ad ON ad.id = t.almacen_destino_id
             LEFT JOIN almacen_traspasos_detalle d ON d.traspaso_id = t.id
             WHERE t.id = :id
             GROUP BY t.id'
        );
        $stmt->execute(['id' => $id]);
        $traspaso = $stmt->fetch();
        if (!$traspaso) {
            return null;
        }

        $stmtDetalle = $this->pdo->prepare(
            'SELECT d.*, t.talla, t.orden
             FROM almacen_traspasos_detalle d
             JOIN tallas t ON t.id = d.talla_id
             WHERE d.traspaso_id = :id
             ORDER BY t.orden, d.sexo'
        );
        $stmtDetalle->execute(['id' => $id]);
        $traspaso['detalle'] = $stmtDetalle->fetchAll();

        return $traspaso;
    }

    /**
     * Obtiene el comprobante completo de un ajuste/merma por su ID.
     */
    public function obtenerAjustePorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, alm.nombre AS almacen_nombre, alm.clave AS almacen_clave, alm.responsable AS almacen_responsable,
                    COALESCE(SUM(d.cantidad), 0) AS total_piezas,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINA\' THEN d.cantidad ELSE 0 END), 0) AS total_nina,
                    COALESCE(SUM(CASE WHEN d.sexo = \'NINO\' THEN d.cantidad ELSE 0 END), 0) AS total_nino
             FROM almacen_ajustes a
             JOIN almacenes alm ON alm.id = a.almacen_id
             LEFT JOIN almacen_ajustes_detalle d ON d.ajuste_id = a.id
             WHERE a.id = :id
             GROUP BY a.id'
        );
        $stmt->execute(['id' => $id]);
        $ajuste = $stmt->fetch();
        if (!$ajuste) {
            return null;
        }

        $stmtDetalle = $this->pdo->prepare(
            'SELECT d.*, t.talla, t.orden
             FROM almacen_ajustes_detalle d
             JOIN tallas t ON t.id = d.talla_id
             WHERE d.ajuste_id = :id
             ORDER BY t.orden, d.sexo'
        );
        $stmtDetalle->execute(['id' => $id]);
        $ajuste['detalle'] = $stmtDetalle->fetchAll();

        return $ajuste;
    }

    /**
     * Obtiene las alertas de stock mínimo para el dashboard y los almacenes.
     */
    public function obtenerAlertasStockMinimo(?int $almacenId = null): array
    {
        $whereAlm = $almacenId ? 'AND ae.almacen_id = :almacen_id' : '';
        $sql = "SELECT ae.id, ae.almacen_id, a.nombre AS almacen_nombre, a.clave AS almacen_clave,
                       t.id AS talla_id, t.talla, ae.sexo,
                       ae.cantidad_fisica, ae.cantidad_apartada,
                       (ae.cantidad_fisica - ae.cantidad_apartada) AS disponible,
                       ae.stock_minimo,
                       CASE 
                           WHEN (ae.cantidad_fisica - ae.cantidad_apartada) <= 0 THEN 'AGOTADO'
                           WHEN (ae.cantidad_fisica - ae.cantidad_apartada) <= 15 THEN 'CRITICO'
                           ELSE 'BAJO'
                       END AS nivel_alerta
                FROM almacen_existencias ae
                JOIN almacenes a ON a.id = ae.almacen_id
                JOIN tallas t ON t.id = ae.talla_id
                WHERE a.activo = 1 
                  AND (ae.cantidad_fisica - ae.cantidad_apartada) <= ae.stock_minimo
                  $whereAlm
                ORDER BY (ae.cantidad_fisica - ae.cantidad_apartada) ASC, a.nombre ASC, t.orden ASC";

        $stmt = $this->pdo->prepare($sql);
        if ($almacenId) {
            $stmt->bindValue(':almacen_id', $almacenId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene tallas activas ordenadas.
     */
    public function obtenerTallas(): array
    {
        return $this->pdo->query('SELECT id, talla, orden FROM tallas WHERE activo = 1 ORDER BY orden')->fetchAll();
    }

    /**
     * Normaliza y valida una estructura de cantidades [sexo => [talla_id => cantidad]].
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
