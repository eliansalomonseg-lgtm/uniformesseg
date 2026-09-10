<?php

declare(strict_types=1);

class modelEscuela
{
    public function __construct(private PDO $pdo) {}

    public function obtenerEscuelas(string $busqueda='', int $servicioId=0): array
    {
        $sql="SELECT e.id,e.cct,e.nombre,e.nivel,COALESCE(m.nombre, e.municipio) AS municipio,e.localidad,e.cct_servicio_regional,sr.nombre servicio_regional,
            COUNT(DISTINCT s.id) solicitudes,
            MAX(s.fecha_solicitud) ultima_solicitud,
            CASE
                WHEN COUNT(s.id)=0 THEN 'SIN_SOLICITUD'
                WHEN SUM(s.estado='ATENDIDA_POR_ALMACEN')>0 THEN 'ATENDIDA_POR_ALMACEN'
                WHEN SUM(s.estado='INCLUIDA_EN_ENTREGA')>0 THEN 'INCLUIDA_EN_ENTREGA'
                ELSE 'SOLICITUD_PENDIENTE'
            END estado_proceso
        FROM escuelas e
        LEFT JOIN municipios m ON m.id = e.municipio_id
        LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id=e.id AND esr.vigente=1
        LEFT JOIN servicios_regionales sr ON sr.id=esr.servicio_regional_id
        LEFT JOIN solicitudes_uniformes_escuelas se ON se.escuela_id=e.id
        LEFT JOIN solicitudes_uniformes s ON s.id=se.solicitud_id AND s.estado<>'CANCELADA'
        WHERE (:servicio=0 OR sr.id=:servicio2)
          AND (:busqueda='' OR e.cct LIKE :termino OR e.nombre LIKE :termino2 OR m.nombre LIKE :termino3 OR e.municipio LIKE :termino4 OR e.localidad LIKE :termino5 OR sr.nombre LIKE :termino6)
        GROUP BY e.id,m.nombre,sr.nombre
        ORDER BY e.nombre
        LIMIT 500";
        $stmt=$this->pdo->prepare($sql);
        $termino='%'.$busqueda.'%';
        $stmt->execute([
            'servicio'=>$servicioId,
            'servicio2'=>$servicioId,
            'busqueda'=>$busqueda,
            'termino'=>$termino,
            'termino2'=>$termino,
            'termino3'=>$termino,
            'termino4'=>$termino,
            'termino5'=>$termino,
            'termino6'=>$termino
        ]);
        return $stmt->fetchAll();
    }
}
