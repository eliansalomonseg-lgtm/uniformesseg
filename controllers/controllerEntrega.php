<?php

declare(strict_types=1);

require_once __DIR__.'/../models/modelEntrega.php';
require_once __DIR__.'/../models/modelAlmacen.php';
require_once __DIR__.'/../models/modelServicioRegional.php';
require_once __DIR__.'/../models/modelSolicitud.php';

class controllerEntrega
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        $entregas = (new modelEntrega($this->pdo))->obtenerEntregas();
        renderizarVista('entregas/index', compact('entregas'), 'Entregas regionales', 'entregas');
    }

    public function nueva(): void
    {
        $error = null;
        $modeloEntrega = new modelEntrega($this->pdo);

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'enviar') {
            try {
                $almacenId = filter_input(INPUT_POST, 'almacen_id', FILTER_VALIDATE_INT);
                $servicioId = filter_input(INPUT_POST, 'servicio_id', FILTER_VALIDATE_INT);
                if (!$almacenId || !$servicioId) {
                    throw new DomainException('Seleccione un almacén y un Servicio Regional válidos.');
                }
                $entregaId = $modeloEntrega->registrarEnvioServicioRegional($almacenId, $servicioId);
                header('Location: ' . url('entrega-detalle', ['id' => $entregaId, 'enviada' => 1]));
                exit;
            } catch (Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        $almacenId = filter_input(INPUT_GET, 'almacen_id', FILTER_VALIDATE_INT) ?: 0;
        $servicioId = filter_input(INPUT_GET, 'servicio_id', FILTER_VALIDATE_INT) ?: 0;

        $almacenes = (new modelAlmacen($this->pdo))->obtenerAlmacenes();
        $servicios = (new modelServicioRegional($this->pdo))->obtenerServiciosRegionales();

        // Enlace automático: evitar que almacenes y servicios se crucen
        if ($servicioId && !$almacenId) {
            $almMatch = $modeloEntrega->obtenerAlmacenCorrespondiente($servicioId);
            if ($almMatch) {
                $almacenId = (int)$almMatch['id'];
            }
        } elseif ($almacenId && !$servicioId) {
            $srMatch = (new modelServicioRegional($this->pdo))->obtenerServicioRegionalPorAlmacen($almacenId);
            if ($srMatch) {
                $servicioId = (int)$srMatch['id'];
            }
        } elseif ($almacenId && $servicioId) {
            if (!$modeloEntrega->sonCorrespondientes($almacenId, $servicioId)) {
                $almMatch = $modeloEntrega->obtenerAlmacenCorrespondiente($servicioId);
                $almacenId = (int)$almMatch['id'];
            }
        }

        $comparativo = $almacenId && $servicioId ? $modeloEntrega->obtenerComparativo($almacenId, $servicioId) : [];
        $solicitudes = $servicioId ? (new modelSolicitud($this->pdo))->obtenerPendientesPorServicioRegional($servicioId) : [];

        renderizarVista(
            'entregas/nueva',
            compact('almacenes', 'servicios', 'almacenId', 'servicioId', 'comparativo', 'solicitudes', 'error'),
            'Preparar entrega',
            'entregas'
        );
    }

    public function detalle(int $id): void
    {
        $modelo = new modelEntrega($this->pdo);
        $entrega = $modelo->obtenerEntregaPorId($id);
        if (!$entrega) {
            mostrarNoEncontrado();
            return;
        }
        $detalle = $modelo->obtenerDetalle($id);
        $solicitudes = $modelo->obtenerSolicitudesPorEntrega($id);
        renderizarVista('entregas/detalle', compact('entrega', 'detalle', 'solicitudes'), 'Detalle de entrega', 'entregas');
    }

    public function cancelar(int $id = 0): void
    {
        $id = $id ?: (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
        if (!$id) {
            header('Location: ' . url('entregas'));
            exit;
        }

        $origen = (string)($_REQUEST['origen'] ?? 'entregas');

        try {
            $modelo = new modelEntrega($this->pdo);
            $folio = $modelo->cancelarEntrega($id);
            if ($origen === 'detalle') {
                header('Location: ' . url('entrega-detalle', ['id' => $id, 'cancelada' => 1]));
            } else {
                header('Location: ' . url('entregas', ['cancelada' => 1, 'folio' => $folio]));
            }
            exit;
        } catch (Throwable $e) {
            if ($origen === 'detalle') {
                header('Location: ' . url('entrega-detalle', ['id' => $id, 'error' => $e->getMessage()]));
            } else {
                header('Location: ' . url('entregas', ['error' => $e->getMessage()]));
            }
            exit;
        }
    }

    public function eliminar(int $id = 0): void
    {
        $id = $id ?: (filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
        if (!$id) {
            header('Location: ' . url('entregas'));
            exit;
        }

        try {
            $modelo = new modelEntrega($this->pdo);
            $folio = $modelo->eliminarEntrega($id);
            header('Location: ' . url('entregas', ['eliminada' => 1, 'folio' => $folio]));
            exit;
        } catch (Throwable $e) {
            $origen = (string)($_REQUEST['origen'] ?? 'entregas');
            if ($origen === 'detalle') {
                header('Location: ' . url('entrega-detalle', ['id' => $id, 'error' => $e->getMessage()]));
            } else {
                header('Location: ' . url('entregas', ['error' => $e->getMessage()]));
            }
            exit;
        }
    }
}
