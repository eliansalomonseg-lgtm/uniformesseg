<?php

declare(strict_types=1);

class modelSolicitud
{
    public function __construct(private PDO $pdo) {}

    public function buscarEscuelasParaSolicitud(string $busqueda): array
    {
        if ($busqueda === '') {
            return [];
        }

        $termino='%'.$busqueda.'%';
        $stmt=$this->pdo->prepare("SELECT e.id,e.cct,e.nombre,e.nivel,e.municipio,e.localidad,sr.nombre servicio_regional,e.cct_servicio_regional FROM escuelas e LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id=e.id AND esr.vigente=1 LEFT JOIN servicios_regionales sr ON sr.id=esr.servicio_regional_id WHERE e.cct LIKE :termino OR e.nombre LIKE :termino2 ORDER BY CASE WHEN e.cct=:exacta THEN 0 ELSE 1 END,e.nombre LIMIT 20");
        $stmt->execute(['termino'=>$termino,'termino2'=>$termino,'exacta'=>$busqueda]);
        return $stmt->fetchAll();
    }

    public function obtenerEscuelaParaSolicitud(int $escuelaId): ?array
    {
        $stmt=$this->pdo->prepare("SELECT e.id,e.cct,e.nombre,e.nivel,e.municipio,e.localidad,sr.id servicio_regional_id,sr.nombre servicio_regional,e.cct_servicio_regional FROM escuelas e LEFT JOIN escuelas_servicios_regionales esr ON esr.escuela_id=e.id AND esr.vigente=1 LEFT JOIN servicios_regionales sr ON sr.id=esr.servicio_regional_id WHERE e.id=:id");
        $stmt->execute(['id'=>$escuelaId]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerTallasActivas(): array
    {
        return $this->pdo->query('SELECT id,talla FROM tallas WHERE activo=1 ORDER BY orden')->fetchAll();
    }

    public function obtenerDisponibilidadGeneral(): array
    {
        return $this->pdo->query("SELECT talla_id,sexo,COALESCE(SUM(cantidad_fisica-cantidad_apartada),0) disponible FROM almacen_existencias GROUP BY talla_id,sexo")->fetchAll();
    }

    public function registrarSolicitud(int $escuelaId, string $fechaSolicitud, ?string $cicloPeriodo, ?string $observaciones, array $cantidades): int
    {
        $escuela=$this->obtenerEscuelaParaSolicitud($escuelaId);
        if(!$escuela || !$escuela['servicio_regional_id']){
            throw new DomainException('La escuela seleccionada no tiene un Servicio Regional oficial vigente.');
        }

        $detalles=[];
        foreach($this->obtenerTallasActivas() as $talla){
            foreach(['NINO','NINA'] as $sexo){
                $cantidad=$cantidades[$sexo][$talla['id']]??0;
                if(!is_int($cantidad) || $cantidad<0){
                    throw new DomainException('Las cantidades deben ser números enteros iguales o mayores que cero.');
                }
                if($cantidad>0){
                    $detalles[]=['talla_id'=>(int)$talla['id'],'sexo'=>$sexo,'cantidad'=>$cantidad];
                }
            }
        }
        if(!$detalles){
            throw new DomainException('Capture al menos una cantidad mayor que cero.');
        }

        $fecha=DateTime::createFromFormat('Y-m-d',$fechaSolicitud);
        if(!$fecha || $fecha->format('Y-m-d')!==$fechaSolicitud){
            throw new DomainException('La fecha de solicitud no es válida.');
        }

        $this->pdo->beginTransaction();
        try{
            $ultimoId=$this->pdo->query('SELECT id FROM solicitudes_uniformes ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $siguiente=(int)$ultimoId+1;
            $folio=sprintf('SOL-%s-%05d',$fecha->format('Y'),$siguiente);
            $stmt=$this->pdo->prepare("INSERT INTO solicitudes_uniformes (folio,escuela_id,servicio_regional_id,fecha_solicitud,ciclo_periodo,estado,observaciones) VALUES (:folio,:escuela_id,:servicio_regional_id,:fecha_solicitud,:ciclo_periodo,'PENDIENTE',:observaciones)");
            $stmt->execute(['folio'=>$folio,'escuela_id'=>$escuelaId,'servicio_regional_id'=>$escuela['servicio_regional_id'],'fecha_solicitud'=>$fechaSolicitud,'ciclo_periodo'=>$cicloPeriodo?:null,'observaciones'=>$observaciones?:null]);
            $solicitudId=(int)$this->pdo->lastInsertId();
            $detalle=$this->pdo->prepare('INSERT INTO solicitudes_uniformes_detalle (solicitud_id,talla_id,sexo,cantidad) VALUES (:solicitud_id,:talla_id,:sexo,:cantidad)');
            foreach($detalles as $registro){
                $detalle->execute(['solicitud_id'=>$solicitudId,'talla_id'=>$registro['talla_id'],'sexo'=>$registro['sexo'],'cantidad'=>$registro['cantidad']]);
            }
            $this->pdo->commit();
            return $solicitudId;
        }catch(Throwable $error){
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function obtenerSolicitudes(int $servicioId=0): array
    {
        $stmt=$this->pdo->prepare("SELECT s.id,s.folio,s.fecha_solicitud,s.estado,s.servicio_regional_id,e.cct,e.nombre escuela,e.municipio,e.localidad,sr.nombre servicio_regional,COALESCE(SUM(d.cantidad),0) total FROM solicitudes_uniformes s JOIN escuelas e ON e.id=s.escuela_id JOIN servicios_regionales sr ON sr.id=s.servicio_regional_id LEFT JOIN solicitudes_uniformes_detalle d ON d.solicitud_id=s.id WHERE (:servicio=0 OR s.servicio_regional_id=:servicio2) GROUP BY s.id ORDER BY s.fecha_solicitud DESC,s.id DESC");
        $stmt->execute(['servicio'=>$servicioId,'servicio2'=>$servicioId]); return $stmt->fetchAll();
    }

    public function obtenerSolicitudPorId(int $id): ?array
    {
        $stmt=$this->pdo->prepare('SELECT s.*,e.cct,e.nombre escuela,e.municipio,e.localidad,sr.nombre servicio_regional FROM solicitudes_uniformes s JOIN escuelas e ON e.id=s.escuela_id JOIN servicios_regionales sr ON sr.id=s.servicio_regional_id WHERE s.id=:id'); $stmt->execute(['id'=>$id]);
        return $stmt->fetch() ?: null;
    }

    public function obtenerDetallePorTalla(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT t.talla,COALESCE(SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad ELSE 0 END),0) nina,COALESCE(SUM(d.cantidad),0) total FROM tallas t LEFT JOIN solicitudes_uniformes_detalle d ON d.talla_id=t.id AND d.solicitud_id=:id WHERE t.activo=1 GROUP BY t.id,t.talla,t.orden HAVING total>0 ORDER BY t.orden"); $stmt->execute(['id'=>$id]); return $stmt->fetchAll();
    }

    public function obtenerCantidadesEditables(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT t.id,t.talla,COALESCE(SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad ELSE 0 END),0) nino,COALESCE(SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad ELSE 0 END),0) nina FROM tallas t LEFT JOIN solicitudes_uniformes_detalle d ON d.talla_id=t.id AND d.solicitud_id=:id WHERE t.activo=1 GROUP BY t.id,t.talla,t.orden ORDER BY t.orden");
        $stmt->execute(['id'=>$id]);
        return $stmt->fetchAll();
    }

    public function solicitudEsEditable(string $estado): bool
    {
        return in_array($estado,['PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION'],true);
    }

    public function actualizarSolicitud(int $solicitudId, string $fechaSolicitud, ?string $cicloPeriodo, ?string $observaciones, array $cantidades): void
    {
        $fecha=DateTime::createFromFormat('Y-m-d',$fechaSolicitud);
        if(!$fecha || $fecha->format('Y-m-d')!==$fechaSolicitud){
            throw new DomainException('La fecha de solicitud no es válida.');
        }
        $detalles=[];
        foreach($this->obtenerTallasActivas() as $talla){
            foreach(['NINO','NINA'] as $sexo){
                $cantidad=$cantidades[$sexo][$talla['id']]??0;
                if(!is_int($cantidad) || $cantidad<0){
                    throw new DomainException('Las cantidades deben ser números enteros iguales o mayores que cero.');
                }
                if($cantidad>0){
                    $detalles[]=['talla_id'=>(int)$talla['id'],'sexo'=>$sexo,'cantidad'=>$cantidad];
                }
            }
        }
        if(!$detalles){
            throw new DomainException('Capture al menos una cantidad mayor que cero.');
        }
        $this->pdo->beginTransaction();
        try{
            $bloqueo=$this->pdo->prepare('SELECT estado FROM solicitudes_uniformes WHERE id=:id FOR UPDATE');
            $bloqueo->execute(['id'=>$solicitudId]);
            $estado=$bloqueo->fetchColumn();
            if(!$estado){
                throw new DomainException('La solicitud no existe.');
            }
            if(!$this->solicitudEsEditable($estado)){
                throw new DomainException('La solicitud ya forma parte de un envío y no puede modificarse.');
            }
            $actualizar=$this->pdo->prepare('UPDATE solicitudes_uniformes SET fecha_solicitud=:fecha_solicitud,ciclo_periodo=:ciclo_periodo,observaciones=:observaciones,updated_at=NOW() WHERE id=:id');
            $actualizar->execute(['fecha_solicitud'=>$fechaSolicitud,'ciclo_periodo'=>$cicloPeriodo?:null,'observaciones'=>$observaciones?:null,'id'=>$solicitudId]);
            $this->pdo->prepare('DELETE FROM solicitudes_uniformes_detalle WHERE solicitud_id=:id')->execute(['id'=>$solicitudId]);
            $insertar=$this->pdo->prepare('INSERT INTO solicitudes_uniformes_detalle (solicitud_id,talla_id,sexo,cantidad) VALUES (:solicitud_id,:talla_id,:sexo,:cantidad)');
            foreach($detalles as $detalle){
                $insertar->execute(['solicitud_id'=>$solicitudId,'talla_id'=>$detalle['talla_id'],'sexo'=>$detalle['sexo'],'cantidad'=>$detalle['cantidad']]);
            }
            $this->pdo->commit();
        }catch(Throwable $error){
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function obtenerPendientesPorServicioRegional(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT s.id,s.folio,e.cct,e.nombre escuela,COALESCE(SUM(d.cantidad),0) total FROM solicitudes_uniformes s JOIN escuelas e ON e.id=s.escuela_id LEFT JOIN solicitudes_uniformes_detalle d ON d.solicitud_id=s.id WHERE s.servicio_regional_id=:id AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION') GROUP BY s.id ORDER BY s.fecha_solicitud"); $stmt->execute(['id'=>$id]); return $stmt->fetchAll();
    }
}
