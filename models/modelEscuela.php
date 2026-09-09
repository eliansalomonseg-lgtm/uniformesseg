<?php

declare(strict_types=1);

class modelEscuela
{
    public function __construct(private PDO $pdo) {}

    public function obtenerEscuelas(string $busqueda='', int $servicioId=0): array
    {
        $sql="SELECT e.id,e.cct,e.nombre,e.nivel,e.municipio,e.localidad,e.cct_servicio_regional,sr.nombre servicio_regional,COUNT(DISTINCT s.id) solicitudes,MAX(s.fecha_solicitud) ultima_solicitud,CASE WHEN COUNT(s.id)=0 THEN 'SIN_SOLICITUD' WHEN SUM(s.estado='ATENDIDA_POR_ALMACEN')>0 THEN 'ATENDIDA_POR_ALMACEN' WHEN SUM(s.estado='INCLUIDA_EN_ENTREGA')>0 THEN 'INCLUIDA_EN_ENTREGA' ELSE 'SOLICITUD_PENDIENTE' END estado_proceso FROM escuelas e LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id=e.id AND esr.vigente=1 LEFT JOIN servicios_regionales sr ON sr.id=esr.servicio_regional_id LEFT JOIN solicitudes_uniformes s ON s.escuela_id=e.id AND s.estado<>'CANCELADA' WHERE (:servicio=0 OR sr.id=:servicio2) AND (:busqueda='' OR e.cct LIKE :termino OR e.nombre LIKE :termino2 OR e.municipio LIKE :termino3 OR e.localidad LIKE :termino4 OR sr.nombre LIKE :termino5) GROUP BY e.id,sr.nombre ORDER BY e.nombre LIMIT 500";
        $stmt=$this->pdo->prepare($sql); $termino='%'.$busqueda.'%';
        $stmt->execute(['servicio'=>$servicioId,'servicio2'=>$servicioId,'busqueda'=>$busqueda,'termino'=>$termino,'termino2'=>$termino,'termino3'=>$termino,'termino4'=>$termino,'termino5'=>$termino]);
        return $stmt->fetchAll();
    }
}
