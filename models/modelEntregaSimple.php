<?php

declare(strict_types=1);

require_once __DIR__ . '/../services/serviceDatabase.php';

class modelEntregaSimple
{
    /**
     * Obtiene el catálogo de tallas activas ordenadas
     */
    public static function obtenerTallas(): array
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->query("SELECT id, talla, orden FROM tallas WHERE activo = 1 ORDER BY orden ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las escuelas activas para selector
     */
    public static function obtenerEscuelas(): array
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->query("SELECT id, cct, nombre, nivel, municipio, localidad FROM escuelas WHERE activo = 1 ORDER BY nombre ASC LIMIT 2000");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los servicios regionales activos para selector
     */
    public static function obtenerServiciosRegionales(): array
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->query("SELECT id, clave, nombre FROM servicios_regionales WHERE activo = 1 ORDER BY clave ASC, nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el listado de entregas con filtros opcionales
     */
    public static function obtenerEntregas(array $filtros = []): array
    {
        $pdo = serviceDatabase::obtenerConexion();

        $sql = "SELECT e.*, 
                       esc.cct AS escuela_cct, 
                       esc.nombre AS escuela_nombre, 
                       esc.municipio AS escuela_municipio,
                       sr.clave AS servicio_clave, 
                       sr.nombre AS servicio_nombre
                FROM entregas e
                LEFT JOIN escuelas esc ON e.escuela_id = esc.id
                LEFT JOIN servicios_regionales sr ON e.servicio_regional_id = sr.id
                WHERE 1 = 1";

        $params = [];

        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], ['ESCUELA', 'SERVICIO_REGIONAL'], true)) {
            $sql .= " AND e.tipo_destino = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['busqueda'])) {
            $term = '%' . trim($filtros['busqueda']) . '%';
            $sql .= " AND (e.folio LIKE ? OR e.recibido_por_nombre LIKE ? OR esc.nombre LIKE ? OR esc.cct LIKE ? OR sr.nombre LIKE ?)";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql .= " ORDER BY e.fecha_entrega DESC, e.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una entrega por su ID con datos completos del destino
     */
    public static function obtenerPorId(int $id): ?array
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->prepare("
            SELECT e.*, 
                   esc.cct AS escuela_cct, 
                   esc.nombre AS escuela_nombre, 
                   esc.nivel AS escuela_nivel,
                   esc.municipio AS escuela_municipio, 
                   esc.localidad AS escuela_localidad,
                   sr.clave AS servicio_clave, 
                   sr.nombre AS servicio_nombre
            FROM entregas e
            LEFT JOIN escuelas esc ON e.escuela_id = esc.id
            LEFT JOIN servicios_regionales sr ON e.servicio_regional_id = sr.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $entrega = $stmt->fetch(PDO::FETCH_ASSOC);

        return $entrega ?: null;
    }

    /**
     * Obtiene el desglose de prendas por talla y género para una entrega
     */
    public static function obtenerDetalleTallas(int $entregaId): array
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->prepare("
            SELECT ed.talla_id, ed.sexo, ed.cantidad, t.talla AS talla_nombre, t.orden
            FROM entregas_detalle ed
            JOIN tallas t ON ed.talla_id = t.id
            WHERE ed.entrega_id = ?
            ORDER BY t.orden ASC, t.id ASC, ed.sexo ASC
        ");
        $stmt->execute([$entregaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Registra una nueva entrega de uniformes
     * 
     * @param array $datos Cabecera: tipo_destino, escuela_id, servicio_regional_id, fecha_entrega, recibido_por_nombre, recibido_por_cargo, recibido_por_telefono, entregado_por_nombre, observaciones
     * @param array $cantidades Arreglo [talla_id => ['NINO' => int, 'NINA' => int]]
     */
    public static function registrarEntrega(array $datos, array $cantidades): int
    {
        $pdo = serviceDatabase::obtenerConexion();
        $pdo->beginTransaction();

        try {
            $tipoDestino = ($datos['tipo_destino'] === 'SERVICIO_REGIONAL') ? 'SERVICIO_REGIONAL' : 'ESCUELA';
            $prefijo = ($tipoDestino === 'ESCUELA') ? 'ENT-ESC' : 'ENT-REG';
            $anio = date('Y', strtotime($datos['fecha_entrega'] ?? 'now'));

            // Generar folio consecutivo
            $stmtConsecutivo = $pdo->query("SELECT MAX(id) FROM entregas");
            $siguienteNumero = ((int)$stmtConsecutivo->fetchColumn()) + 1;
            $folio = sprintf('%s-%s-%04d', $prefijo, $anio, $siguienteNumero);

            // Calcular total de piezas
            $totalPiezas = 0;
            $detallesValidos = [];

            foreach ($cantidades as $tallaId => $generos) {
                $tallaId = (int)$tallaId;
                foreach (['NINO', 'NINA'] as $sexo) {
                    $cant = isset($generos[$sexo]) ? (int)$generos[$sexo] : 0;
                    if ($cant > 0) {
                        $totalPiezas += $cant;
                        $detallesValidos[] = [
                            'talla_id' => $tallaId,
                            'sexo' => $sexo,
                            'cantidad' => $cant,
                        ];
                    }
                }
            }

            if ($totalPiezas <= 0) {
                throw new InvalidArgumentException('Debe ingresar al menos una prenda en la entrega.');
            }

            $stmtHeader = $pdo->prepare("
                INSERT INTO entregas (
                    folio, tipo_destino, escuela_id, servicio_regional_id, fecha_entrega,
                    recibido_por_nombre, recibido_por_cargo, recibido_por_telefono,
                    entregado_por_nombre, observaciones, total_piezas
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtHeader->execute([
                $folio,
                $tipoDestino,
                ($tipoDestino === 'ESCUELA') ? (!empty($datos['escuela_id']) ? (int)$datos['escuela_id'] : null) : null,
                ($tipoDestino === 'SERVICIO_REGIONAL') ? (!empty($datos['servicio_regional_id']) ? (int)$datos['servicio_regional_id'] : null) : null,
                $datos['fecha_entrega'] ?: date('Y-m-d'),
                trim($datos['recibido_por_nombre'] ?? 'Director / Responsable'),
                trim($datos['recibido_por_cargo'] ?? ''),
                trim($datos['recibido_por_telefono'] ?? ''),
                trim($datos['entregado_por_nombre'] ?? 'Personal SEG'),
                trim($datos['observaciones'] ?? ''),
                $totalPiezas,
            ]);

            $entregaId = (int)$pdo->lastInsertId();

            $stmtDet = $pdo->prepare("
                INSERT INTO entregas_detalle (entrega_id, talla_id, sexo, cantidad)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($detallesValidos as $item) {
                $stmtDet->execute([$entregaId, $item['talla_id'], $item['sexo'], $item['cantidad']]);
            }

            $pdo->commit();
            return $entregaId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina una entrega por su ID
     */
    public static function eliminarEntrega(int $id): bool
    {
        $pdo = serviceDatabase::obtenerConexion();
        $stmt = $pdo->prepare("DELETE FROM entregas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Resumen de métricas para el Dashboard
     */
    public static function obtenerMetricasDashboard(): array
    {
        $pdo = serviceDatabase::obtenerConexion();

        // Totales globales
        $resGlobal = $pdo->query("
            SELECT 
                COUNT(*) AS total_entregas, 
                COALESCE(SUM(total_piezas), 0) AS total_uniformes,
                COALESCE(SUM(CASE WHEN tipo_destino = 'ESCUELA' THEN total_piezas ELSE 0 END), 0) AS uniformes_escuelas,
                COALESCE(SUM(CASE WHEN tipo_destino = 'SERVICIO_REGIONAL' THEN total_piezas ELSE 0 END), 0) AS uniformes_regionales,
                COALESCE(SUM(CASE WHEN tipo_destino = 'ESCUELA' THEN 1 ELSE 0 END), 0) AS entregas_escuelas,
                COALESCE(SUM(CASE WHEN tipo_destino = 'SERVICIO_REGIONAL' THEN 1 ELSE 0 END), 0) AS entregas_regionales
            FROM entregas
        ")->fetch(PDO::FETCH_ASSOC);

        // Últimas entregas
        $stmtRecientes = $pdo->query("
            SELECT e.id, e.folio, e.tipo_destino, e.fecha_entrega, e.total_piezas, e.recibido_por_nombre,
                   esc.cct AS escuela_cct, esc.nombre AS escuela_nombre,
                   sr.nombre AS servicio_nombre
            FROM entregas e
            LEFT JOIN escuelas esc ON e.escuela_id = esc.id
            LEFT JOIN servicios_regionales sr ON e.servicio_regional_id = sr.id
            ORDER BY e.fecha_entrega DESC, e.id DESC
            LIMIT 6
        ");
        $ultimasEntregas = $stmtRecientes->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_entregas' => (int)($resGlobal['total_entregas'] ?? 0),
            'total_uniformes' => (int)($resGlobal['total_uniformes'] ?? 0),
            'uniformes_escuelas' => (int)($resGlobal['uniformes_escuelas'] ?? 0),
            'uniformes_regionales' => (int)($resGlobal['uniformes_regionales'] ?? 0),
            'entregas_escuelas' => (int)($resGlobal['entregas_escuelas'] ?? 0),
            'entregas_regionales' => (int)($resGlobal['entregas_regionales'] ?? 0),
            'ultimas_entregas' => $ultimasEntregas,
        ];
    }
}
