<?php

declare(strict_types=1);

class modelAlmacen
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerAlmacenes(): array
    {
        $sql = "SELECT a.id, a.clave, a.nombre, a.ubicacion, a.responsable,
            (SELECT asr.servicio_regional_id 
             FROM almacenes_servicios_regionales asr 
             WHERE asr.almacen_id = a.id AND asr.vigente = 1 
             LIMIT 1) AS servicio_regional_id,
            (SELECT GROUP_CONCAT(sr.nombre ORDER BY sr.nombre SEPARATOR ', ') 
             FROM almacenes_servicios_regionales asr 
             JOIN servicios_regionales sr ON sr.id = asr.servicio_regional_id 
             WHERE asr.almacen_id = a.id AND asr.vigente = 1) AS servicio_regional,
            COALESCE(stock.total, 0) AS total,
            COALESCE(stock.nino, 0) AS nino,
            COALESCE(stock.nina, 0) AS nina,
            COALESCE(stock.apartado, 0) AS apartado,
            COALESCE(stock.disponible, 0) AS disponible
        FROM almacenes a
        LEFT JOIN (
            SELECT ae.almacen_id,
                SUM(ae.cantidad_fisica) AS total,
                SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END) AS nino,
                SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END) AS nina,
                SUM(ae.cantidad_apartada) AS apartado,
                SUM(ae.cantidad_fisica - ae.cantidad_apartada) AS disponible
            FROM almacen_existencias ae
            GROUP BY ae.almacen_id
        ) stock ON stock.almacen_id = a.id
        WHERE a.activo = 1
        ORDER BY a.nombre";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerAlmacenPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT a.*, 
            (SELECT asr.servicio_regional_id 
             FROM almacenes_servicios_regionales asr 
             WHERE asr.almacen_id = a.id AND asr.vigente = 1 
             LIMIT 1) AS servicio_regional_id,
            (SELECT GROUP_CONCAT(sr.nombre ORDER BY sr.nombre SEPARATOR ', ') 
             FROM almacenes_servicios_regionales asr 
             JOIN servicios_regionales sr ON sr.id = asr.servicio_regional_id 
             WHERE asr.almacen_id = a.id AND asr.vigente = 1) AS servicio_regional
        FROM almacenes a 
        WHERE a.id = :id AND a.activo = 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerAlmacenPorServicioRegional(int $servicioRegionalId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT a.*, sr.nombre servicio_regional
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
        $stmtCentral = $this->pdo->query("SELECT * FROM almacenes WHERE clave = 'AC' AND activo = 1 LIMIT 1");
        return $stmtCentral->fetch() ?: null;
    }

    public function obtenerExistenciasPorTalla(int $id): array
    {
        $stmt = $this->pdo->prepare("SELECT t.id,t.talla,COALESCE(SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END),0) nina,COALESCE(SUM(ae.cantidad_fisica),0) total,COALESCE(SUM(ae.cantidad_apartada),0) apartado,COALESCE(SUM(ae.cantidad_fisica-ae.cantidad_apartada),0) disponible FROM tallas t LEFT JOIN almacen_existencias ae ON ae.talla_id=t.id AND ae.almacen_id=:id WHERE t.activo=1 GROUP BY t.id,t.talla,t.orden ORDER BY t.orden");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public function obtenerMovimientosAlmacen(int $id, int $limite = 50): array
    {
        $stmt = $this->pdo->prepare('SELECT am.*,t.talla FROM almacen_movimientos am JOIN tallas t ON t.id=am.talla_id WHERE am.almacen_id=:id ORDER BY am.fecha_movimiento DESC LIMIT :limite');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
