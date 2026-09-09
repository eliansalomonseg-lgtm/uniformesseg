<?php

declare(strict_types=1);

class modelMovimientoAlmacen
{
    public function __construct(private PDO $pdo) {}

    public function obtenerMovimientosAlmacen(): array
    {
        return $this->pdo->query('SELECT m.*,a.nombre almacen,t.talla FROM almacen_movimientos m JOIN almacenes a ON a.id=m.almacen_id JOIN tallas t ON t.id=m.talla_id ORDER BY m.fecha_movimiento DESC LIMIT 500')->fetchAll();
    }
}
