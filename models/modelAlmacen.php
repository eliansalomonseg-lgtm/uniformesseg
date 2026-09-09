<?php

declare(strict_types=1);

class modelAlmacen
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerAlmacenes(): array
    {
        return $this->pdo->query("SELECT a.id,a.clave,a.nombre,a.ubicacion,a.responsable,COALESCE(SUM(ae.cantidad_fisica),0) total,COALESCE(SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END),0) nina,COALESCE(SUM(ae.cantidad_apartada),0) apartado,COALESCE(SUM(ae.cantidad_fisica-ae.cantidad_apartada),0) disponible FROM almacenes a LEFT JOIN almacen_existencias ae ON ae.almacen_id=a.id WHERE a.activo=1 GROUP BY a.id ORDER BY a.nombre")->fetchAll();
    }

    public function obtenerAlmacenPorId(int $id): ?array
    {
        $stmt=$this->pdo->prepare('SELECT * FROM almacenes WHERE id=:id AND activo=1');
        $stmt->execute(['id'=>$id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerExistenciasPorTalla(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT t.id,t.talla,COALESCE(SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END),0) nina,COALESCE(SUM(ae.cantidad_fisica),0) total,COALESCE(SUM(ae.cantidad_apartada),0) apartado,COALESCE(SUM(ae.cantidad_fisica-ae.cantidad_apartada),0) disponible FROM tallas t LEFT JOIN almacen_existencias ae ON ae.talla_id=t.id AND ae.almacen_id=:id WHERE t.activo=1 GROUP BY t.id,t.talla,t.orden ORDER BY t.orden");
        $stmt->execute(['id'=>$id]);
        return $stmt->fetchAll();
    }

    public function obtenerMovimientosAlmacen(int $id, int $limite=50): array
    {
        $stmt=$this->pdo->prepare('SELECT am.*,t.talla FROM almacen_movimientos am JOIN tallas t ON t.id=am.talla_id WHERE am.almacen_id=:id ORDER BY am.fecha_movimiento DESC LIMIT :limite');
        $stmt->bindValue(':id',$id,PDO::PARAM_INT); $stmt->bindValue(':limite',$limite,PDO::PARAM_INT); $stmt->execute();
        return $stmt->fetchAll();
    }
}
