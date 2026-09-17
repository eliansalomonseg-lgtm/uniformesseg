<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelMovimientoAlmacen.php';
require_once __DIR__ . '/../models/modelAlmacen.php';

class controllerMovimientoAlmacen
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $almacenId = filter_input(INPUT_GET, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
        $tipo = trim((string)($_GET['tipo'] ?? ''));
        $fechaDesde = trim((string)($_GET['fecha_desde'] ?? ''));
        $fechaHasta = trim((string)($_GET['fecha_hasta'] ?? ''));

        $modeloMov = new modelMovimientoAlmacen($this->pdo);
        $movimientos = $modeloMov->obtenerMovimientosAlmacen($almacenId, $tipo, $fechaDesde, $fechaHasta);
        $tipos = $modeloMov->obtenerTiposMovimiento();

        $almacenes = (new modelAlmacen($this->pdo))->obtenerAlmacenes();

        renderizarVista(
            'movimientos/index',
            compact('movimientos', 'almacenes', 'tipos', 'almacenId', 'tipo', 'fechaDesde', 'fechaHasta'),
            'Movimientos de almacén',
            'movimientos'
        );
    }
}
