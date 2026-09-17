<?php

declare(strict_types=1);

class modelMovimientoAlmacen
{
    public function __construct(private PDO $pdo) {}

    /**
     * Obtiene el listado de movimientos de almacén (kardex) con filtros dinámicos.
     */
    public function obtenerMovimientosAlmacen(
        int $almacenId = 0,
        string $tipo = '',
        string $fechaDesde = '',
        string $fechaHasta = ''
    ): array {
        $condiciones = [];
        $parametros = [];

        if ($almacenId > 0) {
            $condiciones[] = 'm.almacen_id = :almacen_id';
            $parametros['almacen_id'] = $almacenId;
        }

        if ($tipo !== '' && $tipo !== '0') {
            $condiciones[] = 'm.tipo = :tipo';
            $parametros['tipo'] = $tipo;
        }

        if ($fechaDesde !== '') {
            $condiciones[] = 'DATE(m.fecha_movimiento) >= :fecha_desde';
            $parametros['fecha_desde'] = $fechaDesde;
        }

        if ($fechaHasta !== '') {
            $condiciones[] = 'DATE(m.fecha_movimiento) <= :fecha_hasta';
            $parametros['fecha_hasta'] = $fechaHasta;
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $sql = "SELECT m.*, a.nombre AS almacen, a.clave AS almacen_clave, t.talla
                FROM almacen_movimientos m
                JOIN almacenes a ON a.id = m.almacen_id
                JOIN tallas t ON t.id = m.talla_id
                $where
                ORDER BY m.fecha_movimiento DESC, m.id DESC
                LIMIT 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($parametros);
        return $stmt->fetchAll();
    }

    /**
     * Obtiene los tipos distintos de movimiento registrados para el filtro.
     */
    public function obtenerTiposMovimiento(): array
    {
        return $this->pdo->query('SELECT DISTINCT tipo FROM almacen_movimientos ORDER BY tipo')->fetchAll(PDO::FETCH_COLUMN);
    }
}
