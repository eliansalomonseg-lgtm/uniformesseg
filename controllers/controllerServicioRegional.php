<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelServicioRegional.php';

class controllerServicioRegional
{
    public function __construct(private PDO $pdo) {}
    public function index(): void { $servicios=(new modelServicioRegional($this->pdo))->obtenerServiciosRegionales(); renderizarVista('servicios_regionales/index',compact('servicios'),'Servicios Regionales','servicios'); }
}
