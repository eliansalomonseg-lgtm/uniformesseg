<?php

declare(strict_types=1);

class modelServicioRegional
{
    public function __construct(private PDO $pdo) {}

    public function obtenerServiciosRegionales(): array
    {
        return $this->pdo->query("SELECT sr.id,sr.clave,sr.nombre,(SELECT COUNT(DISTINCT esr.escuela_id) FROM escuelas_servicios_regionales esr WHERE esr.servicio_regional_id=sr.id AND esr.vigente=1) escuelas,(SELECT COUNT(*) FROM solicitudes_uniformes s WHERE s.servicio_regional_id=sr.id AND s.estado<>'CANCELADA') solicitudes,(SELECT COALESCE(SUM(sd.cantidad),0) FROM solicitudes_uniformes s JOIN solicitudes_uniformes_detalle sd ON sd.solicitud_id=s.id WHERE s.servicio_regional_id=sr.id AND s.estado<>'CANCELADA') solicitado,(SELECT COUNT(*) FROM solicitudes_uniformes s WHERE s.servicio_regional_id=sr.id AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')) pendientes FROM servicios_regionales sr WHERE sr.activo=1 ORDER BY sr.nombre")->fetchAll();
    }

    public function obtenerServicioRegionalPorId(int $id): ?array
    {
        $stmt=$this->pdo->prepare('SELECT * FROM servicios_regionales WHERE id=:id'); $stmt->execute(['id'=>$id]);
        return $stmt->fetch() ?: null;
    }
}
