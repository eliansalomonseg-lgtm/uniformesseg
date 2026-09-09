<?php

declare(strict_types=1);

class modelEntrega
{
    public function __construct(private PDO $pdo) {}

    public function obtenerEntregas(): array
    {
        return $this->pdo->query("SELECT e.id,e.folio,e.fecha_programada,e.fecha_salida,e.estado,a.nombre almacen,sr.nombre servicio_regional,COALESCE(SUM(d.cantidad_entregada),0) total FROM entregas_servicios_regionales e JOIN almacenes a ON a.id=e.almacen_id JOIN servicios_regionales sr ON sr.id=e.servicio_regional_id LEFT JOIN entregas_servicios_regionales_detalle d ON d.entrega_id=e.id GROUP BY e.id ORDER BY e.id DESC")->fetchAll();
    }

    public function obtenerEntregaPorId(int $id): ?array
    {
        $stmt=$this->pdo->prepare('SELECT e.*,a.nombre almacen,sr.nombre servicio_regional FROM entregas_servicios_regionales e JOIN almacenes a ON a.id=e.almacen_id JOIN servicios_regionales sr ON sr.id=e.servicio_regional_id WHERE e.id=:id'); $stmt->execute(['id'=>$id]); return $stmt->fetch() ?: null;
    }

    public function obtenerDetalle(int $id): array
    {
        $stmt=$this->pdo->prepare("SELECT t.talla,SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad_solicitada ELSE 0 END) solicitado_nino,SUM(CASE WHEN d.sexo='NINO' THEN d.cantidad_entregada ELSE 0 END) entregado_nino,SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad_solicitada ELSE 0 END) solicitado_nina,SUM(CASE WHEN d.sexo='NINA' THEN d.cantidad_entregada ELSE 0 END) entregado_nina FROM entregas_servicios_regionales_detalle d JOIN tallas t ON t.id=d.talla_id WHERE d.entrega_id=:id GROUP BY t.id,t.talla,t.orden ORDER BY t.orden"); $stmt->execute(['id'=>$id]); return $stmt->fetchAll();
    }

    public function obtenerComparativo(int $almacenId,int $servicioId): array
    {
        $stmt=$this->pdo->prepare("SELECT t.id,t.talla,COALESCE((SELECT SUM(sd.cantidad) FROM solicitudes_uniformes_detalle sd JOIN solicitudes_uniformes s ON s.id=sd.solicitud_id WHERE sd.talla_id=t.id AND sd.sexo='NINO' AND s.servicio_regional_id=:servicio AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')),0) solicitado_nino,COALESCE((SELECT ae.cantidad_fisica-ae.cantidad_apartada FROM almacen_existencias ae WHERE ae.talla_id=t.id AND ae.sexo='NINO' AND ae.almacen_id=:almacen LIMIT 1),0) disponible_nino,COALESCE((SELECT SUM(sd.cantidad) FROM solicitudes_uniformes_detalle sd JOIN solicitudes_uniformes s ON s.id=sd.solicitud_id WHERE sd.talla_id=t.id AND sd.sexo='NINA' AND s.servicio_regional_id=:servicio2 AND s.estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION')),0) solicitado_nina,COALESCE((SELECT ae.cantidad_fisica-ae.cantidad_apartada FROM almacen_existencias ae WHERE ae.talla_id=t.id AND ae.sexo='NINA' AND ae.almacen_id=:almacen2 LIMIT 1),0) disponible_nina FROM tallas t WHERE t.activo=1 ORDER BY t.orden"); $stmt->execute(['servicio'=>$servicioId,'almacen'=>$almacenId,'servicio2'=>$servicioId,'almacen2'=>$almacenId]); return $stmt->fetchAll();
    }

