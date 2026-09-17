<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/modelEntregaEscuela.php';
require_once __DIR__ . '/../models/modelSolicitud.php';
require_once __DIR__ . '/../models/modelEntrega.php';
require_once __DIR__ . '/../services/serviceQr.php';
require_once __DIR__ . '/../services/serviceUpload.php';

class controllerEntregaEscuela
{
    public function __construct(private PDO $pdo) {}

    /**
     * Formulario y procesamiento de entrega directa al plantel escolar.
     */
    public function nueva(): void
    {
        $solicitudId = filter_input(INPUT_GET, 'solicitud_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'solicitud_id', FILTER_VALIDATE_INT) ?: 0;
        $escuelaId = filter_input(INPUT_GET, 'escuela_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'escuela_id', FILTER_VALIDATE_INT) ?: 0;

        if (!$solicitudId || !$escuelaId) {
            header('Location: ' . url('solicitudes'));
            exit;
        }

        $modelSolicitud = new modelSolicitud($this->pdo);
        $solicitud = $modelSolicitud->obtenerSolicitudPorId($solicitudId);
        if (!$solicitud) {
            mostrarNoEncontrado();
            return;
        }

        $modelEntregaEscuela = new modelEntregaEscuela($this->pdo);
        // Verificar si ya fue entregada
        $entregaPrevia = $modelEntregaEscuela->obtenerEntregaPorEscuelaYSolicitud($solicitudId, $escuelaId);
        if ($entregaPrevia) {
            header('Location: ' . url('acta-escuela', ['id' => $entregaPrevia['id']]));
            exit;
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            try {
                $servicioRegionalId = filter_input(INPUT_POST, 'servicio_regional_id', FILTER_VALIDATE_INT) ?: 0;
                $entregaRegionalId = filter_input(INPUT_POST, 'entrega_regional_id', FILTER_VALIDATE_INT) ?: null;
                $fechaEntrega = (string)($_POST['fecha_entrega'] ?? date('Y-m-d'));
                $recibidoNombre = (string)($_POST['recibido_por_nombre'] ?? '');
                $recibidoCargo = (string)($_POST['recibido_por_cargo'] ?? 'Director(a)');
                $recibidoIden = (string)($_POST['recibido_por_identificacion'] ?? '');
                $recibidoTel = (string)($_POST['recibido_por_telefono'] ?? '');
                $entregadoNombre = (string)($_POST['entregado_por_nombre'] ?? '');
                $observaciones = (string)($_POST['observaciones'] ?? '');
                $cantidades = (array)($_POST['cantidad'] ?? []);

                $entregaId = $modelEntregaEscuela->registrarEntregaEscuela(
                    $solicitudId,
                    $escuelaId,
                    $servicioRegionalId,
                    $entregaRegionalId,
                    $fechaEntrega,
                    $recibidoNombre,
                    $recibidoCargo,
                    $recibidoIden,
                    $recibidoTel,
                    $entregadoNombre,
                    $observaciones,
                    $cantidades
                );

                header('Location: ' . url('acta-escuela', ['id' => $entregaId, 'exito' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        // Obtener datos de la escuela específica dentro de la solicitud
        $escuelasDetalle = $modelSolicitud->obtenerDetallePorEscuelaYTalla($solicitudId);
        $escuelaData = null;
        foreach ($escuelasDetalle as $ed) {
            if ((int)$ed['escuela_id'] === $escuelaId) {
                $escuelaData = $ed;
                break;
            }
        }

        if (!$escuelaData) {
            mostrarNoEncontrado();
            return;
        }

        $tallas = $modelSolicitud->obtenerTallasActivas();

        renderizarVista(
            'entregas_escuelas/nueva',
            compact('solicitud', 'escuelaData', 'tallas', 'error'),
            'Entrega Física al Plantel Escolar',
            'solicitudes'
        );
    }

    /**
     * Acta oficial de entrega-recepción con código QR verificador.
     */
    public function acta(int $id): void
    {
        $modelo = new modelEntregaEscuela($this->pdo);
        $entrega = $modelo->obtenerEntregaEscuelaPorId($id);
        if (!$entrega) {
            mostrarNoEncontrado();
            return;
        }

        // Generar URL y Código QR en SVG
        $urlVerificacion = serviceQr::generarUrlVerificacion($entrega['codigo_verificacion']);
        $qrSvg = serviceQr::generarQrSvg($urlVerificacion, 170);

        renderizarVista(
            'entregas_escuelas/acta_entrega',
            compact('entrega', 'urlVerificacion', 'qrSvg'),
            'Acta Oficial de Entrega-Recepción Escolar',
            'solicitudes'
        );
    }

    /**
     * Subida de acuse digital firmado y sellado.
     */
    public function subirAcuse(int $id): void
    {
        $modelo = new modelEntregaEscuela($this->pdo);
        $entrega = $modelo->obtenerEntregaEscuelaPorId($id);
        if (!$entrega) {
            mostrarNoEncontrado();
            return;
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['archivo_acuse'])) {
            try {
                $rutaGuardada = serviceUpload::guardarAcuse($_FILES['archivo_acuse'], 'acuse_esc_' . $id);
                $modelo->guardarArchivoAcuse($id, $rutaGuardada);
                header('Location: ' . url('acta-escuela', ['id' => $id, 'acuse_ok' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        renderizarVista(
            'entregas_escuelas/subir_acuse',
            compact('entrega', 'error'),
            'Subir Acuse Escaneado de Entrega',
            'solicitudes'
        );
    }

    /**
     * Subida de acuse digital para una entrega regional.
     */
    public function subirAcuseRegional(int $id): void
    {
        $modelEntrega = new modelEntrega($this->pdo);
        $entrega = $modelEntrega->obtenerEntregaPorId($id);
        if (!$entrega) {
            mostrarNoEncontrado();
            return;
        }

        $error = null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['archivo_acuse'])) {
            try {
                $rutaGuardada = serviceUpload::guardarAcuse($_FILES['archivo_acuse'], 'acuse_reg_' . $id);
                $modelo = new modelEntregaEscuela($this->pdo);
                $modelo->guardarAcuseRegional($id, $rutaGuardada);
                header('Location: ' . url('entrega-detalle', ['id' => $id, 'acuse_ok' => 1]));
                exit;
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        renderizarVista(
            'entregas_escuelas/subir_acuse_regional',
            compact('entrega', 'error'),
            'Subir Acuse Firmado de Entrega Regional',
            'entregas'
        );
    }
}
