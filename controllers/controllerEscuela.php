<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelEscuela.php';
require_once __DIR__.'/../models/modelServicioRegional.php';

class controllerEscuela
{
    public function __construct(private PDO $pdo) {}
    public function index(): void
    {
        $busqueda=trim((string)($_GET['busqueda']??'')); $servicioId=filter_input(INPUT_GET,'servicio_id',FILTER_VALIDATE_INT)?:0; $escuelas=(new modelEscuela($this->pdo))->obtenerEscuelas($busqueda,$servicioId); $servicios=(new modelServicioRegional($this->pdo))->obtenerServiciosRegionales(); renderizarVista('escuelas/index',compact('escuelas','servicios','busqueda','servicioId'),'Escuelas','escuelas');
    }
}
