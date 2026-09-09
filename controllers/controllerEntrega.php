<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelEntrega.php';
require_once __DIR__.'/../models/modelAlmacen.php';
require_once __DIR__.'/../models/modelServicioRegional.php';
require_once __DIR__.'/../models/modelSolicitud.php';

class controllerEntrega
{
    public function __construct(private PDO $pdo) {}
    public function index(): void { $entregas=(new modelEntrega($this->pdo))->obtenerEntregas(); renderizarVista('entregas/index',compact('entregas'),'Entregas regionales','entregas'); }
    public function nueva(): void
    {
        $error=null;
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST' && ($_POST['accion']??'')==='enviar'){
            try{
                $almacenId=filter_input(INPUT_POST,'almacen_id',FILTER_VALIDATE_INT);
                $servicioId=filter_input(INPUT_POST,'servicio_id',FILTER_VALIDATE_INT);
                if(!$almacenId || !$servicioId){
                    throw new DomainException('Seleccione un almacén y un Servicio Regional válidos.');
                }
                $entregaId=(new modelEntrega($this->pdo))->registrarEnvioServicioRegional($almacenId,$servicioId);
                header('Location: '.url('entrega-detalle',['id'=>$entregaId,'enviada'=>1]));
                exit;
            }catch(Throwable $exception){
                $error=$exception->getMessage();
            }
        }
        $almacenId=filter_input(INPUT_GET,'almacen_id',FILTER_VALIDATE_INT)?:0; $servicioId=filter_input(INPUT_GET,'servicio_id',FILTER_VALIDATE_INT)?:0; $almacenes=(new modelAlmacen($this->pdo))->obtenerAlmacenes(); $servicios=(new modelServicioRegional($this->pdo))->obtenerServiciosRegionales(); $comparativo=$almacenId&&$servicioId?(new modelEntrega($this->pdo))->obtenerComparativo($almacenId,$servicioId):[]; $solicitudes=$servicioId?(new modelSolicitud($this->pdo))->obtenerPendientesPorServicioRegional($servicioId):[]; renderizarVista('entregas/nueva',compact('almacenes','servicios','almacenId','servicioId','comparativo','solicitudes','error'),'Preparar entrega','entregas');
    }
    public function detalle(int $id): void { $modelo=new modelEntrega($this->pdo); $entrega=$modelo->obtenerEntregaPorId($id); if(!$entrega){mostrarNoEncontrado();return;} $detalle=$modelo->obtenerDetalle($id); renderizarVista('entregas/detalle',compact('entrega','detalle'),'Detalle de entrega','entregas'); }
}
