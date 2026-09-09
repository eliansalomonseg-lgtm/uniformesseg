<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelAlmacen.php';

class controllerAlmacen
{
    public function __construct(private PDO $pdo) {}
    public function index(): void { $almacenes=(new modelAlmacen($this->pdo))->obtenerAlmacenes(); renderizarVista('almacenes/index',compact('almacenes'),'Almacenes','almacenes'); }
    public function detalle(int $id): void
    {
        $modelo=new modelAlmacen($this->pdo); $almacen=$modelo->obtenerAlmacenPorId($id); if(!$almacen){mostrarNoEncontrado();return;} $existencias=$modelo->obtenerExistenciasPorTalla($id); $movimientos=$modelo->obtenerMovimientosAlmacen($id); renderizarVista('almacenes/detalle',compact('almacen','existencias','movimientos'),'Detalle de almacén','almacenes');
    }
}
