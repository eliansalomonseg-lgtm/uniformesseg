<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelDashboard.php';
require_once __DIR__ . '/../models/modelInventario.php';

class controllerDashboard
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $modelo = new modelDashboard($this->pdo);
        $resumen = $modelo->obtenerResumen();
        $almacenes = $modelo->obtenerExistenciasPorAlmacen();
        $regionales = $modelo->obtenerSolicitudesPorServicioRegional();
        $existenciasPorTalla = $modelo->obtenerExistenciasPorTalla();

        $modeloInventario = new modelInventario($this->pdo);
        $alertasStock = $modeloInventario->obtenerAlertasStockMinimo();

        renderizarVista(
            'dashboard/index',
            compact('resumen', 'almacenes', 'regionales', 'existenciasPorTalla', 'alertasStock'),
            'Inicio',
            'inicio'
        );
    }
}
