<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelSolicitud.php';
require_once __DIR__.'/../models/modelServicioRegional.php';
require_once __DIR__.'/../models/modelAlmacen.php';
require_once __DIR__.'/../models/modelEntrega.php';

class controllerSolicitud
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $servicioId = filter_input(INPUT_GET, 'servicio_id', FILTER_VALIDATE_INT) ?: 0;
        $modelo = new modelSolicitud($this->pdo);
        $solicitudes = $modelo->obtenerSolicitudes($servicioId);
        $servicios = (new modelServicioRegional($this->pdo))->obtenerServiciosRegionales();
        $modelEntrega = new modelEntrega($this->pdo);

        foreach ($solicitudes as &$sol) {
            if (in_array($sol['estado'], ['PENDIENTE', 'EN_REVISION', 'ASIGNADA', 'EN_PREPARACION'], true)) {
                $sol['plan_despacho'] = $modelEntrega->obtenerPlanDespachoPorSolicitud((int)$sol['id']);
            } else {
                $sol['plan_despacho'] = [];
            }
        }
        unset($sol);

        renderizarVista(
            'solicitudes/index',
            compact('solicitudes', 'servicios', 'servicioId'),
            'Solicitudes',
            'solicitudes'
        );
    }

    public function nueva(): void
    {
        $modelo = new modelSolicitud($this->pdo);
        $error = null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $fechaSolicitud = (string) ($_POST['fecha_solicitud'] ?? '');
                $cicloPeriodo = trim((string) ($_POST['ciclo_periodo'] ?? ''));
                $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

                $escuelas = array_filter(array_map('intval', (array) ($_POST['escuelas'] ?? [])));
                $escuelaUnica = filter_input(INPUT_POST, 'escuela_id', FILTER_VALIDATE_INT);
                if ($escuelaUnica && !in_array($escuelaUnica, $escuelas, true)) {
                    $escuelas[] = $escuelaUnica;
                }

                if (!$escuelas) {
                    throw new DomainException('Debe agregar al menos una escuela a la solicitud.');
                }

                $cantidadesPorEscuela = [];
                $postCantidad = $_POST['cantidad'] ?? [];

                foreach ($escuelas as $escuelaId) {
                    $cantidadesPorEscuela[$escuelaId] = ['NINO' => [], 'NINA' => []];
                    foreach (['NINO', 'NINA'] as $sexo) {
                        $fuente = $postCantidad[$escuelaId][$sexo] ?? ($postCantidad[$sexo] ?? []);
                        foreach ((array) $fuente as $tallaId => $cantidad) {
                            $idT = filter_var($tallaId, FILTER_VALIDATE_INT);
                            $cant = filter_var($cantidad, FILTER_VALIDATE_INT);
                            if ($idT && $cant !== false) {
                                $cantidadesPorEscuela[$escuelaId][$sexo][$idT] = $cant;
                            }
                        }
                    }
                }

                $solicitudId = $modelo->registrarSolicitud(
                    $escuelas,
                    $fechaSolicitud,
                    $cicloPeriodo,
                    $observaciones,
                    $cantidadesPorEscuela
                );

                header('Location: ' . url('solicitud-detalle', ['id' => $solicitudId, 'guardada' => 1]));
                exit;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $escuelaInicialId = filter_input(INPUT_GET, 'escuela_id', FILTER_VALIDATE_INT) ?: 0;
        $escuelaInicial = $escuelaInicialId ? $modelo->obtenerEscuelaParaSolicitud($escuelaInicialId) : null;
        $tallas = $modelo->obtenerTallasActivas();
        $disponibilidadGeneral = $modelo->obtenerDisponibilidadGeneral();

        renderizarVista(
            'solicitudes/nueva',
            compact('escuelaInicial', 'tallas', 'disponibilidadGeneral', 'error'),
            'Nueva solicitud escolar',
            'solicitudes'
        );
    }

    public function buscarEscuelasJson(): void
    {
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        if (mb_strlen($busqueda) < 2) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $escuelas = (new modelSolicitud($this->pdo))->buscarEscuelasParaSolicitud($busqueda);
        echo json_encode(array_map(fn($escuela) => [
            'id' => (int) $escuela['id'],
            'cct' => $escuela['cct'],
            'nombre' => $escuela['nombre'],
            'nivel' => $escuela['nivel'],
            'municipio' => $escuela['municipio'],
            'localidad' => $escuela['localidad'],
            'servicio_regional_id' => (int) ($escuela['servicio_regional_id'] ?? 0),
            'servicio_regional' => $escuela['servicio_regional'],
            'cct_servicio_regional' => $escuela['cct_servicio_regional'],
        ], $escuelas), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function editar(int $id): void
    {
        $modelo = new modelSolicitud($this->pdo);
        $solicitud = $modelo->obtenerSolicitudPorId($id);
        if (!$solicitud || !$modelo->solicitudEsEditable($solicitud['estado'])) {
            mostrarNoEncontrado();
            return;
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $fechaSolicitud = (string) ($_POST['fecha_solicitud'] ?? '');
                $cicloPeriodo = trim((string) ($_POST['ciclo_periodo'] ?? ''));
                $observaciones = trim((string) ($_POST['observaciones'] ?? ''));

                $escuelas = array_filter(array_map('intval', (array) ($_POST['escuelas'] ?? [])));
                if (!$escuelas) {
                    throw new DomainException('Debe haber al menos una escuela en la solicitud.');
                }

                $cantidadesPorEscuela = [];
                $postCantidad = $_POST['cantidad'] ?? [];

                foreach ($escuelas as $escuelaId) {
                    $cantidadesPorEscuela[$escuelaId] = ['NINO' => [], 'NINA' => []];
                    foreach (['NINO', 'NINA'] as $sexo) {
                        $fuente = $postCantidad[$escuelaId][$sexo] ?? ($postCantidad[$sexo] ?? []);
                        foreach ((array) $fuente as $tallaId => $cantidad) {
                            $idT = filter_var($tallaId, FILTER_VALIDATE_INT);
                            $cant = filter_var($cantidad, FILTER_VALIDATE_INT);
                            if ($idT && $cant !== false) {
                                $cantidadesPorEscuela[$escuelaId][$sexo][$idT] = $cant;
                            }
                        }
                    }
                }

                $modelo->actualizarSolicitud(
                    $id,
                    $escuelas,
                    $fechaSolicitud,
                    $cicloPeriodo,
                    $observaciones,
                    $cantidadesPorEscuela
                );

                header('Location: ' . url('solicitud-detalle', ['id' => $id, 'actualizada' => 1]));
                exit;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $escuelasDetalle = $modelo->obtenerDetallePorEscuelaYTalla($id);
        $tallas = $modelo->obtenerTallasActivas();
        $disponibilidadGeneral = $modelo->obtenerDisponibilidadGeneral();

        renderizarVista(
            'solicitudes/editar',
            compact('solicitud', 'escuelasDetalle', 'tallas', 'disponibilidadGeneral', 'error'),
            'Editar solicitud',
            'solicitudes'
        );
    }

    public function detalle(int $id): void
    {
        $modelo = new modelSolicitud($this->pdo);
        $solicitud = $modelo->obtenerSolicitudPorId($id);
        if (!$solicitud) {
            mostrarNoEncontrado();
            return;
        }

        $detalle = $modelo->obtenerDetallePorTalla($id);
        $escuelasDetalle = $modelo->obtenerDetallePorEscuelaYTalla($id);
        $modelEntrega = new modelEntrega($this->pdo);
        $planDespacho = $modelEntrega->obtenerPlanDespachoPorSolicitud($id);

        renderizarVista(
            'solicitudes/detalle',
            compact('solicitud', 'detalle', 'escuelasDetalle', 'planDespacho'),
            'Detalle de solicitud',
            'solicitudes'
        );
    }

    public function entregarDirecto(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            header('Location: ' . url('solicitudes'));
            exit;
        }

        $solicitudId = filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT);

        if (!$solicitudId) {
            header('Location: ' . url('solicitudes'));
            exit;
        }

        try {
            $modelEntrega = new modelEntrega($this->pdo);
            $entregas = $modelEntrega->despacharEntregaPorSolicitud($solicitudId);
            $resumen = [];
            foreach ($entregas as $e) {
                $resumen[] = "{$e['folio']} ({$e['almacen']} → {$e['servicio']}, " . numero($e['total']) . " piezas)";
            }
            $foliosMsg = implode('; ', $resumen);
            header('Location: ' . url('solicitudes', [
                'entrega_ok' => 1,
                'folios' => $foliosMsg,
                'piezas' => array_sum(array_column($entregas, 'total')),
            ]));
            exit;
        } catch (Throwable $e) {
            header('Location: ' . url('solicitudes', ['error' => $e->getMessage()]));
            exit;
        }
    }
}
