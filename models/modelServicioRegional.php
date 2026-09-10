<?php

declare(strict_types=1);

class modelServicioRegional
{
    public function __construct(private PDO $pdo) {}

    public function obtenerServiciosRegionales(): array
    {
        return $this->pdo->query("SELECT sr.id, sr.clave, sr.nombre, COALESCE(asr.almacen_id, sr.almacen_id) AS almacen_id, a.nombre almacen,
            (SELECT COUNT(DISTINCT esr.escuela_id) FROM escuelas_servicios_regionales esr WHERE esr.servicio_regional_id=sr.id AND esr.vigente=1) escuelas,
            (SELECT COUNT(DISTINCT se.solicitud_id) FROM solicitudes_uniformes_escuelas se JOIN solicitudes_uniformes s ON s.id=se.solicitud_id WHERE se.servicio_regional_id=sr.id AND s.estado<>'CANCELADA') solicitudes,
            (SELECT COALESCE(SUM(sd.cantidad),0) FROM solicitudes_uniformes_escuelas se JOIN solicitudes_uniformes s ON s.id=se.solicitud_id JOIN solicitudes_uniformes_detalle sd ON sd.solicitud_id=s.id AND sd.escuela_id=se.escuela_id WHERE se.servicio_regional_id=sr.id AND s.estado<>'CANCELADA') solicitado,
            (SELECT COUNT(DISTINCT se.solicitud_id) FROM solicitudes_uniformes_escuelas se JOIN solicitudes_uniformes s ON s.id=se.solicitud_id WHERE se.servicio_regional_id=sr.id AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')) pendientes
        FROM servicios_regionales sr
        LEFT JOIN almacenes_servicios_regionales asr ON asr.servicio_regional_id = sr.id AND asr.vigente = 1
        LEFT JOIN almacenes a ON a.id = COALESCE(asr.almacen_id, sr.almacen_id)
        WHERE sr.activo=1
        ORDER BY sr.nombre")->fetchAll();
    }

    public function obtenerServicioRegionalPorAlmacen(int $almacenId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT sr.*
            FROM servicios_regionales sr
            LEFT JOIN almacenes_servicios_regionales asr ON asr.servicio_regional_id = sr.id AND asr.vigente = 1
            WHERE (asr.almacen_id = :almacen_id OR sr.almacen_id = :almacen_id2)
              AND sr.activo = 1
            LIMIT 1");
        $stmt->execute(['almacen_id' => $almacenId, 'almacen_id2' => $almacenId]);
        return $stmt->fetch() ?: null;
    }
}
