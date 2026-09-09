<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelMovimientoAlmacen.php';

class controllerMovimientoAlmacen
{
    public function __construct(private PDO $pdo) {}
    public function index(): void { $movimientos=(new modelMovimientoAlmacen($this->pdo))->obtenerMovimientosAlmacen(); renderizarVista('movimientos/index',compact('movimientos'),'Movimientos de almacén','movimientos'); }
}