    public function registrarEnvioServicioRegional(int $almacenId, int $servicioId): int
    {
        $this->pdo->beginTransaction();
        try{
            $solicitudes=$this->pdo->prepare("SELECT id FROM solicitudes_uniformes WHERE servicio_regional_id=:servicio_id AND estado IN ('PENDIENTE','EN_REVISION','ASIGNADA','EN_PREPARACION') ORDER BY id FOR UPDATE");
            $solicitudes->execute(['servicio_id'=>$servicioId]);
            $idsSolicitudes=array_map('intval',array_column($solicitudes->fetchAll(),'id'));
            if(!$idsSolicitudes){
                throw new DomainException('No hay solicitudes pendientes para enviar a este Servicio Regional.');
            }

            $marcadores=implode(',',array_fill(0,count($idsSolicitudes),'?'));
            $detalleSolicitud=$this->pdo->prepare("SELECT d.talla_id,d.sexo,SUM(d.cantidad) cantidad FROM solicitudes_uniformes_detalle d WHERE d.solicitud_id IN ($marcadores) GROUP BY d.talla_id,d.sexo");
            $detalleSolicitud->execute($idsSolicitudes);
            $detalles=$detalleSolicitud->fetchAll();
            if(!$detalles){
                throw new DomainException('Las solicitudes pendientes no contienen cantidades para enviar.');
            }

            $existencia=$this->pdo->prepare('SELECT id,cantidad_fisica,cantidad_apartada FROM almacen_existencias WHERE almacen_id=:almacen_id AND talla_id=:talla_id AND sexo=:sexo FOR UPDATE');
            foreach($detalles as $detalle){
                $existencia->execute(['almacen_id'=>$almacenId,'talla_id'=>$detalle['talla_id'],'sexo'=>$detalle['sexo']]);
                $registro=$existencia->fetch();
                $disponible=$registro?(int)$registro['cantidad_fisica']-(int)$registro['cantidad_apartada']:0;
                if($disponible<(int)$detalle['cantidad']){
                    throw new DomainException('La existencia cambió y ya no alcanza para completar el envío. Revise nuevamente las existencias.');
                }
            }

            $ultimoId=$this->pdo->query('SELECT id FROM entregas_servicios_regionales ORDER BY id DESC LIMIT 1 FOR UPDATE')->fetchColumn();
            $folio=sprintf('UNIF-%s-%05d',date('Y'),(int)$ultimoId+1);
            $entrega=$this->pdo->prepare("INSERT INTO entregas_servicios_regionales (folio,almacen_id,servicio_regional_id,fecha_salida,estado,created_at,updated_at) VALUES (:folio,:almacen_id,:servicio_regional_id,NOW(),'EN_TRASLADO',NOW(),NOW())");
            $entrega->execute(['folio'=>$folio,'almacen_id'=>$almacenId,'servicio_regional_id'=>$servicioId]);
            $entregaId=(int)$this->pdo->lastInsertId();

            $insertarDetalle=$this->pdo->prepare('INSERT INTO entregas_servicios_regionales_detalle (entrega_id,talla_id,sexo,cantidad_solicitada,cantidad_preparada,cantidad_entregada,cantidad_recibida) VALUES (:entrega_id,:talla_id,:sexo,:cantidad,:cantidad,:cantidad,0)');
            $descontar=$this->pdo->prepare('UPDATE almacen_existencias SET cantidad_fisica=cantidad_fisica-:cantidad,updated_at=NOW() WHERE almacen_id=:almacen_id AND talla_id=:talla_id AND sexo=:sexo');
            $movimiento=$this->pdo->prepare("INSERT INTO almacen_movimientos (almacen_id,talla_id,sexo,tipo,cantidad,referencia_tipo,referencia_id,observaciones,fecha_movimiento,created_at) VALUES (:almacen_id,:talla_id,:sexo,'SALIDA',:cantidad,'ENTREGA_SERVICIO_REGIONAL',:entrega_id,'Envío interno al Servicio Regional',NOW(),NOW())");
            foreach($detalles as $detalle){
                $parametros=['entrega_id'=>$entregaId,'talla_id'=>$detalle['talla_id'],'sexo'=>$detalle['sexo'],'cantidad'=>$detalle['cantidad']];
                $insertarDetalle->execute($parametros);
                $descontar->execute(['almacen_id'=>$almacenId,'talla_id'=>$detalle['talla_id'],'sexo'=>$detalle['sexo'],'cantidad'=>$detalle['cantidad']]);
                $movimiento->execute(['almacen_id'=>$almacenId,'talla_id'=>$detalle['talla_id'],'sexo'=>$detalle['sexo'],'cantidad'=>$detalle['cantidad'],'entrega_id'=>$entregaId]);
            }

            $relacion=$this->pdo->prepare('INSERT INTO entregas_solicitudes (entrega_id,solicitud_id) VALUES (:entrega_id,:solicitud_id)');
            $actualizarSolicitud=$this->pdo->prepare("UPDATE solicitudes_uniformes SET estado='INCLUIDA_EN_ENTREGA',updated_at=NOW() WHERE id=:id");
            foreach($idsSolicitudes as $solicitudId){
                $relacion->execute(['entrega_id'=>$entregaId,'solicitud_id'=>$solicitudId]);
                $actualizarSolicitud->execute(['id'=>$solicitudId]);
            }
            $this->pdo->commit();
            return $entregaId;
        }catch(Throwable $error){
            if($this->pdo->inTransaction()){
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
