<?php

declare(strict_types=1);

require_once __DIR__.'/services/serviceDatabase.php';

function escapar(mixed $valor): string { return htmlspecialchars((string)$valor,ENT_QUOTES,'UTF-8'); }
function numero(mixed $valor): string { return number_format((int)$valor,0,'.',','); }
function url(string $ruta, array $parametros=[]): string { return 'index.php?'.http_build_query(array_merge(['ruta'=>$ruta],$parametros)); }
function renderizarVista(string $vista,array $datos,string $titulo,string $activo): void { extract($datos,EXTR_SKIP); require __DIR__.'/views/fragments/header.php'; require __DIR__.'/views/fragments/sidebar.php'; require __DIR__.'/views/'.$vista.'.php'; require __DIR__.'/views/fragments/footer.php'; }
function mostrarNoEncontrado(): void { http_response_code(404); renderizarVista('errores/404',[],'No encontrado',''); }

$rutas=[
    'inicio'=>['controllerDashboard','index'], 'almacenes'=>['controllerAlmacen','index'], 'almacen-detalle'=>['controllerAlmacen','detalle'],
    'servicios-regionales'=>['controllerServicioRegional','index'], 'escuelas'=>['controllerEscuela','index'], 'solicitudes'=>['controllerSolicitud','index'], 'solicitud-nueva'=>['controllerSolicitud','nueva'], 'solicitud-buscar-escuelas'=>['controllerSolicitud','buscarEscuelasJson'],
    'solicitud-detalle'=>['controllerSolicitud','detalle'], 'solicitud-editar'=>['controllerSolicitud','editar'], 'entregas'=>['controllerEntrega','index'], 'entrega-nueva'=>['controllerEntrega','nueva'],
    'entrega-detalle'=>['controllerEntrega','detalle'], 'movimientos'=>['controllerMovimientoAlmacen','index'],
];
$ruta=(string)($_GET['ruta']??'inicio');
if(!isset($rutas[$ruta])){mostrarNoEncontrado();exit;}
[$clase,$metodo]=$rutas[$ruta]; require_once __DIR__.'/controllers/'.$clase.'.php'; $controlador=new $clase(serviceDatabase::obtenerConexion());
if(in_array($ruta,['almacen-detalle','solicitud-detalle','solicitud-editar','entrega-detalle'],true)){ $id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT); if(!$id){mostrarNoEncontrado();exit;} $controlador->$metodo($id); } else { $controlador->$metodo(); }
