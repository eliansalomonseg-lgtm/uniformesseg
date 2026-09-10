<?php

declare(strict_types=1);

class modelDashboard
{
    public function __construct(private PDO $pdo)
    {
    }

    public function obtenerResumen(): array
    {
        $inventario = $this->pdo->query("SELECT COALESCE(SUM(cantidad_fisica),0) total, COALESCE(SUM(CASE WHEN sexo='NINO' THEN cantidad_fisica ELSE 0 END),0) nino, COALESCE(SUM(CASE WHEN sexo='NINA' THEN cantidad_fisica ELSE 0 END),0) nina, COALESCE(SUM(cantidad_apartada),0) apartado, COALESCE(SUM(cantidad_fisica-cantidad_apartada),0) disponible FROM almacen_existencias")->fetch();
        $solicitudes = $this->pdo->query("SELECT
            COUNT(*) total,
            (SELECT COALESCE(SUM(cantidad),0) FROM solicitudes_uniformes_detalle) piezas,
            COUNT(CASE WHEN s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION') THEN 1 END) pendientes,
            (SELECT COUNT(DISTINCT se.servicio_regional_id) FROM solicitudes_uniformes_escuelas se JOIN solicitudes_uniformes su ON su.id=se.solicitud_id WHERE su.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')) regionales_pendientes
        FROM solicitudes_uniformes s")->fetch();
        $entregas = $this->pdo->query("SELECT COALESCE(SUM(CASE WHEN e.estado IN ('ENTREGADO','RECIBIDO') THEN d.cantidad_entregada ELSE 0 END),0) entregados, COUNT(DISTINCT CASE WHEN e.estado IN ('ENTREGADO','RECIBIDO') THEN e.id END) realizadas, COUNT(DISTINCT CASE WHEN e.estado IN ('PENDIENTE','PREPARANDO','LISTO') THEN e.id END) preparacion FROM entregas_servicios_regionales e LEFT JOIN entregas_servicios_regionales_detalle d ON d.entrega_id=e.id")->fetch();
        $activos = (int) $this->pdo->query("SELECT COUNT(*) FROM almacenes WHERE activo=1")->fetchColumn();
        return compact('inventario', 'solicitudes', 'entregas', 'activos');
    }

    public function obtenerExistenciasPorAlmacen(): array
    {
        return $this->pdo->query("SELECT a.id,a.nombre,COALESCE(SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END),0) nina,COALESCE(SUM(ae.cantidad_fisica),0) total,COALESCE(SUM(ae.cantidad_apartada),0) apartado,COALESCE(SUM(ae.cantidad_fisica-ae.cantidad_apartada),0) disponible FROM almacenes a LEFT JOIN almacen_existencias ae ON ae.almacen_id=a.id WHERE a.activo=1 GROUP BY a.id,a.nombre ORDER BY a.nombre")->fetchAll();
    }

    public function obtenerSolicitudesPorServicioRegional(): array
    {
        return $this->pdo->query("SELECT sr.id,sr.nombre,
            COUNT(DISTINCT se.escuela_id) escuelas,
            COALESCE(SUM(CASE WHEN sd.sexo='NINO' THEN sd.cantidad ELSE 0 END),0) nino,
            COALESCE(SUM(CASE WHEN sd.sexo='NINA' THEN sd.cantidad ELSE 0 END),0) nina,
            COALESCE(SUM(sd.cantidad),0) total,
            COALESCE(SUM(CASE WHEN s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION') THEN sd.cantidad ELSE 0 END),0) pendiente
        FROM servicios_regionales sr
        LEFT JOIN solicitudes_uniformes_escuelas se ON se.servicio_regional_id=sr.id
        LEFT JOIN solicitudes_uniformes s ON s.id=se.solicitud_id AND s.estado<>'CANCELADA'
        LEFT JOIN solicitudes_uniformes_detalle sd ON sd.solicitud_id=s.id AND sd.escuela_id=se.escuela_id
        WHERE sr.activo=1
        GROUP BY sr.id,sr.nombre
        ORDER BY sr.nombre")->fetchAll();
    }

    public function obtenerExistenciasPorTalla(): array
    {
        return $this->pdo->query("SELECT t.talla,COALESCE(SUM(CASE WHEN ae.sexo='NINO' THEN ae.cantidad_fisica ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN ae.sexo='NINA' THEN ae.cantidad_fisica ELSE 0 END),0) nina,COALESCE(SUM(ae.cantidad_fisica),0) total FROM tallas t LEFT JOIN almacen_existencias ae ON ae.talla_id=t.id WHERE t.activo=1 GROUP BY t.id,t.talla,t.orden ORDER BY t.orden")->fetchAll();
    }
}
