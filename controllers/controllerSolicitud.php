<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelSolicitud.php';

class controllerSolicitud
{
    public function __construct(private PDO $pdo) {}
    public function index(): void { $servicioId=filter_input(INPUT_GET,'servicio_id',FILTER_VALIDATE_INT)?:0; $solicitudes=(new modelSolicitud($this->pdo))->obtenerSolicitudes($servicioId); renderizarVista('solicitudes/index',compact('solicitudes'),'Solicitudes','solicitudes'); }
    public function nueva(): void
    {
        $modelo=new modelSolicitud($this->pdo);
        $error=null;
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            try{
                $escuelaId=filter_input(INPUT_POST,'escuela_id',FILTER_VALIDATE_INT);
                $fechaSolicitud=(string)($_POST['fecha_solicitud']??'');
                $cicloPeriodo=trim((string)($_POST['ciclo_periodo']??''));
                $observaciones=trim((string)($_POST['observaciones']??''));
                $cantidades=[];
                foreach(['NINO','NINA'] as $sexo){
                    foreach((array)($_POST['cantidad'][$sexo]??[]) as $tallaId=>$cantidad){
                        $id=filter_var($tallaId,FILTER_VALIDATE_INT);
                        $valor=filter_var($cantidad,FILTER_VALIDATE_INT);
                        if($id && $valor!==false){
                            $cantidades[$sexo][$id]=$valor;
                        }
                    }
                }
                if(!$escuelaId){
                    throw new DomainException('Seleccione una escuela válida.');
                }
                $solicitudId=$modelo->registrarSolicitud($escuelaId,$fechaSolicitud,$cicloPeriodo,$observaciones,$cantidades);
                header('Location: '.url('solicitud-detalle',['id'=>$solicitudId,'guardada'=>1]));
                exit;
            }catch(Throwable $exception){
                $error=$exception->getMessage();
            }
        }
        $busqueda=trim((string)($_GET['busqueda']??''));
        $escuelaId=filter_input(INPUT_GET,'escuela_id',FILTER_VALIDATE_INT)?:0;
        $resultados=$modelo->buscarEscuelasParaSolicitud($busqueda);
        $escuela=$escuelaId?$modelo->obtenerEscuelaParaSolicitud($escuelaId):null;
        $tallas=$modelo->obtenerTallasActivas();
        $disponibilidadGeneral=$modelo->obtenerDisponibilidadGeneral();
        renderizarVista('solicitudes/nueva',compact('busqueda','resultados','escuela','tallas','disponibilidadGeneral','error'),'Nueva solicitud','solicitudes');
    }
    public function buscarEscuelasJson(): void
    {
        $busqueda=trim((string)($_GET['q']??''));
        header('Content-Type: application/json; charset=utf-8');
        if(mb_strlen($busqueda)<2){
            echo json_encode([],JSON_UNESCAPED_UNICODE);
            exit;
        }
        $escuelas=(new modelSolicitud($this->pdo))->buscarEscuelasParaSolicitud($busqueda);
        echo json_encode(array_map(fn($escuela)=>[
            'id'=>(int)$escuela['id'],
            'cct'=>$escuela['cct'],
            'nombre'=>$escuela['nombre'],
            'nivel'=>$escuela['nivel'],
            'municipio'=>$escuela['municipio'],
            'localidad'=>$escuela['localidad'],
            'servicio_regional'=>$escuela['servicio_regional'],
            'cct_servicio_regional'=>$escuela['cct_servicio_regional'],
        ],$escuelas),JSON_UNESCAPED_UNICODE);
        exit;
    }
    public function editar(int $id): void
    {
        $modelo=new modelSolicitud($this->pdo);
        $solicitud=$modelo->obtenerSolicitudPorId($id);
        if(!$solicitud || !$modelo->solicitudEsEditable($solicitud['estado'])){
            mostrarNoEncontrado();
            return;
        }
        $error=null;
        if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
            try{
                $cantidades=[];
                foreach(['NINO','NINA'] as $sexo){
                    foreach((array)($_POST['cantidad'][$sexo]??[]) as $tallaId=>$cantidad){
                        $idTalla=filter_var($tallaId,FILTER_VALIDATE_INT);
                        $valor=filter_var($cantidad,FILTER_VALIDATE_INT);
                        if($idTalla && $valor!==false){
                            $cantidades[$sexo][$idTalla]=$valor;
                        }
                    }
                }
                $modelo->actualizarSolicitud($id,(string)($_POST['fecha_solicitud']??''),trim((string)($_POST['ciclo_periodo']??'')),trim((string)($_POST['observaciones']??'')),$cantidades);
                header('Location: '.url('solicitud-detalle',['id'=>$id,'actualizada'=>1]));
                exit;
            }catch(Throwable $exception){
                $error=$exception->getMessage();
            }
        }
        $tallas=$modelo->obtenerCantidadesEditables($id);
        $disponibilidadGeneral=$modelo->obtenerDisponibilidadGeneral();
        renderizarVista('solicitudes/editar',compact('solicitud','tallas','disponibilidadGeneral','error'),'Editar solicitud','solicitudes');
    }
    public function detalle(int $id): void { $modelo=new modelSolicitud($this->pdo); $solicitud=$modelo->obtenerSolicitudPorId($id); if(!$solicitud){mostrarNoEncontrado();return;} $detalle=$modelo->obtenerDetallePorTalla($id); renderizarVista('solicitudes/detalle',compact('solicitud','detalle'),'Detalle de solicitud','solicitudes'); }
}
