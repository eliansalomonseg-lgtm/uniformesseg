<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelEntregaSimple.php';

class controllerDashboard
{
    public function __construct(private ?PDO $pdo = null) {}

    public function index(): void
    {
        $metricas = modelEntregaSimple::obtenerMetricasDashboard();

        renderizarVista(
            'dashboard/index',
            compact('metricas'),
            'Panel de Control',
            'inicio'
        );
    }
}
